<div class="space-y-8">
    <x-slot name="header">
        <div>
            <h2 class="app-title">Produits</h2>
            <p class="app-subtitle">Catalogue complet avec prix et stock initial.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-8">
        <div class="app-card">
            <div class="app-card-header">
                <div>
                    <h3 class="app-card-title">Import / Export CSV</h3>
                    <p class="app-card-subtitle">Fichier CSV compatible Excel.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="exportProducts" class="app-btn-secondary">Exporter CSV</button>
                    <button type="button" wire:click="downloadTemplate" class="app-btn-ghost text-teal-600 hover:text-teal-700">Modele CSV</button>
                </div>
            </div>

            <div class="app-card-body space-y-4">
                <form wire:submit.prevent="importProducts" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="app-label">Importer un CSV</label>
                        <input type="file" wire:model="importFile" accept=".csv,text/csv" class="app-input" />
                        @error('importFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="app-btn-primary">Importer</button>
                </form>

                @if ($importedCount > 0 || $skippedCount > 0)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        Import termine: {{ $importedCount }} lignes importees, {{ $skippedCount }} ignorees.
                    </div>
                @endif

                @if ($importErrors !== [])
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="space-y-1">
                            @foreach ($importErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @if (auth()->user()?->isAdmin())
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h3 class="app-card-title">Import Excel</h3>
                        <p class="app-card-subtitle">Mise a jour securisee via fichier XLS/XLSX.</p>
                    </div>
                </div>

                <div class="app-card-body space-y-4">
                    <form wire:submit.prevent="importProductsExcel" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="app-label">Importer un Excel</label>
                            <input type="file" wire:model="importExcelFile" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="app-input" />
                            @error('importExcelFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="importCreateMissing" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                            Creer les produits manquants
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="importMatchByName" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                            Associer par nom si SKU/Code-barres absent
                        </label>
                        <button type="submit" class="app-btn-primary">Importer Excel</button>
                    </form>

                    @if ($importedCount > 0 || $skippedCount > 0)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            Import termine: {{ $importedCount }} lignes importees, {{ $skippedCount }} ignorees.
                        </div>
                    @endif

                    @if ($importErrors !== [])
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <ul class="space-y-1">
                                @foreach ($importErrors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="text-xs text-slate-500">
                        Les produits absents du fichier Excel ne sont pas modifies.
                    </p>
                </div>
            </div>
        @endif

        <div class="app-card">
            <div class="app-card-header">
                <h3 class="app-card-title">
                    {{ $productId ? 'Modifier un produit' : 'Nouveau produit' }}
                </h3>
            </div>

            <div class="app-card-body">
                <form wire:submit.prevent="saveProduct" class="grid gap-4 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label class="app-label">Nom</label>
                    <input type="text" wire:model="name" class="app-input" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Categorie</label>
                    <select wire:model="categoryId" class="app-select">
                        <option value="">Sans categorie</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Unite</label>
                    <input type="text" wire:model="unit" placeholder="piece, kg, litre" class="app-input" />
                    @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">SKU</label>
                    <input type="text" wire:model="sku" class="app-input" />
                    @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Code-barres</label>
                    <input type="text" wire:model="barcode" class="app-input" />
                    @error('barcode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Prix d'achat</label>
                    <input type="number" step="0.01" wire:model="cost_price" class="app-input" />
                    @error('cost_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Prix de vente</label>
                    <input type="number" step="0.01" wire:model="sale_price" class="app-input" />
                    @error('sale_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Devise</label>
                    <select wire:model="currency" class="app-select">
                        <option value="CDF">CDF</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                    @error('currency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Stock initial</label>
                    <input type="number" min="0" wire:model="stock_quantity" class="app-input" />
                    @error('stock_quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Seuil alerte</label>
                    <input type="number" min="0" wire:model="min_stock" class="app-input" />
                    @error('min_stock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="app-label">Qté reappro</label>
                    <input type="number" min="0" wire:model="reorder_qty" class="app-input" />
                    @error('reorder_qty') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 lg:col-span-4">
                    <button type="submit" class="app-btn-primary">
                        {{ $productId ? 'Mettre a jour' : 'Ajouter' }}
                    </button>
                    @if ($productId)
                        <button type="button" wire:click="resetForm" class="app-btn-secondary">
                            Annuler
                        </button>
                    @endif
                </div>
            </form>
            </div>
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <div>
                    <h3 class="app-card-title">Liste des produits</h3>
                    <p class="app-card-subtitle">Recherchez rapidement un produit.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="showArchived" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                        Afficher archives
                    </label>
                    <input type="text" wire:model.debounce.300ms="search" placeholder="Rechercher..." class="app-input sm:max-w-xs" />
                </div>
            </div>

            @if ($deleteError !== '')
                <div class="mx-6 mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $deleteError }}
                </div>
            @endif

            <div class="px-6 pt-6">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products->take(6) as $product)
                        @php
                            $stockQty = $product->stock?->quantity ?? 0;
                            $minStock = $product->min_stock ?? 0;
                            $status = $stockQty <= $minStock ? 'alert' : ($stockQty <= ($minStock + 5) ? 'warn' : 'ok');
                        @endphp
                        <div class="rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $product->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $product->category?->name ?? 'Sans categorie' }}</div>
                                </div>
                                <span class="app-badge {{ $status === 'alert' ? 'bg-rose-100 text-rose-700' : ($status === 'warn' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    Stock {{ $stockQty }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                                <span>Seuil {{ $minStock }}</span>
                                <span>{{ number_format($product->sale_price ?? 0, 2) }} {{ $product->currency ?? 'CDF' }}</span>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" wire:click="editProduct({{ $product->id }})" class="app-btn-ghost text-teal-600 hover:text-teal-700">Modifier</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Categorie</th>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Seuil</th>
                            <th>Reappro</th>
                            <th>Prix vente</th>
                            <th>Devise</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($products as $product)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>{{ $product->sku ?? '—' }}</td>
                                <td>{{ $product->stock?->quantity ?? 0 }}</td>
                                <td>{{ $product->min_stock ?? 0 }}</td>
                                <td>{{ $product->reorder_qty ?? 0 }}</td>
                                <td>
                                    {{ $product->sale_price !== null ? number_format($product->sale_price, 2) : '—' }}
                                </td>
                                <td>{{ $product->currency ?? 'CDF' }}</td>
                                <td class="text-right">
                                    <button type="button" wire:click="editProduct({{ $product->id }})" class="app-btn-ghost text-teal-600 hover:text-teal-700">Modifier</button>
                                    <button type="button" onclick="return confirm('Archiver ce produit ?') || event.stopImmediatePropagation()" wire:click="deleteProduct({{ $product->id }})" class="app-btn-ghost text-rose-600 hover:text-rose-700">Archiver</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-500">Aucun produit trouve.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4">
                {{ $products->links() }}
            </div>
        </div>

        @if ($showArchived)
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h3 class="app-card-title">Produits archives</h3>
                        <p class="app-card-subtitle">Produits archives uniquement.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Categorie</th>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Seuil</th>
                            <th>Reappro</th>
                            <th>Prix vente</th>
                            <th>Devise</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($archivedProducts as $product)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>{{ $product->sku ?? '—' }}</td>
                                <td>{{ $product->stock?->quantity ?? 0 }}</td>
                                <td>{{ $product->min_stock ?? 0 }}</td>
                                <td>{{ $product->reorder_qty ?? 0 }}</td>
                                <td>
                                    {{ $product->sale_price !== null ? number_format($product->sale_price, 2) : '—' }}
                                </td>
                                <td>{{ $product->currency ?? 'CDF' }}</td>
                                <td class="text-right">
                                    <button type="button" wire:click="restoreProduct({{ $product->id }})" class="app-btn-ghost text-amber-600 hover:text-amber-700">Restaurer</button>
                                </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-500">Aucun produit archive.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4">
                    {{ $archivedProducts->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
