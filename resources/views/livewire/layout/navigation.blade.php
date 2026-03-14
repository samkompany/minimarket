<?php

use App\Livewire\Actions\Logout;
use App\Models\Product;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $user = auth()->user();
    $role = $user?->role ?? 'vendeur';
    $appName = \App\Models\AppSetting::get('app_name', config('app.name', 'miniMaket'));
    $lowStockCount = 0;
    if ($role !== 'vendeur_simple') {
        $lowStockCount = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.archived_at')
            ->whereRaw('COALESCE(stocks.quantity, 0) <= products.min_stock')
            ->count();
    }

    $navSections = [
        [
            'title' => 'Operations',
            'items' => [
                ['label' => 'Dashboard',  'route' => 'dashboard',     'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                ['label' => 'Ventes',     'route' => 'sales.index',   'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z'],
                ['label' => 'Historique', 'route' => 'sales.history', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ],
        ],
    ];

    if ($role !== 'vendeur_simple') {
        $navSections[] = [
            'title' => 'Stock & Achats',
            'items' => [
                ['label' => 'Stock',           'route' => 'stocks.index',      'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
                ['label' => 'Alertes stock',   'route' => 'stocks.alerts',     'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0', 'badge' => $lowStockCount],
                ['label' => 'Sorties stock',   'route' => 'stock-outs.index',  'icon' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3'],
                ['label' => 'Produits',        'route' => 'products.index',    'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z'],
                ['label' => 'Categories',      'route' => 'categories.index',  'icon' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'],
                ['label' => 'Fournisseurs',    'route' => 'suppliers.index',   'icon' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'],
                ['label' => 'Achats',          'route' => 'purchases.index',   'icon' => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
            ],
        ];

        $navSections[] = [
            'title' => 'Depenses',
            'items' => [
                ['label' => 'Categories depenses', 'route' => 'expense-categories.index', 'icon' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z'],
                ['label' => 'Depenses',             'route' => 'expenses.index',           'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
            ],
        ];

        $navSections[] = [
            'title' => 'Rapports',
            'items' => [
                ['label' => 'Rapports ventes', 'route' => 'reports.sales',    'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                ['label' => 'Solde caisse',    'route' => 'reports.cashflow', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
            ],
        ];
    }

    if ($role === 'admin') {
        $navSections[] = [
            'title' => 'Administration',
            'items' => [
                ['label' => 'Utilisateurs', 'route' => 'users.index', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ],
        ];
    }

    $navItems = collect($navSections)->pluck('items')->flatten(1)->all();
@endphp

<div
    x-data="{
        collapsed: localStorage.getItem('minimarket-sidebar') === '1',
        mobileOpen: false,
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('minimarket-sidebar', this.collapsed ? '1' : '0');
        }
    }"
    x-on:keydown.window="
        if ($event.key === 'Escape') mobileOpen = false;
        if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') {
            $event.preventDefault();
            document.querySelector('[data-global-search-input]')?.focus();
        }
    "
>
    {{-- ============================================================ --}}
    {{-- MOBILE TOPBAR                                                --}}
    {{-- ============================================================ --}}
    <div class="app-topbar">
        <div class="flex items-center gap-2">
            <button @click="mobileOpen = true" class="app-btn-ghost -ml-1 p-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <a href="{{ route('dashboard') }}" wire:navigate class="app-topbar-title">
                {{ $appName }}
            </a>
        </div>
        <div class="flex items-center gap-2">
            <div class="hidden sm:block">
                <livewire:global-search />
            </div>
            <a href="{{ route('profile') }}" wire:navigate class="app-btn-ghost">Profil</a>
            <button wire:click="logout" class="app-btn-ghost">Deconnexion</button>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MOBILE DRAWER — BACKDROP                                     --}}
    {{-- ============================================================ --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition-opacity duration-300 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-200 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileOpen = false"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        style="display:none;"
    ></div>

    {{-- ============================================================ --}}
    {{-- MOBILE DRAWER — PANEL                                        --}}
    {{-- ============================================================ --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition-transform duration-300 ease-out"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-200 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col overflow-y-auto border-r border-slate-200/70 bg-white shadow-2xl lg:hidden"
        style="display:none;"
    >
        {{-- Drawer header --}}
        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-4">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-9 w-9 fill-current text-teal-600" />
                <div>
                    <div class="text-base font-semibold text-slate-900">{{ $appName }}</div>
                    <div class="text-xs uppercase tracking-wider text-slate-500">Gestion magasin</div>
                </div>
            </div>
            <button @click="mobileOpen = false" class="app-btn-ghost p-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Drawer search --}}
        <div class="border-b border-slate-100 px-4 py-3">
            <livewire:global-search />
        </div>

        {{-- Drawer navigation --}}
        <nav class="flex flex-1 flex-col gap-5 overflow-y-auto px-4 py-5">
            @foreach ($navSections as $section)
                <div class="space-y-1">
                    <div class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                        {{ $section['title'] }}
                    </div>
                    @foreach ($section['items'] as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            @click="mobileOpen = false"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition
                                {{ $active
                                    ? 'bg-gradient-to-r from-teal-50 via-emerald-50 to-transparent text-teal-700 ring-1 ring-teal-100'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        >
                            <svg
                                class="h-5 w-5 flex-shrink-0 {{ $active ? 'text-teal-600' : 'text-slate-400' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            <span class="flex-1">{{ $item['label'] }}</span>
                            @if (!empty($item['badge']))
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Drawer user footer --}}
        <div class="border-t border-slate-200/70 px-4 py-4">
            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3 text-sm">
                <div class="min-w-0">
                    <div class="text-xs text-slate-500">Connecté</div>
                    <div class="truncate font-semibold text-slate-700">{{ auth()->user()->name ?? 'Utilisateur' }}</div>
                </div>
                <div class="flex flex-shrink-0 items-center gap-1">
                    <a href="{{ route('profile') }}" wire:navigate class="app-btn-ghost px-2.5 py-1.5 text-xs">Profil</a>
                    <button wire:click="logout" class="app-btn-ghost px-2.5 py-1.5 text-xs text-rose-600 hover:text-rose-700">Quitter</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- DESKTOP SIDEBAR                                              --}}
    {{-- ============================================================ --}}
    <aside
        class="app-sidebar"
        :class="{ 'sidebar-is-collapsed': collapsed }"
    >
        {{-- Sidebar header --}}
        <div
            class="app-sidebar-header transition-all duration-300"
            :class="collapsed ? 'flex-col gap-2 px-3 py-4 justify-center items-center' : ''"
        >
            <x-application-logo class="h-10 w-10 flex-shrink-0 fill-current text-teal-600" />

            <div
                class="min-w-0 flex-1 overflow-hidden"
                x-show="!collapsed"
                x-transition:enter="transition-opacity duration-150 delay-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <div class="app-sidebar-title truncate">{{ $appName }}</div>
                <div class="app-sidebar-subtitle">Gestion magasin</div>
            </div>

            {{-- Toggle button --}}
            <button
                @click="toggle()"
                class="sidebar-toggle-btn flex-shrink-0"
                :title="collapsed ? 'Agrandir le menu' : 'Réduire le menu'"
            >
                <svg
                    class="h-4 w-4 transition-transform duration-300"
                    :class="collapsed ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>
        </div>

        {{-- Search (hidden when collapsed) --}}
        <div
            class="overflow-hidden px-4 pt-4"
            x-show="!collapsed"
            x-transition:enter="transition-opacity duration-150 delay-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <livewire:global-search />
        </div>

        {{-- Navigation --}}
        <nav
            class="flex flex-1 flex-col overflow-x-hidden overflow-y-auto transition-all duration-300"
            :class="collapsed ? 'gap-2 px-2 py-4' : 'gap-6 px-4 py-6'"
        >
            @foreach ($navSections as $section)
                <div :class="collapsed ? 'space-y-1' : 'space-y-2'">

                    {{-- Section title (expanded) --}}
                    <div
                        class="app-sidebar-section-title overflow-hidden whitespace-nowrap"
                        x-show="!collapsed"
                        x-transition:enter="transition-opacity duration-150 delay-100"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition-opacity duration-75"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    >{{ $section['title'] }}</div>

                    {{-- Separator (collapsed) --}}
                    <div x-show="collapsed" class="my-1 border-t border-slate-200/60" style="display:none;"></div>

                    @foreach ($section['items'] as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            class="sidebar-link group relative flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition
                                {{ $active
                                    ? 'bg-gradient-to-r from-teal-50 via-emerald-50 to-transparent text-teal-700 shadow-sm ring-1 ring-teal-100'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                            :class="collapsed ? 'justify-center px-0' : ''"
                            title="{{ $item['label'] }}"
                        >
                            {{-- Icon --}}
                            <svg
                                class="h-5 w-5 flex-shrink-0 transition-colors
                                    {{ $active ? 'text-teal-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>

                            {{-- Label (expanded) --}}
                            <span
                                class="flex-1 overflow-hidden whitespace-nowrap"
                                x-show="!collapsed"
                                x-transition:enter="transition-opacity duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition-opacity duration-75"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                            >{{ $item['label'] }}</span>

                            {{-- Badge (expanded) --}}
                            @if (!empty($item['badge']))
                                <span x-show="!collapsed" class="app-nav-badge">{{ $item['badge'] }}</span>
                                {{-- Badge dot (collapsed) --}}
                                <span
                                    x-show="collapsed"
                                    style="display:none;"
                                    class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white"
                                >{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                            @endif

                            {{-- Tooltip (shown on hover when collapsed) --}}
                            <span class="sidebar-tooltip">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- User footer --}}
        <div class="border-t border-slate-200/70 p-3">
            {{-- Expanded --}}
            <div
                x-show="!collapsed"
                x-transition:enter="transition-opacity duration-150 delay-100"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-75"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3 text-sm"
            >
                <div class="min-w-0">
                    <div class="text-xs text-slate-500">Connecté</div>
                    <div class="truncate font-semibold text-slate-700">{{ auth()->user()->name ?? 'Utilisateur' }}</div>
                </div>
                <div class="flex flex-shrink-0 items-center gap-1">
                    <a href="{{ route('profile') }}" wire:navigate class="app-btn-ghost px-2.5 py-1.5 text-xs">Profil</a>
                    <button wire:click="logout" class="app-btn-ghost px-2.5 py-1.5 text-xs text-rose-600 hover:text-rose-700">Quitter</button>
                </div>
            </div>

            {{-- Collapsed: icon-only buttons --}}
            <div x-show="collapsed" style="display:none;" class="flex flex-col items-center gap-1">
                <a
                    href="{{ route('profile') }}"
                    wire:navigate
                    class="sidebar-link group relative flex w-full items-center justify-center rounded-xl py-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    title="Profil"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span class="sidebar-tooltip">Profil</span>
                </a>
                <button
                    wire:click="logout"
                    class="sidebar-link group relative flex w-full items-center justify-center rounded-xl py-2 text-rose-400 transition hover:bg-rose-50 hover:text-rose-600"
                    title="Déconnexion"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    <span class="sidebar-tooltip">Déconnexion</span>
                </button>
            </div>
        </div>
    </aside>
</div>
