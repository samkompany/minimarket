<?php

namespace App\Livewire;

use App\Models\ExpensePayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockOut;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $last30Days = Carbon::now()->subDays(30);
        $last90Days = Carbon::now()->subDays(90);
        $slowStockCutoff = Carbon::now()->subDays(30);
        $inactiveSupplierCutoff = Carbon::now()->subDays(60);

        $salesTodayQuery = Sale::query()
            ->where('status', 'paid')
            ->whereDate('sold_at', $today);
        if (! $isAdmin) {
            $salesTodayQuery->where('user_id', auth()->id());
        }

        $salesTodayCount = $salesTodayQuery->count();

        $revenueByCurrency = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'paid')
            ->whereDate('sales.sold_at', '>=', $monthStart)
            ->when(! $isAdmin, fn ($q) => $q->where('sales.user_id', auth()->id()))
            ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
            ->selectRaw('SUM(sale_items.line_total) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $expenseByCurrency = ExpensePayment::query()
            ->join('expenses', 'expenses.id', '=', 'expense_payments.expense_id')
            ->whereDate('expense_payments.paid_at', '>=', $monthStart)
            ->selectRaw("COALESCE(expenses.currency, 'CDF') as currency")
            ->selectRaw('SUM(expense_payments.amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $netByCurrency = [];
        foreach ($revenueByCurrency as $row) {
            $netByCurrency[$row->currency] = [
                'currency' => $row->currency,
                'income' => (float) $row->total,
                'expense' => 0,
                'net' => (float) $row->total,
            ];
        }

        foreach ($expenseByCurrency as $row) {
            $existing = $netByCurrency[$row->currency] ?? [
                'currency' => $row->currency,
                'income' => 0,
                'expense' => 0,
                'net' => 0,
            ];
            $existing['expense'] = (float) $row->total;
            $existing['net'] = round($existing['income'] - $existing['expense'], 2);
            $netByCurrency[$row->currency] = $existing;
        }

        $lowStockCount = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.archived_at')
            ->whereRaw('COALESCE(stocks.quantity, 0) <= products.min_stock')
            ->count();

        $stockCount = Stock::query()->sum('quantity');
        $suppliersCount = Supplier::query()->count();

        $recentSales = Sale::query()
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', auth()->id()))
            ->orderByDesc('sold_at')
            ->limit(5)
            ->get();

        $recentExpenses = DB::table('expenses')
            ->orderByDesc('incurred_at')
            ->limit(5)
            ->get();

        $salesByUser = collect();
        $marginByCurrency = collect();
        $negativeMarginProducts = collect();
        $outOfStockCount = 0;
        $outOfStockProducts = collect();
        $stockValueByCurrency = collect();
        $slowMovingCount = 0;
        $slowMovingProducts = collect();
        $revenueLast30 = collect();
        $revenueLast90 = collect();
        $expenseLast30 = collect();
        $expenseLast90 = collect();
        $unpaidSalesCount = 0;
        $unpaidSalesByCurrency = collect();
        $expenseByCategory = collect();
        $largeStockOuts = collect();
        $missingSalePriceCount = 0;
        $missingCostPriceCount = 0;
        $missingMinStockCount = 0;
        $inactiveSuppliersCount = 0;
        $inactiveSuppliers = collect();
        $usersCount = 0;
        $suspendedUsersCount = 0;
        $unverifiedUsersCount = 0;
        $recentUsers = collect();

        if ($isAdmin) {
            $salesByUser = Sale::query()
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->where('sales.status', 'paid')
                ->whereDate('sales.sold_at', '>=', $monthStart)
                ->groupBy('sales.user_id', 'users.name')
                ->selectRaw('users.name as user_name')
                ->selectRaw('COUNT(*) as sales_count')
                ->selectRaw('SUM(sales.total_amount) as total_amount')
                ->orderByDesc('sales_count')
                ->limit(5)
                ->get();

            $marginByCurrency = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', 'paid')
                ->whereDate('sales.sold_at', '>=', $monthStart)
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('SUM(sale_items.line_total - (sale_items.quantity * COALESCE(products.cost_price, 0))) as margin')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $negativeMarginProducts = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', 'paid')
                ->whereDate('sales.sold_at', '>=', $monthStart)
                ->groupBy('products.id', 'products.name', 'products.currency')
                ->selectRaw('products.name')
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('SUM(sale_items.line_total) as revenue')
                ->selectRaw('SUM(sale_items.quantity * COALESCE(products.cost_price, 0)) as cost')
                ->selectRaw('SUM(sale_items.line_total - (sale_items.quantity * COALESCE(products.cost_price, 0))) as margin')
                ->havingRaw('SUM(sale_items.line_total - (sale_items.quantity * COALESCE(products.cost_price, 0))) < 0')
                ->orderBy('margin')
                ->limit(5)
                ->get();

            $outOfStockCount = Product::query()
                ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
                ->whereNull('products.archived_at')
                ->whereRaw('COALESCE(stocks.quantity, 0) <= 0')
                ->count();

            $outOfStockProducts = Product::query()
                ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
                ->whereNull('products.archived_at')
                ->whereRaw('COALESCE(stocks.quantity, 0) <= 0')
                ->select('products.name', 'products.currency')
                ->selectRaw('COALESCE(stocks.quantity, 0) as quantity')
                ->orderBy('products.name')
                ->limit(5)
                ->get();

            $stockValueByCurrency = Stock::query()
                ->join('products', 'products.id', '=', 'stocks.product_id')
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('SUM(stocks.quantity * COALESCE(products.cost_price, 0)) as total')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $slowMovingBaseQuery = DB::table('products')
                ->leftJoin('sale_items', 'sale_items.product_id', '=', 'products.id')
                ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereNull('products.archived_at')
                ->groupBy('products.id', 'products.name', 'products.currency')
                ->havingRaw('(MAX(sales.sold_at) IS NULL OR MAX(sales.sold_at) < ?)', [$slowStockCutoff]);

            $slowMovingCount = (clone $slowMovingBaseQuery)
                ->select('products.id')
                ->get()
                ->count();

            $slowMovingProducts = (clone $slowMovingBaseQuery)
                ->selectRaw('products.name')
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('MAX(sales.sold_at) as last_sold_at')
                ->orderByRaw('MAX(sales.sold_at) IS NULL desc')
                ->orderByRaw('MAX(sales.sold_at) asc')
                ->limit(5)
                ->get();

            $revenueLast30 = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', 'paid')
                ->whereDate('sales.sold_at', '>=', $last30Days)
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('SUM(sale_items.line_total) as total')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $revenueLast90 = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', 'paid')
                ->whereDate('sales.sold_at', '>=', $last90Days)
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('SUM(sale_items.line_total) as total')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $expenseLast30 = ExpensePayment::query()
                ->join('expenses', 'expenses.id', '=', 'expense_payments.expense_id')
                ->whereDate('expense_payments.paid_at', '>=', $last30Days)
                ->selectRaw("COALESCE(expenses.currency, 'CDF') as currency")
                ->selectRaw('SUM(expense_payments.amount) as total')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $expenseLast90 = ExpensePayment::query()
                ->join('expenses', 'expenses.id', '=', 'expense_payments.expense_id')
                ->whereDate('expense_payments.paid_at', '>=', $last90Days)
                ->selectRaw("COALESCE(expenses.currency, 'CDF') as currency")
                ->selectRaw('SUM(expense_payments.amount) as total')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $unpaidSalesCount = Sale::query()
                ->where('status', '!=', 'paid')
                ->count();

            $unpaidSalesByCurrency = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.status', '!=', 'paid')
                ->selectRaw("COALESCE(products.currency, 'CDF') as currency")
                ->selectRaw('SUM(sale_items.line_total) as total')
                ->groupBy('currency')
                ->orderBy('currency')
                ->get();

            $expenseByCategory = DB::table('expenses')
                ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
                ->whereDate('expenses.incurred_at', '>=', $last30Days)
                ->groupBy('expense_categories.name', 'expenses.currency')
                ->selectRaw('expense_categories.name as category')
                ->selectRaw("COALESCE(expenses.currency, 'CDF') as currency")
                ->selectRaw('SUM(expenses.amount) as total')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $largeStockOuts = StockOut::query()
                ->with('user')
                ->whereDate('occurred_at', '>=', $last30Days)
                ->orderByDesc('total_quantity')
                ->limit(5)
                ->get();

            $missingSalePriceCount = Product::query()
                ->whereNull('archived_at')
                ->where(function ($query) {
                    $query->whereNull('sale_price')->orWhere('sale_price', '<=', 0);
                })
                ->count();

            $missingCostPriceCount = Product::query()
                ->whereNull('archived_at')
                ->where(function ($query) {
                    $query->whereNull('cost_price')->orWhere('cost_price', '<=', 0);
                })
                ->count();

            $missingMinStockCount = Product::query()
                ->whereNull('archived_at')
                ->whereNull('min_stock')
                ->count();

            $inactiveSuppliersBaseQuery = Supplier::query()
                ->leftJoin('purchases', 'purchases.supplier_id', '=', 'suppliers.id')
                ->groupBy('suppliers.id', 'suppliers.name')
                ->havingRaw('(MAX(purchases.purchased_at) IS NULL OR MAX(purchases.purchased_at) < ?)', [$inactiveSupplierCutoff]);

            $inactiveSuppliersCount = (clone $inactiveSuppliersBaseQuery)
                ->select('suppliers.id')
                ->get()
                ->count();

            $inactiveSuppliers = (clone $inactiveSuppliersBaseQuery)
                ->selectRaw('suppliers.name')
                ->selectRaw('MAX(purchases.purchased_at) as last_purchase_at')
                ->orderByRaw('MAX(purchases.purchased_at) IS NULL desc')
                ->orderByRaw('MAX(purchases.purchased_at) asc')
                ->limit(5)
                ->get();

            $usersCount = User::query()->count();
            $suspendedUsersCount = User::query()->whereNotNull('suspended_at')->count();
            $unverifiedUsersCount = User::query()->whereNull('email_verified_at')->count();
            $recentUsers = User::query()->orderByDesc('created_at')->limit(5)->get();
        }

        return view('livewire.dashboard', [
            'salesTodayCount' => $salesTodayCount,
            'revenueByCurrency' => $revenueByCurrency,
            'netByCurrency' => collect($netByCurrency)->values(),
            'lowStockCount' => $lowStockCount,
            'stockCount' => $stockCount,
            'suppliersCount' => $suppliersCount,
            'recentSales' => $recentSales,
            'recentExpenses' => $recentExpenses,
            'isAdmin' => $isAdmin,
            'salesByUser' => $salesByUser,
            'marginByCurrency' => $marginByCurrency,
            'negativeMarginProducts' => $negativeMarginProducts,
            'outOfStockCount' => $outOfStockCount,
            'outOfStockProducts' => $outOfStockProducts,
            'stockValueByCurrency' => $stockValueByCurrency,
            'slowMovingCount' => $slowMovingCount,
            'slowMovingProducts' => $slowMovingProducts,
            'revenueLast30' => $revenueLast30,
            'revenueLast90' => $revenueLast90,
            'expenseLast30' => $expenseLast30,
            'expenseLast90' => $expenseLast90,
            'unpaidSalesCount' => $unpaidSalesCount,
            'unpaidSalesByCurrency' => $unpaidSalesByCurrency,
            'expenseByCategory' => $expenseByCategory,
            'largeStockOuts' => $largeStockOuts,
            'missingSalePriceCount' => $missingSalePriceCount,
            'missingCostPriceCount' => $missingCostPriceCount,
            'missingMinStockCount' => $missingMinStockCount,
            'inactiveSuppliersCount' => $inactiveSuppliersCount,
            'inactiveSuppliers' => $inactiveSuppliers,
            'usersCount' => $usersCount,
            'suspendedUsersCount' => $suspendedUsersCount,
            'unverifiedUsersCount' => $unverifiedUsersCount,
            'recentUsers' => $recentUsers,
        ])->layout('layouts.app');
    }
}
