<?php

namespace App\Services;

use App\Models\{Sale, Product, Client, Payment, Expense, ProductBranch};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    public function getBranchStats(array $filters): array
    {
        $branchId = $filters['branch_id'];

        // Pasamos los filtros a los métodos para que las fechas afecten a todo
        $salesInfo = $this->getSalesInfoboxes($branchId, $filters);
        $expenseInfo = $this->getExpenseInfoboxes($branchId, $filters);

        return [
            'infoboxes'    => $salesInfo,
            'expenseBoxes' => $expenseInfo,
            'resultBoxes'  => $this->calculateResultBoxes($salesInfo, $expenseInfo),
            'products'     => $this->getTopProducts($filters),
            'clients'      => $this->getTopClients($filters), // Ahora con filtros
            'chartData'    => $this->getMonthlyChartData($branchId),
            'stockReport'  => $this->getStockReport($branchId),
        ];
    }

    private function getSalesInfoboxes(int $branchId, array $filters): array
    {
        $stats = config('analytics.infoboxes');

        // Si hay rango de fechas, lo usamos. Si no, usamos los periodos por defecto.
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);

        $periods = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'month' => $hasRange ? [$filters['start_date'], $filters['end_date']] : [now()->startOfMonth(), now()->endOfMonth()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
        ];

        foreach ($periods as $period => $dates) {
            $key = "sales_$period";

            $stats[$key]['number'] = Sale::forBranch($branchId)
                ->whereBetween('created_at', $dates)
                ->count();

            $stats[$key]['secondaryNumber'] = Payment::where('paymentable_type', Sale::class)
                ->whereBetween('created_at', $dates)
                ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
                ->sum('amount');

            $stats[$key]['secondarySuffix'] = '$';
        }

        return $stats;
    }

    private function getExpenseInfoboxes(int $branchId, array $filters): array
    {
        $boxes = config('analytics.expense_infoboxes');
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);

        $startDate = $hasRange ? $filters['start_date'] : now()->startOfMonth();
        $endDate = $hasRange ? $filters['end_date'] : now()->endOfMonth();

        $boxes['expenses_today']['number'] = Expense::forBranch($branchId)
            ->whereDate('created_at', today())->sum('amount');

        $boxes['expenses_month']['number'] = Expense::forBranch($branchId)
            ->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        $boxes['expenses_year']['number'] = Expense::forBranch($branchId)
            ->whereYear('created_at', now()->year)->sum('amount');

        return $boxes;
    }

    private function calculateResultBoxes(array $salesInfo, array $expenseInfo): array
    {
        $results = config('analytics.result_infoboxes');
        $results['net_month']['number'] = $salesInfo['sales_month']['secondaryNumber'] - $expenseInfo['expenses_month']['number'];
        $results['net_year']['number']  = $salesInfo['sales_year']['secondaryNumber'] - $expenseInfo['expenses_year']['number'];

        foreach (['net_month', 'net_year'] as $key) {
            $results[$key]['color'] = $results[$key]['number'] >= 0 ? 'success' : 'danger';
        }
        return $results;
    }

    private function getTopProducts(array $filters, int $limit = 5)
    {
        $query = Product::select('products.name')
            ->selectRaw('SUM(sale_items.quantity) as units')
            ->selectRaw('SUM(sale_items.subtotal) as total')
            ->join('sale_items', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->where('sales.branch_id', $filters['branch_id']);

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('sales.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->groupBy('products.id', 'products.name')->orderByDesc('units')->limit($limit)->get();
    }

    private function getTopClients(array $filters, int $limit = 5)
    {
        $query = Client::select('clients.full_name as name')
            ->selectRaw('COUNT(sales.id) as orders')
            ->selectRaw('SUM(sales.total_amount) as total')
            ->join('sales', function ($join) use ($filters) {
                $join->on('sales.customer_id', '=', 'clients.id')
                    ->where('sales.customer_type', Client::class)
                    ->where('sales.branch_id', $filters['branch_id']);
            })
            ->whereNull('sales.deleted_at');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('sales.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->groupBy('clients.id', 'clients.full_name')->orderByDesc('total')->limit($limit)->get();
    }

    private function getStockReport(int $branchId)
    {
        return ProductBranch::with('product')
            ->where('branch_id', $branchId)
            ->get()
            ->map(fn($pb) => [
                'name'      => $pb->product->name,
                'stock'     => $pb->stock,
                'is_low'    => $pb->stock <= $pb->low_stock_threshold,
                'threshold' => $pb->low_stock_threshold
            ]);
    }

    private function getMonthlyChartData(int $branchId): array
    {
        $monthlySales = Sale::forBranch($branchId)
            ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')->orderBy('month')->get();

        return [
            'months'  => $monthlySales->pluck('month')->map(fn($m) => Carbon::create()->month($m)->format('M')),
            'revenue' => $monthlySales->pluck('total'),
            'profits' => $monthlySales->pluck('total')->map(fn($v) => $v * 0.3),
        ];
    }
}
