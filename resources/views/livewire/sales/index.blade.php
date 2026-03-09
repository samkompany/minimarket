<div
    class="space-y-8 sales-screen"
    x-data="{ searchCount: {{ $filteredProducts->count() }}, activeIndex: 0, screenMode: @entangle('screenMode').live, lastAddedId: null }"
    x-bind:class="`sales-screen-${screenMode}`"
    x-init="$nextTick(() => $refs.barcode?.focus())"
    x-on:keydown.window="
        if (($event.ctrlKey || $event.metaKey) && $event.key === 'Enter' && ! $event.shiftKey) { $event.preventDefault(); $wire.saveSale(); }
        if (($event.ctrlKey || $event.metaKey) && $event.key === 'Enter' && $event.shiftKey) { $event.preventDefault(); $wire.savePending(); }
        if (($event.ctrlKey || $event.metaKey) && ($event.key === 'i' || $event.key === 'I')) { $event.preventDefault(); $wire.addItem(); }
        if ($event.key === 'F2') { $event.preventDefault(); $refs.customer?.focus(); }
        if ($event.key === 'F4') { $event.preventDefault(); $refs.barcode?.focus(); }
        if (!['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && /^[a-zA-Z]$/.test($event.key)) {
            $refs.productSearch?.focus();
        }
    "
    x-on:notify.window="
        $dispatch('toast', { message: $event.detail.message, invoiceId: $event.detail.invoiceId });
        if ($event.detail.lastAddedId) {
            lastAddedId = $event.detail.lastAddedId;
            setTimeout(() => lastAddedId = null, 600);
        }
        if ($event.detail.invoiceId) {
            if (window.__receiptWindow && !window.__receiptWindow.closed) {
                window.__receiptWindow.location = `/invoices/${$event.detail.invoiceId}/receipt`;
                window.__receiptWindow.focus();
                window.__receiptWindow = null;
            } else {
                window.open(`/invoices/${$event.detail.invoiceId}/receipt`, '_blank');
            }
        }
    "
    x-on:focus-barcode.window="$refs.barcode?.focus()"
>
    <div
        x-data="{ open: false, message: '', invoiceId: null }"
        x-on:toast.window="
            message = $event.detail.message || '';
            invoiceId = $event.detail.invoiceId || null;
            open = true;
            setTimeout(() => open = false, 3000);
        "
        x-show="open"
        class="fixed right-6 top-6 z-50 w-72 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-lg"
        style="display: none;"
    >
        <div class="flex items-center justify-between gap-3">
            <span x-text="message"></span>
            <template x-if="invoiceId">
                <a :href="`/invoices/${invoiceId}/receipt`" class="text-emerald-800 underline">Ticket</a>
            </template>
        </div>
    </div>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
            <h2 class="app-title">Ventes</h2>
            <p class="app-subtitle">Gestion des ventes et emission de factures.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600">
                    <span class="uppercase tracking-wider">Mode ecran</span>
                    <select wire:model.live="screenMode" wire:change="setScreenMode($event.target.value)" class="border-0 bg-transparent p-0 text-xs font-semibold text-slate-700 focus:ring-0">
                        <option value="pos">POS</option>
                        <option value="tablet">Tablette</option>
                        <option value="pc">PC</option>
                        <option value="mobile">Mobile</option>
                    </select>
                </div>
                <a href="{{ route('sales.history') }}" wire:navigate class="app-btn-secondary">Historique</a>
                <a href="{{ route('dashboard') }}" wire:navigate class="app-btn-ghost">Dashboard</a>
            </div>
        </div>
    </x-slot>

    <div class="sales-shortcuts">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Raccourcis clavier</div>
            <div class="text-xs font-semibold text-slate-500">Entrer : Ajouter au panier</div>
        </div>
        <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Ctrl + Enter : Valider</span>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Ctrl + Shift + Enter : Attente</span>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1">F2 : Client</span>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1">F4 : Scan</span>
        </div>
    </div>

    <div class="mx-auto max-w-6xl space-y-8 sales-shell">
        <form wire:submit.prevent="saveSale" class="sales-grid grid gap-6">
            <div class="sales-cart space-y-6 lg:order-1">
                <div class="app-card sales-card">
                    <div class="app-card-header">
                        <div>
                            <h3 class="app-card-title">Catalogue</h3>
                            <p class="app-card-subtitle">Recherchez et ajoutez rapidement.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sales-cart-actions">
                            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-500 sales-scan">
                                <span class="uppercase tracking-wider">Scan</span>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="barcodeInput"
                                    x-ref="barcode"
                                    placeholder="Code-barres"
                                    x-on:keydown.enter.prevent
                                    class="w-28 border-0 bg-transparent p-0 text-xs text-slate-700 focus:ring-0"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="app-card-body space-y-4">
                        @if ($favoriteProducts->isNotEmpty() || $frequentProducts->isNotEmpty())
                            <div>
                                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Raccourcis</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($favoriteProducts as $product)
                                        <div class="flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                            <button type="button" wire:click="selectProduct({{ $product->id }})" class="px-1">
                                                ★ {{ $product->name }}
                                            </button>
                                            <button type="button" data-quick-add wire:click="selectProduct({{ $product->id }}); $wire.addToCart();" class="rounded-full border border-amber-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                +
                                            </button>
                                        </div>
                                    @endforeach
                                    @foreach ($frequentProducts as $product)
                                        <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600">
                                            <button type="button" wire:click="selectProduct({{ $product->id }})" class="px-1">
                                                {{ $product->name }}
                                            </button>
                                            <button type="button" data-quick-add wire:click="selectProduct({{ $product->id }}); $wire.addToCart();" class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                                +
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="grid gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 sales-search">
                            <div>
                                <label class="app-label">Recherche rapide</label>
                                <input type="text"
                                    wire:model.live.debounce.300ms="productSearch"
                                    placeholder="Nom du produit"
                                    class="app-input"
                                    x-ref="productSearch"
                                    x-on:keydown.enter.prevent
                                    x-on:keydown.down.prevent="if (searchCount > 0) { activeIndex = Math.min(activeIndex + 1, searchCount - 1); }"
                                    x-on:keydown.up.prevent="if (searchCount > 0) { activeIndex = Math.max(activeIndex - 1, 0); }"
                                    x-on:keydown.enter.prevent="if (searchCount > 0) { document.getElementById('search-add-' + activeIndex)?.click(); }"
                                />
                            </div>
                            @if ($productSearch !== '')
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @forelse ($filteredProducts as $product)
                                        @php
                                            $displayPrice = $product->promo_price ?? $product->sale_price;
                                            $displayCurrency = $product->currency ?? 'CDF';
                                        @endphp
                                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                                            :class="activeIndex === {{ $loop->index }} ? 'ring-2 ring-teal-200' : ''">
                                            <button type="button"
                                                id="search-item-{{ $loop->index }}"
                                                wire:click="selectProduct({{ $product->id }})"
                                                class="flex-1 text-left font-semibold text-slate-700"
                                                :class="activeIndex === {{ $loop->index }} ? 'text-teal-700' : ''">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="truncate">{{ $product->name }}</div>
                                                        <div class="mt-0.5 text-[11px] text-slate-500">
                                                            {{ number_format((float) $displayPrice, 2) }} {{ $displayCurrency }}
                                                            · Stock {{ $product->stock?->quantity ?? 0 }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                            <button type="button"
                                                id="search-add-{{ $loop->index }}"
                                                wire:click="selectProduct({{ $product->id }}); $wire.addToCart();"
                                                class="app-btn-secondary px-3 py-1 text-xs font-semibold">
                                                +
                                            </button>
                                        </div>
                                    @empty
                                        <div class="text-sm text-slate-500">Aucun produit trouve.</div>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card sales-card">
                    <div class="app-card-header">
                        <div>
                            <h3 class="app-card-title">Ajout au panier</h3>
                            <p class="app-card-subtitle">Selectionnez l'article et ajoutez-le au panier.</p>
                        </div>
                        @error('selectedProductId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="app-card-body space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="app-label">Produit</label>
                                    <select wire:model.live="selectedProductId" class="app-select" x-on:keydown.enter.prevent="$wire.addToCart()">
                                        <option value="">Selectionner</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name }} · Stock {{ $product->stock?->quantity ?? 0 }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            <div>
                                <label class="app-label">Quantite</label>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="decrementSelectedQuantity" class="h-9 w-9 rounded-xl border border-slate-200 bg-white text-lg font-semibold text-slate-600 hover:bg-slate-50">
                                        -
                                    </button>
                                    <input type="number" min="1" wire:model.live="selectedQuantity" class="app-input" x-on:keydown.enter.prevent="$wire.addToCart()" />
                                    <button type="button" wire:click="incrementSelectedQuantity" class="h-9 w-9 rounded-xl border border-slate-200 bg-white text-lg font-semibold text-slate-600 hover:bg-slate-50">
                                        +
                                    </button>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="app-label">Prix unitaire</label>
                                    <span class="text-xs font-semibold text-emerald-600">Auto</span>
                                </div>
                                <input type="number" step="0.01" min="0" wire:model.live="selectedUnitPrice" class="app-input bg-slate-100" readonly />
                                <div class="mt-1 text-xs text-slate-500">
                                    Devise: {{ $selectedProductId ? ($productsById->get($selectedProductId)?->currency ?? 'CDF') : 'CDF' }}
                                </div>
                            </div>

                            <div>
                                <label class="app-label">Remise %</label>
                                <input type="number" min="0" max="100" step="0.01" wire:model.live="selectedDiscountRate" class="app-input" x-on:keydown.enter.prevent="$wire.addToCart()" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sales-add-actions">
                            <button type="button" wire:click="addToCart" class="app-btn-primary">
                                Ajouter au panier
                            </button>
                            <button type="button" wire:click="resetSelectedItemForm" class="app-btn-ghost">
                                Reinitialiser
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <div class="sales-summary space-y-6 lg:order-2">
                <div class="sales-mini-total hidden lg:flex">
                    <div class="min-w-0">
                        <div class="text-[11px] uppercase tracking-wider text-slate-500">Total panier</div>
                        <div class="text-lg font-semibold text-slate-900">
                            {{ number_format($totals['total'], 2) }} {{ $cartCurrency }}
                        </div>
                        @if ($hasMixedCurrency)
                            <div class="text-[11px] font-semibold text-rose-600">Devise unique</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="app-btn-primary" x-on:click="window.__receiptWindow = window.open('about:blank', '_blank');" @disabled($hasMixedCurrency)>
                            Valider
                        </button>
                        <button type="button" wire:click="savePending" class="app-btn-secondary">
                            Attente
                        </button>
                    </div>
                </div>

                <div class="app-card sales-card">
                    <div class="app-card-header">
                        <div>
                            <h3 class="app-card-title">Panier</h3>
                            <p class="app-card-subtitle">Recap des articles choisis.</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1">
                                {{ count($items) }} article(s)
                            </span>
                        </div>
                        @error('items') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="app-card-body">
                        @if (empty($items))
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                Aucun article dans le panier pour le moment.
                            </div>
                        @else
                            <div class="space-y-3 sales-lines">
                                @foreach ($items as $index => $item)
                                    @php
                                        $product = $productsById->get($item['product_id']);
                                        $lineBase = ((int) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0));
                                        $lineDiscount = $lineBase * (((float) ($item['discount_rate'] ?? 0)) / 100);
                                        $lineTotal = $lineBase - $lineDiscount;
                                    @endphp
                                    <div class="sales-line group flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition"
                                        :class="lastAddedId === {{ $item['product_id'] ?? 0 }} ? 'ring-2 ring-emerald-300 shadow-emerald-200/50' : ''">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-900">
                                                <span>{{ $product?->name ?? 'Produit' }}</span>
                                                <span class="text-xs font-semibold text-emerald-700">
                                                    {{ number_format($lineTotal, 2) }} {{ $product?->currency ?? 'CDF' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 hidden text-xs text-slate-500 group-hover:block">
                                                PU {{ number_format((float) ($item['unit_price'] ?? 0), 2) }} {{ $product?->currency ?? 'CDF' }}
                                                · Remise {{ number_format((float) ($item['discount_rate'] ?? 0), 2) }}%
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="decrementQuantity({{ $index }})" class="h-9 w-9 rounded-xl border border-slate-200 bg-white text-lg font-semibold text-slate-600 hover:bg-slate-50">
                                                -
                                            </button>
                                            <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity" class="app-input w-20" />
                                            <button type="button" wire:click="incrementQuantity({{ $index }})" class="h-9 w-9 rounded-xl border border-slate-200 bg-white text-lg font-semibold text-slate-600 hover:bg-slate-50">
                                                +
                                            </button>
                                        </div>

                                        <div class="text-right">
                                            <button type="button" wire:click="removeItem({{ $index }})" class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                                Retirer
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-400">Total provisoire</div>
                                <div class="text-xl font-semibold text-slate-900">{{ number_format($totals['total'], 2) }} {{ $cartCurrency }}</div>
                                <div class="mt-1 text-xs text-slate-500">Ajoutez tous les articles, puis validez le panier.</div>
                                @if ($hasMixedCurrency)
                                    <div class="mt-1 text-xs font-semibold text-rose-600">Panier mixte: utilisez une seule devise.</div>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="app-btn-primary" x-on:click="window.__receiptWindow = window.open('about:blank', '_blank');" @disabled($hasMixedCurrency)>
                                    Valider le panier
                                </button>
                                <button type="button" wire:click="resetForm" class="app-btn-ghost">
                                    Reinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="sales-mobile-bar lg:hidden">
                <div>
                    <div class="text-[11px] uppercase tracking-wider text-slate-400">Total</div>
                    <div class="text-lg font-semibold text-slate-900">{{ number_format($totals['total'], 2) }} {{ $cartCurrency }}</div>
                    @if ($hasMixedCurrency)
                        <div class="mt-1 text-[11px] font-semibold text-rose-600">Devise unique</div>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="app-btn-primary" x-on:click="window.__receiptWindow = window.open('about:blank', '_blank');" @disabled($hasMixedCurrency)>
                        Valider
                    </button>
                    <button type="button" wire:click="savePending" class="app-btn-secondary">
                        Attente
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
