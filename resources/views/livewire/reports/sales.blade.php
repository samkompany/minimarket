<div class="space-y-8">
    <x-slot name="header">
        <div>
            <h2 class="app-title">Rapport ventes</h2>
            <p class="app-subtitle">Synthese des ventes avec benefice.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-8">
        <div class="app-card">
            <div class="app-card-header">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="app-card-title">Filtres</h3>
                        <p class="app-card-subtitle">Choisissez la periode.</p>
                    </div>
                    <button type="button" wire:click="exportSales" wire:loading.attr="disabled" wire:target="exportSales" class="app-btn-secondary">
                        Exporter Excel
                    </button>
                </div>
            </div>
            <div class="app-card-body space-y-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-full sm:w-auto">
                        <label class="app-label">Du</label>
                        <input type="date" wire:model.live="startDate" class="app-input sm:w-44" />
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="app-label">Au</label>
                        <input type="date" wire:model.live="endDate" class="app-input sm:w-44" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-400">Ventes</div>
                        <div class="text-2xl font-semibold text-slate-900">{{ number_format($salesCount) }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ number_format($itemsCount) }} articles</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-400">Depenses</div>
                        @if ($summaryByCurrency->isEmpty() && $expenseByCurrency->isEmpty())
                            <div class="text-2xl font-semibold text-slate-900">0</div>
                        @elseif ($expenseByCurrency->count() === 1)
                            @php
                                $currency = $expenseByCurrency->keys()->first();
                                $expense = (float) ($expenseByCurrency[$currency] ?? 0);
                            @endphp
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ number_format($expense, 2) }} {{ $currency }}
                            </div>
                        @else
                            <div class="mt-2 space-y-1 text-sm">
                                @foreach ($expenseByCurrency as $currency => $expense)
                                    <div class="flex items-center justify-between text-slate-700">
                                        <span>{{ $currency }}</span>
                                        <span class="font-semibold">{{ number_format((float) $expense, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-400">Total global</div>
                        @if ($summaryByCurrency->isEmpty())
                            <div class="text-2xl font-semibold text-slate-900">0</div>
                        @elseif ($summaryByCurrency->count() === 1)
                            @php
                                $summary = $summaryByCurrency->first();
                                $expense = (float) ($expenseByCurrency[$summary->currency] ?? 0);
                                $balance = (float) $summary->revenue - $expense;
                            @endphp
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ number_format((float) $summary->revenue, 2) }} {{ $summary->currency }}
                            </div>
                            <div class="mt-2 text-xs text-slate-500">Depense: {{ number_format($expense, 2) }}</div>
                            <div class="text-xs font-semibold text-emerald-600">Solde: {{ number_format($balance, 2) }}</div>
                        @else
                            <div class="mt-2 space-y-1 text-xs">
                                @foreach ($summaryByCurrency as $summary)
                                    @php
                                        $expense = (float) ($expenseByCurrency[$summary->currency] ?? 0);
                                        $balance = (float) $summary->revenue - $expense;
                                    @endphp
                                    <div class="space-y-0.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700">
                                        <div class="flex items-center justify-between text-sm font-semibold">
                                            <span>{{ $summary->currency }}</span>
                                            <span>{{ number_format((float) $summary->revenue, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs text-slate-500">
                                            <span>Depense</span>
                                            <span>{{ number_format($expense, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs font-semibold text-emerald-600">
                                            <span>Solde</span>
                                            <span>{{ number_format($balance, 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @forelse ($summaryByCurrency as $summary)
                @php
                    $expense = (float) ($expenseByCurrency[$summary->currency] ?? 0);
                    $balance = (float) $summary->revenue - $expense;
                @endphp
                <div class="app-kpi">
                    <div class="app-kpi-label">Chiffre d'affaires ({{ $summary->currency }})</div>
                    <div class="app-kpi-value">{{ number_format((float) $summary->revenue, 2) }}</div>
                    <div class="mt-3 text-xs text-slate-500">Cout: {{ number_format((float) $summary->cost, 2) }}</div>
                    <div class="mt-1 text-sm font-semibold text-emerald-600">
                        Benefice: {{ number_format((float) $summary->profit, 2) }}
                    </div>
                    <div class="mt-3 text-xs text-slate-500">Depense: {{ number_format($expense, 2) }}</div>
                    <div class="mt-1 text-sm font-semibold text-emerald-700">
                        Solde: {{ number_format($balance, 2) }}
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center text-sm text-slate-500 md:col-span-3">
                    Aucun resultat pour cette periode.
                </div>
            @endforelse
        </div>

        <div class="app-card">
            <div class="app-card-header">
                <div>
                    <h3 class="app-card-title">Details des ventes</h3>
                    <p class="app-card-subtitle">Liste des articles vendus avec quantite, prix unitaire et prix achat.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Produit</th>
                            <th>Client</th>
                            <th>Qte</th>
                            <th>PU</th>
                            <th>PA</th>
                            <th>Total</th>
                            <th>Vendeur</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($saleItems as $item)
                            @php
                                $currency = $item->product?->currency ?? 'CDF';
                            @endphp
                            <tr wire:key="sale-item-{{ $item->id }}">
                                <td>{{ $item->sale?->sold_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="font-semibold text-slate-900">{{ $item->sale?->reference ?? '—' }}</td>
                                <td>{{ $item->product?->name ?? '—' }}</td>
                                <td>{{ $item->sale?->customer_name ?? 'Comptoir' }}</td>
                                <td>{{ number_format((float) $item->quantity) }}</td>
                                <td>{{ number_format((float) $item->unit_price, 2) }} {{ $currency }}</td>
                                <td>
                                    @if ($item->product?->cost_price !== null)
                                        {{ number_format((float) $item->product->cost_price, 2) }} {{ $currency }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="font-semibold text-slate-900">{{ number_format((float) $item->line_total, 2) }} {{ $currency }}</td>
                                <td>{{ $item->sale?->user?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Aucune ligne de vente pour cette periode.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4">
                {{ $saleItems->links() }}
            </div>
        </div>
    </div>
</div>
