<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $productId = null;

    public ?int $categoryId = null;

    public string $name = '';

    public ?string $sku = null;

    public ?string $barcode = null;

    public ?string $unit = null;

    public ?float $cost_price = null;

    public ?float $sale_price = null;

    public string $currency = 'CDF';

    public int $stock_quantity = 0;

    public int $min_stock = 0;

    public int $reorder_qty = 0;

    public string $search = '';

    public string $deleteError = '';

    public bool $showArchived = false;

    public $importFile;

    public $importExcelFile;

    public array $importErrors = [];

    public int $importedCount = 0;

    public int $skippedCount = 0;

    public bool $importCreateMissing = false;

    public bool $importMatchByName = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected function rules(): array
    {
        return [
            'categoryId' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($this->productId),
            ],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', Rule::in(['CDF', 'USD', 'EUR'])],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'reorder_qty' => ['required', 'integer', 'min:0'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowArchived(): void
    {
        $this->resetPage();
    }

    public function editProduct(int $productId): void
    {
        $this->authorizeAccess();
        $this->deleteError = '';
        $product = Product::query()->with('stock')->findOrFail($productId);

        $this->productId = $product->id;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode;
        $this->unit = $product->unit;
        $this->cost_price = $product->cost_price;
        $this->sale_price = $product->sale_price;
        $this->currency = $product->currency ?? 'CDF';
        $this->stock_quantity = $product->stock?->quantity ?? 0;
        $this->min_stock = $product->min_stock ?? 0;
        $this->reorder_qty = $product->reorder_qty ?? 0;
    }

    public function resetForm(): void
    {
        $this->deleteError = '';
        $this->reset([
            'productId',
            'categoryId',
            'name',
            'sku',
            'barcode',
            'unit',
            'cost_price',
            'sale_price',
            'currency',
            'stock_quantity',
            'min_stock',
            'reorder_qty',
        ]);
    }

    public function saveProduct(): void
    {
        $this->authorizeAccess();
        $this->deleteError = '';
        $validated = $this->validate();

        $product = Product::updateOrCreate(
            ['id' => $this->productId],
            [
                'category_id' => $validated['categoryId'],
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'barcode' => $validated['barcode'],
                'unit' => $validated['unit'],
                'cost_price' => $validated['cost_price'],
                'sale_price' => $validated['sale_price'],
                'currency' => $validated['currency'],
                'min_stock' => $validated['min_stock'],
                'reorder_qty' => $validated['reorder_qty'],
            ]
        );

        Stock::updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $validated['stock_quantity']]
        );

        $this->resetForm();
    }

    public function deleteProduct(int $productId): void
    {
        $this->authorizeAccess();
        $product = Product::query()->findOrFail($productId);

        $product->update([
            'archived_at' => Carbon::now(),
        ]);
        $this->deleteError = '';

        $this->resetForm();
    }

    public function restoreProduct(int $productId): void
    {
        $this->authorizeAccess();
        Product::query()
            ->whereNotNull('archived_at')
            ->findOrFail($productId)
            ->update(['archived_at' => null]);
    }

    public function importProducts(): void
    {
        $this->authorizeAccess();
        $this->resetImportState();

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $path = $this->importFile?->getRealPath();
        if (! $path || ! is_readable($path)) {
            $this->importErrors[] = 'Fichier CSV inaccessible.';

            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->importErrors[] = 'Impossible de lire le fichier CSV.';

            return;
        }

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            $this->importErrors[] = 'Fichier CSV vide.';
            fclose($handle);

            return;
        }

        $delimiter = $this->detectDelimiter($headerLine);
        $headers = str_getcsv($headerLine, $delimiter);
        $headerMap = $this->buildHeaderMap($headers);
        if (! isset($headerMap['name'])) {
            $this->importErrors[] = 'La colonne "name" est obligatoire.';
            fclose($handle);

            return;
        }

        DB::beginTransaction();
        try {
            $lineNumber = 1;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $rawData = $this->mapRow($row, $headerMap);
                [$errors, $data] = $this->sanitizeImportRow($rawData, $lineNumber);
                if ($errors !== []) {
                    $this->importErrors = array_merge($this->importErrors, $errors);
                    $this->skippedCount++;

                    continue;
                }

                $categoryId = $this->resolveCategoryId($data);
                $product = $this->findExistingProduct($data);

                $payload = [
                    'category_id' => $categoryId,
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'],
                    'unit' => $data['unit'],
                    'cost_price' => $data['cost_price'],
                    'sale_price' => $data['sale_price'],
                    'currency' => $data['currency'],
                    'min_stock' => $data['min_stock'],
                    'reorder_qty' => $data['reorder_qty'],
                ];

                if ($product) {
                    $product->update($payload);
                } else {
                    $product = Product::query()->create($payload);
                }

                Stock::updateOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => $data['stock_quantity']]
                );

                $this->importedCount++;
            }

            if ($this->importErrors !== []) {
                DB::rollBack();
                $this->importedCount = 0;
                $this->importErrors[] = 'Import annule: des erreurs ont ete detectees.';
            } else {
                DB::commit();
            }
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->importErrors[] = 'Import annule: une erreur est survenue.';
            report($exception);
        } finally {
            fclose($handle);
            $this->importFile = null;
        }
    }

    public function importProductsExcel(): void
    {
        $this->authorizeAdminAccess();
        $this->resetImportState();

        $this->validate([
            'importExcelFile' => ['required', 'file', 'mimes:xls,xlsx', 'max:20480'],
        ]);

        $rows = $this->loadExcelRows();
        if ($rows->isEmpty()) {
            $this->importErrors[] = 'Fichier Excel vide.';
            $this->importExcelFile = null;

            return;
        }

        $headers = $rows->shift() ?? [];
        $headerMap = $this->buildHeaderMap($headers);
        if (! isset($headerMap['name'])) {
            $this->importErrors[] = 'La colonne "name" est obligatoire.';
            $this->importExcelFile = null;

            return;
        }

        DB::beginTransaction();
        try {
            $lineNumber = 1;
            foreach ($rows as $row) {
                $lineNumber++;
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $rawData = $this->mapRow($row, $headerMap);
                [$errors, $data] = $this->sanitizeImportRow($rawData, $lineNumber);
                if ($errors !== []) {
                    $this->importErrors = array_merge($this->importErrors, $errors);
                    $this->skippedCount++;

                    continue;
                }

                $categoryId = $this->resolveCategoryId($data);
                $product = $this->findExistingProduct($data, $this->importMatchByName);

                if (! $product && ! $this->importCreateMissing) {
                    $this->importErrors[] = "Ligne {$lineNumber}: produit introuvable (sku, code-barres ou nom).";
                    $this->skippedCount++;

                    continue;
                }

                $payload = [
                    'category_id' => $categoryId,
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'],
                    'unit' => $data['unit'],
                    'cost_price' => $data['cost_price'],
                    'sale_price' => $data['sale_price'],
                    'currency' => $data['currency'],
                    'min_stock' => $data['min_stock'],
                    'reorder_qty' => $data['reorder_qty'],
                ];

                if (! $this->canApplyBarcodeUpdate($data['barcode'], $product)) {
                    $this->importErrors[] = "Ligne {$lineNumber}: code-barres deja utilise par un autre produit.";
                    $this->skippedCount++;

                    continue;
                }

                if ($product) {
                    $product->update($payload);
                } else {
                    $product = Product::query()->create($payload);
                }

                Stock::updateOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => $data['stock_quantity']]
                );

                $this->importedCount++;
            }

            if ($this->importErrors !== []) {
                DB::rollBack();
                $this->importedCount = 0;
                $this->importErrors[] = 'Import annule: des erreurs ont ete detectees.';
            } else {
                DB::commit();
            }
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->importErrors[] = 'Import annule: une erreur est survenue.';
            report($exception);
        } finally {
            $this->importExcelFile = null;
        }
    }

    public function exportProducts(): StreamedResponse
    {
        $this->authorizeAccess();

        $headers = $this->csvHeaders();
        $products = Product::query()
            ->with(['category', 'stock'])
            ->whereNull('archived_at')
            ->orderBy('name')
            ->cursor();

        return response()->streamDownload(function () use ($headers, $products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku,
                    $product->barcode,
                    $product->unit,
                    $product->cost_price,
                    $product->sale_price,
                    $product->currency ?? 'CDF',
                    $product->stock?->quantity ?? 0,
                    $product->min_stock ?? 0,
                    $product->reorder_qty ?? 0,
                    $product->category?->name,
                    $product->category_id,
                ]);
            }

            fclose($handle);
        }, 'products.csv');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $this->authorizeAccess();

        $headers = $this->csvHeaders();

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, [
                'Exemple produit',
                'SKU-001',
                'BAR-001',
                'piece',
                1000,
                1500,
                'CDF',
                10,
                2,
                5,
                'Categorie',
                '',
            ]);
            fclose($handle);
        }, 'products-template.csv');
    }

    public function render()
    {
        $this->authorizeAccess();
        $products = Product::query()
            ->with(['category', 'stock'])
            ->whereNull('archived_at')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('barcode', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10);

        $archivedProducts = collect();
        if ($this->showArchived) {
            $archivedProducts = Product::query()
                ->with(['category', 'stock'])
                ->whereNotNull('archived_at')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('sku', 'like', '%'.$this->search.'%')
                            ->orWhere('barcode', 'like', '%'.$this->search.'%');
                    });
                })
                ->orderBy('name')
                ->paginate(10, pageName: 'archived');
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('livewire.products.index', [
            'products' => $products,
            'archivedProducts' => $archivedProducts,
            'categories' => $categories,
        ])->layout('layouts.app');
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->role !== 'vendeur_simple', 403);
    }

    private function authorizeAdminAccess(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin(), 403);
    }

    private function resetImportState(): void
    {
        $this->importErrors = [];
        $this->importedCount = 0;
        $this->skippedCount = 0;
    }

    private function csvHeaders(): array
    {
        return [
            'name',
            'sku',
            'barcode',
            'unit',
            'cost_price',
            'sale_price',
            'currency',
            'stock_quantity',
            'min_stock',
            'reorder_qty',
            'category',
            'category_id',
        ];
    }

    private function detectDelimiter(string $headerLine): string
    {
        $candidates = [',' => substr_count($headerLine, ','), ';' => substr_count($headerLine, ';'), "\t" => substr_count($headerLine, "\t")];
        arsort($candidates);

        $delimiter = array_key_first($candidates);

        return $delimiter ?? ',';
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::of($header)
            ->lower()
            ->replace([' ', '-', '/'], '_')
            ->replace(['é', 'è', 'ê', 'ë'], 'e')
            ->replace(['à', 'â'], 'a')
            ->replace(['ô'], 'o')
            ->replace(['ù', 'û'], 'u')
            ->replace(['ç'], 'c')
            ->toString();

        $header = preg_replace('/[^a-z0-9_]/', '', $header);
        $aliases = [
            'nom' => 'name',
            'produit' => 'name',
            'categorie' => 'category',
            'category_name' => 'category',
            'codebarres' => 'barcode',
            'code_barres' => 'barcode',
            'prix_achat' => 'cost_price',
            'prix_vente' => 'sale_price',
            'devise' => 'currency',
            'stock' => 'stock_quantity',
            'seuil' => 'min_stock',
            'reappro' => 'reorder_qty',
        ];

        return $aliases[$header] ?? $header;
    }

    private function buildHeaderMap(array $headers): array
    {
        $allowed = array_flip($this->csvHeaders());
        $map = [];

        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized === '' || ! isset($allowed[$normalized])) {
                continue;
            }
            $map[$normalized] = $index;
        }

        return $map;
    }

    private function mapRow(array $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $key => $index) {
            $value = $row[$index] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            if ($value === '') {
                $value = null;
            }
            $data[$key] = $value;
        }

        return $data;
    }

    private function sanitizeImportRow(array $data, int $lineNumber): array
    {
        $errors = [];

        $name = $data['name'] ?? null;
        if (! $name) {
            $errors[] = "Ligne {$lineNumber}: nom manquant.";
        }

        $currency = $data['currency'] ?? 'CDF';
        $currency = strtoupper((string) $currency);
        if (! in_array($currency, ['CDF', 'USD', 'EUR'], true)) {
            $errors[] = "Ligne {$lineNumber}: devise invalide.";
        }

        $data['currency'] = $currency ?: 'CDF';
        $data['sku'] = $this->normalizeIdentifier($data['sku'] ?? null);
        $data['barcode'] = $this->normalizeIdentifier($data['barcode'] ?? null);
        $data['unit'] = $data['unit'] ?? null;

        $data['cost_price'] = $this->parseNumber($data['cost_price'] ?? null, false, $errors, $lineNumber, 'prix_achat');
        $data['sale_price'] = $this->parseNumber($data['sale_price'] ?? null, false, $errors, $lineNumber, 'prix_vente');
        $data['stock_quantity'] = $this->parseNumber($data['stock_quantity'] ?? null, true, $errors, $lineNumber, 'stock');
        $data['min_stock'] = $this->parseNumber($data['min_stock'] ?? null, true, $errors, $lineNumber, 'seuil');
        $data['reorder_qty'] = $this->parseNumber($data['reorder_qty'] ?? null, true, $errors, $lineNumber, 'reappro');

        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['reorder_qty'] = $data['reorder_qty'] ?? 0;

        return [$errors, $data];
    }

    private function parseNumber(?string $value, bool $integer, array &$errors, int $lineNumber, string $label): int|float|null
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], $value);
        if (! is_numeric($normalized)) {
            $errors[] = "Ligne {$lineNumber}: valeur invalide pour {$label}.";

            return null;
        }

        $number = (float) $normalized;
        if ($number < 0) {
            $errors[] = "Ligne {$lineNumber}: valeur negative pour {$label}.";

            return null;
        }

        return $integer ? (int) round($number) : $number;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function resolveCategoryId(array $data): ?int
    {
        $categoryId = $data['category_id'] ?? null;
        if ($categoryId !== null && is_numeric($categoryId)) {
            return (int) $categoryId;
        }

        $categoryName = $data['category'] ?? null;
        if ($categoryName) {
            return Category::query()->firstOrCreate(['name' => $categoryName])->id;
        }

        return null;
    }

    private function findExistingProduct(array $data, bool $matchByName = false): ?Product
    {
        $sku = $data['sku'] ?? null;
        if ($sku) {
            return Product::query()->where('sku', $sku)->first();
        }

        $barcode = $data['barcode'] ?? null;
        if ($barcode) {
            return Product::query()->where('barcode', $barcode)->first();
        }

        if ($matchByName) {
            $name = $data['name'] ?? null;
            if ($name) {
                return Product::query()->where('name', $name)->first();
            }
        }

        return null;
    }

    private function canApplyBarcodeUpdate(?string $barcode, ?Product $product): bool
    {
        if (! $barcode) {
            return true;
        }

        $query = Product::query()->where('barcode', $barcode);
        if ($product) {
            $query->whereKeyNot($product->id);
        }

        return ! $query->exists();
    }

    private function normalizeIdentifier($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;
            if ($numeric === (float) (int) $numeric) {
                return (string) (int) $numeric;
            }
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function loadExcelRows(): Collection
    {
        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array): array
            {
                return $array;
            }
        }, $this->importExcelFile);

        $rows = collect($sheets[0] ?? []);

        return $rows->map(function ($row) {
            return array_map(function ($value) {
                $value = is_string($value) ? trim($value) : $value;

                return $value === '' ? null : $value;
            }, is_array($row) ? $row : []);
        });
    }
}
