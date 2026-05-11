<?php

namespace App\Livewire\Sales;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $customer_name = null;

    public ?string $sold_at = null;

    public array $items = [];

    public string $barcodeInput = '';

    public string $productSearch = '';

    public float $discountRate = 0;

    public float $taxRate = 0;

    public ?int $lastInvoiceId = null;

    public float $amountReceived = 0;

    public string $screenMode = 'pc';

    public bool $checkout = false;

    public ?int $selectedProductId = null;

    public float $selectedQuantity = 1;

    public float $selectedDiscountRate = 0;

    public ?float $selectedUnitPrice = null;

    protected function rules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:255'],
            'sold_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discountRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function mount(): void
    {
        $this->sold_at = now()->format('Y-m-d');
        $this->items = [];
        $this->screenMode = auth()->user()?->screen_mode ?? 'pc';
    }

    public function updatingProductSearch(): void
    {
        $this->resetPage();
    }

    public function updatedScreenMode(string $value): void
    {
        $value = in_array($value, ['pos', 'tablet', 'pc', 'mobile'], true) ? $value : 'pc';
        $this->screenMode = $value;

        $user = auth()->user();
        if ($user) {
            $user->update(['screen_mode' => $value]);
        }
    }

    public function setScreenMode(string $value): void
    {
        $this->updatedScreenMode($value);
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'quantity' => 1,
            'unit_price' => null,
            'discount_rate' => 0,
        ];

        $this->dispatch('focus-barcode');
    }

    public function selectProduct(int $productId): void
    {
        $this->selectedProductId = $productId;
        $product = Product::query()->select(['id', 'sold_by_weight'])->find($productId);
        $minQty = $product?->sold_by_weight ? 0.001 : 1;
        $this->selectedQuantity = max($minQty, (float) $this->selectedQuantity);
        $this->selectedDiscountRate = (float) $this->selectedDiscountRate;
        $this->selectedUnitPrice = Product::query()
            ->whereKey($productId)
            ->value(DB::raw('COALESCE(promo_price, sale_price)'));

        $this->dispatch('focus-barcode');
    }

    public function addToCart(): void
    {
        if (! $this->selectedProductId) {
            throw ValidationException::withMessages([
                'selectedProductId' => 'Selectionnez un produit.',
            ]);
        }

        $product = Product::query()->select(['id', 'sold_by_weight', 'promo_price', 'sale_price'])->find($this->selectedProductId);
        $minQty = $product?->sold_by_weight ? 0.001 : 1;
        $quantity = max($minQty, (float) $this->selectedQuantity);
        $discountRate = max(0, min(100, (float) $this->selectedDiscountRate));
        $price = $product?->promo_price !== null ? (float) $product->promo_price : (float) ($product?->sale_price ?? 0);

        foreach ($this->items as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) === (int) $this->selectedProductId) {
                $this->items[$index]['quantity'] = ((float) ($item['quantity'] ?? 0)) + $quantity;
                $this->items[$index]['unit_price'] = $price;
                $this->items[$index]['discount_rate'] = $discountRate;
                $this->resetSelectedItem();
                $this->dispatch('notify', message: 'Ajoute au panier.', lastAddedId: $this->selectedProductId);

                return;
            }
        }

        $this->items[] = [
            'product_id' => $this->selectedProductId,
            'quantity' => $quantity,
            'unit_price' => $price,
            'discount_rate' => $discountRate,
        ];

        $this->resetSelectedItem();
        $this->dispatch('notify', message: 'Ajoute au panier.', lastAddedId: $this->selectedProductId);
    }

    public function incrementSelectedQuantity(): void
    {
        $step = $this->selectedProductIsSoldByWeight() ? 0.1 : 1;
        $min = $this->selectedProductIsSoldByWeight() ? 0.001 : 1;
        $this->selectedQuantity = round(max($min, (float) $this->selectedQuantity) + $step, 3);
    }

    public function decrementSelectedQuantity(): void
    {
        $step = $this->selectedProductIsSoldByWeight() ? 0.1 : 1;
        $min = $this->selectedProductIsSoldByWeight() ? 0.001 : 1;
        $this->selectedQuantity = round(max($min, (float) $this->selectedQuantity - $step), 3);
    }

    private function selectedProductIsSoldByWeight(): bool
    {
        if (! $this->selectedProductId) {
            return false;
        }

        return (bool) Product::query()
            ->whereKey($this->selectedProductId)
            ->value('sold_by_weight');
    }

    public function resetSelectedItemForm(): void
    {
        $this->resetSelectedItem();
    }

    private function resetSelectedItem(): void
    {
        $this->selectedProductId = null;
        $this->selectedQuantity = 1;
        $this->selectedDiscountRate = 0;
        $this->selectedUnitPrice = null;
        $this->productSearch = '';
        $this->barcodeInput = '';
        $this->dispatch('focus-barcode');
    }

    public function addProduct(int $productId): void
    {
        $this->selectProduct($productId);
    }

    public function updatedBarcodeInput($value): void
    {
        $barcode = trim((string) $value);

        if ($barcode === '') {
            return;
        }

        $productId = Product::query()
            ->where('barcode', $barcode)
            ->value('id');

        if ($productId) {
            if ((int) $this->selectedProductId === (int) $productId) {
                $isByWeight = (bool) Product::query()->whereKey($productId)->value('sold_by_weight');
                $step = $isByWeight ? 0.1 : 1;
                $min = $isByWeight ? 0.001 : 1;
                $this->selectedQuantity = round(max($min, (float) $this->selectedQuantity) + $step, 3);
            } else {
                $this->selectProduct((int) $productId);
            }
            $this->barcodeInput = '';
            $this->dispatch('focus-barcode');
        }
    }

    public function updatedItems($value, $name): void
    {
        if (! str_ends_with($name, '.product_id')) {
            return;
        }

        $index = (int) explode('.', $name)[0];

        if (! $value) {
            $this->items[$index]['unit_price'] = null;

            return;
        }

        $product = Product::query()->select(['id', 'sale_price', 'promo_price'])->find($value);
        $price = $product && $product->promo_price !== null ? (float) $product->promo_price : (float) ($product?->sale_price ?? 0);
        $this->items[$index]['unit_price'] = $price;
    }

    public function updatedSelectedProductId($value): void
    {
        if (! $value) {
            $this->selectedUnitPrice = null;

            return;
        }

        $this->selectedUnitPrice = (float) (Product::query()
            ->whereKey($value)
            ->value(DB::raw('COALESCE(promo_price, sale_price)')) ?? 0);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function incrementQuantity(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $isByWeight = $this->itemIsSoldByWeight($index);
        $step = $isByWeight ? 0.1 : 1;
        $min = $isByWeight ? 0.001 : 1;
        $this->items[$index]['quantity'] = round(max($min, (float) ($this->items[$index]['quantity'] ?? 0)) + $step, 3);
        $this->dispatch('focus-barcode');
    }

    public function decrementQuantity(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $isByWeight = $this->itemIsSoldByWeight($index);
        $step = $isByWeight ? 0.1 : 1;
        $min = $isByWeight ? 0.001 : 1;
        $current = (float) ($this->items[$index]['quantity'] ?? $min);
        $this->items[$index]['quantity'] = round(max($min, $current - $step), 3);
        $this->dispatch('focus-barcode');
    }

    private function itemIsSoldByWeight(int $index): bool
    {
        $productId = $this->items[$index]['product_id'] ?? null;
        if (! $productId) {
            return false;
        }

        return (bool) Product::query()->whereKey($productId)->value('sold_by_weight');
    }

    public function resetForm(): void
    {
        $this->reset(['customer_name', 'sold_at', 'items']);
        $this->discountRate = 0;
        $this->taxRate = 0;
        $this->amountReceived = 0;
        $this->checkout = false;
        $this->sold_at = now()->format('Y-m-d');
        $this->items = [];
        $this->resetSelectedItem();
    }

    private function normalizeItemsWithPrices(array $items): array
    {
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
        $prices = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'sale_price', 'promo_price'])
            ->mapWithKeys(function ($product) {
                $price = $product->promo_price !== null ? (float) $product->promo_price : (float) $product->sale_price;

                return [$product->id => $price];
            });

        return collect($items)->map(function ($item) use ($prices) {
            $price = (float) ($prices[$item['product_id']] ?? 0);
            $item['unit_price'] = $price;
            $item['discount_rate'] = (float) ($item['discount_rate'] ?? 0);

            return $item;
        })->all();
    }

    private function calculateTotals(array $items, float $discountRate, float $taxRate): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $lineBase = $quantity * $price;
            $lineDiscountRate = (float) ($item['discount_rate'] ?? 0);
            $lineDiscountAmount = $lineBase * ($lineDiscountRate / 100);
            $lineTotal = $lineBase - $lineDiscountAmount;
            $subtotal += $lineTotal;
        }

        $discountAmount = $subtotal * ($discountRate / 100);
        $taxable = $subtotal - $discountAmount;
        $taxAmount = $taxable * ($taxRate / 100);
        $total = $taxable + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discountAmount' => round($discountAmount, 2),
            'taxAmount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    public function saveSale(): void
    {
        $validated = $this->validate();
        $validated['items'] = $this->normalizeItemsWithPrices($validated['items']);
        $invoiceId = null;
        $cartCurrencies = collect($validated['items'])
            ->map(fn ($item) => Product::query()->whereKey($item['product_id'])->value('currency') ?? 'CDF')
            ->unique()
            ->values();
        if ($cartCurrencies->count() > 1) {
            throw ValidationException::withMessages([
                'items' => 'Les articles doivent etre dans une seule devise.',
            ]);
        }

        $totalsByProduct = [];
        foreach ($validated['items'] as $item) {
            $totalsByProduct[$item['product_id']] = ($totalsByProduct[$item['product_id']] ?? 0) + $item['quantity'];
        }

        DB::transaction(function () use ($validated, $totalsByProduct, &$invoiceId) {
            $stocks = Stock::query()
                ->whereIn('product_id', array_keys($totalsByProduct))
                ->get()
                ->keyBy('product_id');

            foreach ($totalsByProduct as $productId => $requiredQty) {
                $currentQty = $stocks->get($productId)?->quantity ?? 0;
                if ($currentQty < $requiredQty) {
                    throw ValidationException::withMessages([
                        'items' => 'Stock insuffisant pour au moins un produit.',
                    ]);
                }
            }

            $totals = $this->calculateTotals($validated['items'], (float) $this->discountRate, (float) $this->taxRate);

            $sale = Sale::create([
                'user_id' => auth()->id(),
                'reference' => 'SALE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'customer_name' => $validated['customer_name'],
                'total_amount' => $totals['total'],
                'subtotal_amount' => $totals['subtotal'],
                'discount_rate' => $this->discountRate,
                'discount_amount' => $totals['discountAmount'],
                'tax_rate' => $this->taxRate,
                'tax_amount' => $totals['taxAmount'],
                'status' => 'paid',
                'sold_at' => $validated['sold_at'],
            ]);

            $saleItems = [];
            foreach ($validated['items'] as $item) {
                $lineBase = $item['quantity'] * $item['unit_price'];
                $lineDiscountAmount = $lineBase * ((float) ($item['discount_rate'] ?? 0) / 100);
                $lineTotal = $lineBase - $lineDiscountAmount;

                $saleItems[] = [
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_rate' => $item['discount_rate'] ?? 0,
                    'discount_amount' => $lineDiscountAmount,
                    'line_total' => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($saleItems)) {
                SaleItem::insert($saleItems);
            }

            $movements = [];
            foreach ($totalsByProduct as $productId => $requiredQty) {
                $stock = $stocks->get($productId) ?? Stock::create([
                    'product_id' => $productId,
                    'quantity' => 0,
                ]);

                $stock->decrement('quantity', $requiredQty);

                $movements[] = [
                    'product_id' => $productId,
                    'type' => 'out',
                    'quantity' => $requiredQty,
                    'reason' => 'Vente '.$sale->reference,
                    'occurred_at' => $validated['sold_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($movements)) {
                StockMovement::insert($movements);
            }

            $invoice = Invoice::create([
                'sale_id' => $sale->id,
                'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'total_amount' => $totals['total'],
                'status' => 'paid',
                'issued_at' => $validated['sold_at'],
                'due_at' => $validated['sold_at'],
            ]);

            $invoiceId = $invoice->id;
        });

        $this->lastInvoiceId = $invoiceId;
        $this->resetForm();
        $this->dispatch('notify', message: 'Vente enregistree.', invoiceId: $this->lastInvoiceId);
    }

    public function startCheckout(): void
    {
        $this->checkout = true;
    }

    public function backToCart(): void
    {
        $this->checkout = false;
    }

    public function setAmountReceived(float $amount): void
    {
        $this->amountReceived = $amount;
    }

    public function setAmountReceivedToTotal(): void
    {
        $totals = $this->calculateTotals($this->items, (float) $this->discountRate, (float) $this->taxRate);
        $this->amountReceived = $totals['total'];
    }

    public function loadPendingSale(int $saleId): void
    {
        $saleQuery = Sale::query()
            ->with('items')
            ->where('status', 'pending')
            ->whereKey($saleId);

        if (! auth()->user()?->isAdmin()) {
            $saleQuery->where('user_id', auth()->id());
        }

        $sale = $saleQuery->firstOrFail();

        $this->customer_name = $sale->customer_name;
        $this->sold_at = $sale->sold_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->discountRate = (float) ($sale->discount_rate ?? 0);
        $this->taxRate = (float) ($sale->tax_rate ?? 0);
        $this->items = $sale->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_rate' => (float) ($item->discount_rate ?? 0),
            ];
        })->all();

        $this->dispatch('focus-barcode');
    }

    public function savePending(): void
    {
        $validated = $this->validate();
        $validated['items'] = $this->normalizeItemsWithPrices($validated['items']);
        $cartCurrencies = collect($validated['items'])
            ->map(fn ($item) => Product::query()->whereKey($item['product_id'])->value('currency') ?? 'CDF')
            ->unique()
            ->values();
        if ($cartCurrencies->count() > 1) {
            throw ValidationException::withMessages([
                'items' => 'Les articles doivent etre dans une seule devise.',
            ]);
        }

        DB::transaction(function () use ($validated) {
            $totals = $this->calculateTotals($validated['items'], (float) $this->discountRate, (float) $this->taxRate);

            $sale = Sale::create([
                'user_id' => auth()->id(),
                'reference' => 'SALE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'customer_name' => $validated['customer_name'],
                'total_amount' => $totals['total'],
                'subtotal_amount' => $totals['subtotal'],
                'discount_rate' => $this->discountRate,
                'discount_amount' => $totals['discountAmount'],
                'tax_rate' => $this->taxRate,
                'tax_amount' => $totals['taxAmount'],
                'status' => 'pending',
                'sold_at' => $validated['sold_at'],
            ]);

            $saleItems = [];
            foreach ($validated['items'] as $item) {
                $lineBase = $item['quantity'] * $item['unit_price'];
                $lineDiscountAmount = $lineBase * ((float) ($item['discount_rate'] ?? 0) / 100);
                $lineTotal = $lineBase - $lineDiscountAmount;

                $saleItems[] = [
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_rate' => $item['discount_rate'] ?? 0,
                    'discount_amount' => $lineDiscountAmount,
                    'line_total' => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($saleItems)) {
                SaleItem::insert($saleItems);
            }
        });

        $this->resetForm();
    }

    public function finalizeSale(int $saleId): void
    {
        $saleQuery = Sale::query()->with('items')->whereKey($saleId);
        if (! auth()->user()?->isAdmin()) {
            $saleQuery->where('user_id', auth()->id());
        }

        $sale = $saleQuery->firstOrFail();

        if ($sale->status === 'paid') {
            return;
        }

        $totalsByProduct = $sale->items
            ->groupBy('product_id')
            ->map(fn ($items) => $items->sum('quantity'))
            ->toArray();

        DB::transaction(function () use ($sale, $totalsByProduct) {
            $stocks = Stock::query()
                ->whereIn('product_id', array_keys($totalsByProduct))
                ->get()
                ->keyBy('product_id');

            foreach ($totalsByProduct as $productId => $requiredQty) {
                $currentQty = $stocks->get($productId)?->quantity ?? 0;
                if ($currentQty < $requiredQty) {
                    throw ValidationException::withMessages([
                        'items' => 'Stock insuffisant pour finaliser cette vente.',
                    ]);
                }
            }

            foreach ($totalsByProduct as $productId => $requiredQty) {
                $stock = $stocks->get($productId) ?? Stock::create([
                    'product_id' => $productId,
                    'quantity' => 0,
                ]);

                $stock->update([
                    'quantity' => $stock->quantity - $requiredQty,
                ]);

                StockMovement::create([
                    'product_id' => $productId,
                    'type' => 'out',
                    'quantity' => $requiredQty,
                    'reason' => 'Vente '.$sale->reference,
                    'occurred_at' => $sale->sold_at ?? now(),
                ]);
            }

            $totals = $this->calculateTotals($sale->items->toArray(), (float) $sale->discount_rate, (float) $sale->tax_rate);

            $sale->update([
                'status' => 'paid',
                'total_amount' => $totals['total'],
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discountAmount'],
                'tax_amount' => $totals['taxAmount'],
                'sold_at' => $sale->sold_at ?? now(),
            ]);

            if (! $sale->invoice) {
                Invoice::create([
                    'sale_id' => $sale->id,
                    'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                    'total_amount' => $totals['total'],
                    'status' => 'paid',
                    'issued_at' => $sale->sold_at ?? now(),
                    'due_at' => $sale->sold_at ?? now(),
                ]);
            }
        });
    }

    public function render()
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;
        $products = Product::query()
            ->with(['stock', 'category'])
            ->orderBy('name')
            ->get();
        $productsById = $products->keyBy('id');

        $filteredProducts = collect();
        if ($this->productSearch !== '') {
            $filteredProducts = Product::query()
                ->with('stock')
                ->where('name', 'like', '%'.$this->productSearch.'%')
                ->orderBy('name')
                ->limit(6)
                ->get();
        }

        $favoriteProducts = auth()->user()
            ? auth()->user()->favoriteProducts()->with('stock')->limit(6)->get()
            : collect();

        $frequentProductIds = [];
        if (Schema::hasColumns('sale_items', ['product_id', 'sale_id', 'quantity']) && Schema::hasColumn('sales', 'status')) {
            $frequentProductQuery = SaleItem::query()
                ->select('product_id', DB::raw('sum(quantity) as total_qty'))
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', 'paid')
                ->groupBy('product_id')
                ->orderByDesc('total_qty');

            if (! $isAdmin) {
                $frequentProductQuery->where('sales.user_id', auth()->id());
            }

            $frequentProductIds = $frequentProductQuery
                ->limit(6)
                ->pluck('product_id')
                ->all();
        }

        $frequentProducts = collect();
        if (! empty($frequentProductIds)) {
            $frequentProductsQuery = Product::query()
                ->with('stock')
                ->whereIn('id', $frequentProductIds);

            if (DB::getDriverName() === 'mysql') {
                $ids = implode(',', $frequentProductIds);
                $frequentProductsQuery->orderByRaw("FIELD(id, {$ids})");
            } else {
                $caseSql = 'CASE id ';
                $bindings = [];
                foreach (array_values($frequentProductIds) as $index => $productId) {
                    $caseSql .= 'WHEN ? THEN '.$index.' ';
                    $bindings[] = $productId;
                }
                $caseSql .= 'END';
                $frequentProductsQuery->orderByRaw($caseSql, $bindings);
            }

            $frequentProducts = $frequentProductsQuery->get();
        }

        $totals = $this->calculateTotals($this->items, (float) $this->discountRate, (float) $this->taxRate);
        $pendingSalesQuery = Sale::query()
            ->withCount('items')
            ->where('status', 'pending')
            ->orderByDesc('sold_at');

        if (! $isAdmin) {
            $pendingSalesQuery->where('user_id', auth()->id());
        }

        $pendingSales = $pendingSalesQuery
            ->limit(5)
            ->get();

        $changeDue = max(0, round($this->amountReceived - $totals['total'], 2));
        $cartCurrencies = collect($this->items)
            ->map(fn ($item) => $productsById->get($item['product_id'])?->currency ?? 'CDF')
            ->unique()
            ->values();
        $cartCurrency = $cartCurrencies->first() ?? 'CDF';
        $hasMixedCurrency = $cartCurrencies->count() > 1;

        return view('livewire.sales.index', [
            'products' => $products,
            'productsById' => $productsById,
            'filteredProducts' => $filteredProducts,
            'favoriteProducts' => $favoriteProducts,
            'frequentProducts' => $frequentProducts,
            'totals' => $totals,
            'pendingSales' => $pendingSales,
            'changeDue' => $changeDue,
            'cartCurrency' => $cartCurrency,
            'hasMixedCurrency' => $hasMixedCurrency,
        ])->layout('layouts.app');
    }
}
