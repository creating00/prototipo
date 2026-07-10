<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Enums\ProductStatus;
use App\Models\{Sale, Product, Client, Payment, Expense, ProductBranch};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    private $exchangeService;

    public function __construct(CurrencyExchangeService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }

    /**
     * Retorna la expresión SQL para sumar montos de pagos convertidos a ARS.
     * Esta expresión se usa sobre la tabla 'payments'.
     */
    private function getConvertedPaymentExpression(): string
    {
        $rate = $this->exchangeService->getCurrentDollarRate();
        $usdValue = CurrencyType::USD->value;

        // Especificamos 'payments.' para evitar conflictos en los JOINs
        return "SUM(CASE WHEN payments.currency = '{$usdValue}' THEN payments.amount * {$rate} ELSE payments.amount END)";
    }

    public function getBranchStats(array $filters): array
    {
        $branchId = $filters['branch_id'];

        $salesInfo = $this->getSalesInfoboxes($branchId, $filters);
        $expenseInfo = $this->getExpenseInfoboxes($branchId, $filters);
        $bankAccountBoxes = $this->getBankAccountStats($filters);

        return [
            'infoboxes'        => $salesInfo,
            'expenseBoxes'     => $expenseInfo,
            'bankAccountBoxes' => $bankAccountBoxes,
            'resultBoxes'      => $this->calculateResultBoxes($salesInfo, $expenseInfo, $branchId, $filters),
            'products'         => $this->getTopProducts($filters),
            'clients'          => $this->getTopClients($filters),
            'chartData'        => $this->getMonthlyChartData($branchId),
            'stockReport'      => $this->getStockReport($branchId),
        ];
    }

    private function getSalesInfoboxes(int $branchId, array $filters): array
    {
        $stats = config('analytics.infoboxes');
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);

        $periods = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'month' => $hasRange ? [$filters['start_date'], $filters['end_date']] : [now()->startOfMonth(), now()->endOfMonth()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
        ];

        foreach ($periods as $period => $dates) {
            $key = "sales_$period";

            // Cantidad de transacciones (Ventas)
            $stats[$key]['number'] = Sale::forBranch($branchId)
                ->whereBetween('created_at', $dates)
                ->count();

            // Dinero real ingresado (Pagos)
            $stats[$key]['secondaryNumber'] = Payment::where('paymentable_type', Sale::class)
                ->whereBetween('created_at', $dates)
                ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
                ->selectRaw("{$this->getConvertedPaymentExpression()} as total")
                ->value('total') ?? 0;

            $stats[$key]['secondarySuffix'] = '$';
        }

        return $stats;
    }

    private function getExpenseInfoboxes(int $branchId, array $filters): array
    {
        $boxes = config('analytics.expense_infoboxes');
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);
        $expenseTypeId = $filters['expense_type_id'] ?? null;

        $startDate = $hasRange ? $filters['start_date'] : now()->startOfMonth();
        $endDate = $hasRange ? $filters['end_date'] : now()->endOfMonth();

        $queryBase = Expense::forBranch($branchId);
        if ($expenseTypeId) {
            $queryBase->where('expense_type_id', $expenseTypeId);
        }

        // Usa el scope sumConverted del modelo (que usa el trait)
        $boxes['expenses_today']['number'] = (clone $queryBase)
            ->whereDate('date', today())
            ->sumConverted();

        $boxes['expenses_month']['number'] = (clone $queryBase)
            ->whereBetween('date', [$startDate, $endDate])
            ->sumConverted();

        $boxes['expenses_year']['number'] = (clone $queryBase)
            ->whereYear('date', now()->year)
            ->sumConverted();

        return $boxes;
    }

    private function getBankAccountStats(array $filters): array
    {
        $branchId = $filters['branch_id'];
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);
        
        $start = $hasRange ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $hasRange ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfMonth()->endOfDay();

        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])->get();

        $stats = [];
        foreach ($bankAccounts as $account) {
            $salesQuery = Payment::where('paymentable_type', Sale::class)
                ->where('payment_type', \App\Enums\PaymentType::Transfer->value)
                ->where('payment_method_id', $account->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId));

            $salesArs = (clone $salesQuery)
                ->where('currency', CurrencyType::ARS->value)
                ->sum('amount');

            $salesUsd = (clone $salesQuery)
                ->where('currency', CurrencyType::USD->value)
                ->sum('amount');

            $expensesQuery = Expense::forBranch($branchId)
                ->where('payment_type', \App\Enums\PaymentType::Transfer->value)
                ->where('bank_account_id', $account->id)
                ->whereBetween('date', [$start, $end]);

            $expensesArs = (clone $expensesQuery)
                ->where('currency', CurrencyType::ARS->value)
                ->sum('amount');

            $expensesUsd = (clone $expensesQuery)
                ->where('currency', CurrencyType::USD->value)
                ->sum('amount');

            $stats[] = [
                'account' => $account,
                'sales_ars' => $salesArs,
                'sales_usd' => $salesUsd,
                'expenses_ars' => $expensesArs,
                'expenses_usd' => $expensesUsd,
                'net_ars' => $salesArs - $expensesArs,
                'net_usd' => $salesUsd - $expensesUsd,
            ];
        }

        return $stats;
    }

    private function getCostOfGoodsSold(int $branchId, array $dates): float
    {
        return \App\Models\SaleItem::whereHas('sale', function ($q) use ($branchId, $dates) {
            $q->where('branch_id', $branchId)
              ->whereBetween('created_at', $dates)
              ->whereNull('deleted_at');
        })
        ->get()
        ->sum(function ($item) use ($branchId) {
            $cost = $item->product->purchasePrice($branchId) ?? 0;
            return $cost * $item->quantity;
        });
    }

    private function calculateResultBoxes(array $salesInfo, array $expenseInfo, int $branchId, array $filters): array
    {
        $results = config('analytics.result_infoboxes');
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);
        
        $monthDates = $hasRange ? [$filters['start_date'], $filters['end_date']] : [now()->startOfMonth(), now()->endOfMonth()];
        $yearDates = [now()->startOfYear(), now()->endOfYear()];

        $cogsMonth = $this->getCostOfGoodsSold($branchId, $monthDates);
        $cogsYear = $this->getCostOfGoodsSold($branchId, $yearDates);

        $results['net_month']['number'] = $salesInfo['sales_month']['secondaryNumber'] - $expenseInfo['expenses_month']['number'];
        $results['net_year']['number']  = $salesInfo['sales_year']['secondaryNumber'] - $expenseInfo['expenses_year']['number'];
        
        $results['real_profit_month']['number'] = $salesInfo['sales_month']['secondaryNumber'] - $cogsMonth;
        $results['real_profit_year']['number']  = $salesInfo['sales_year']['secondaryNumber'] - $cogsYear;

        foreach (['net_month', 'net_year', 'real_profit_month', 'real_profit_year'] as $key) {
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
        $branchId = $filters['branch_id'];

        return Client::forBranch($branchId)
            ->select('clients.full_name as name')
            ->selectRaw('COUNT(DISTINCT sales.id) as orders')
            ->selectRaw("{$this->getConvertedPaymentExpression()} as total")
            ->join('sales', 'sales.customer_id', '=', 'clients.id')
            ->join('payments', function ($join) {

                $join->on('payments.paymentable_id', '=', 'sales.id')
                    ->where('payments.paymentable_type', Sale::class);
            })
            ->where('sales.customer_type', Client::class)
            ->where('sales.branch_id', $branchId)
            ->whereNull('sales.deleted_at')
            ->groupBy('clients.id', 'clients.full_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    private function getStockReport(int $branchId)
    {
        // Margen de aviso preventivo sobre el umbral
        $alertMargin = 10;

        return ProductBranch::with('product')
            ->where('branch_id', $branchId)
            ->whereHas('product')
            ->where('status', '!=', ProductStatus::Discontinued)
            ->whereRaw('stock <= (low_stock_threshold + ?)', [$alertMargin])
            ->get()
            ->map(fn($pb) => [
                'name'      => $pb->product->name,
                'stock'     => $pb->stock,
                'threshold' => $pb->low_stock_threshold,
                'is_low'    => $pb->stock <= $pb->low_stock_threshold,
                'is_near'   => $pb->stock > $pb->low_stock_threshold
            ]);
    }
    private function getStockReportOld(int $branchId)
    {
        // Margen de aviso preventivo sobre el umbral
        $alertMargin = 10;

        return ProductBranch::with(['product' => fn($q) => $q->withTrashed()])
            ->where('branch_id', $branchId)
            ->where('status', '!=', ProductStatus::Discontinued)
            ->whereRaw('stock <= (low_stock_threshold + ?)', [$alertMargin])
            ->get()
            ->filter(fn($pb) => $pb->product !== null) // Seguridad extra
            ->map(fn($pb) => [
                'name'      => $pb->product->name ?? 'Producto no encontrado',
                'stock'     => $pb->stock,
                'threshold' => $pb->low_stock_threshold,
                'is_low'    => $pb->stock <= $pb->low_stock_threshold,
                'is_near'   => $pb->stock > $pb->low_stock_threshold
            ]);
    }

    private function getMonthlyChartData(int $branchId): array
    {
        $currentYear = now()->year;
        $rate = $this->exchangeService->getCurrentDollarRate();
        $usdValue = CurrencyType::USD->value;

        // --- DATOS MENSUALES (Año actual) ---
        $paymentsMonth = Payment::where('paymentable_type', Sale::class)
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
            ->whereYear('created_at', $currentYear)
            ->selectRaw('MONTH(created_at) as month, ' . $this->getConvertedPaymentExpression() . ' as total')
            ->groupBy('month')->pluck('total', 'month');

        $expensesMonth = Expense::forBranch($branchId)
            ->whereYear('date', $currentYear)
            ->selectRaw('MONTH(date) as month, SUM(CASE WHEN currency = ' . "'{$usdValue}'" . ' THEN amount * ' . $rate . ' ELSE amount END) as total')
            ->groupBy('month')->pluck('total', 'month');

        // --- DATOS ANUALES (Últimos 5 años) ---
        $years = collect(range($currentYear - 4, $currentYear));

        $paymentsYear = Payment::where('paymentable_type', Sale::class)
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
            ->whereBetween('created_at', [now()->subYears(4)->startOfYear(), now()->endOfYear()])
            ->selectRaw('YEAR(created_at) as year, ' . $this->getConvertedPaymentExpression() . ' as total')
            ->groupBy('year')->pluck('total', 'year');

        $expensesYear = Expense::forBranch($branchId)
            ->whereBetween('date', [now()->subYears(4)->startOfYear(), now()->endOfYear()])
            ->selectRaw('YEAR(date) as year, SUM(CASE WHEN currency = ' . "'{$usdValue}'" . ' THEN amount * ' . $rate . ' ELSE amount END) as total')
            ->groupBy('year')->pluck('total', 'year');

        $months = collect(range(1, 12));

        return [
            // Datos para el gráfico de barras/líneas mensual
            'monthly' => [
                'labels'   => $months->map(fn($m) => Carbon::create()->month($m)->format('M')),
                'payments' => $months->map(fn($m) => (float)($paymentsMonth->get($m) ?? 0)),
                'expenses' => $months->map(fn($m) => (float)($expensesMonth->get($m) ?? 0)),
            ],
            // Datos para el gráfico de Ganancias Anuales (Histórico)
            'yearly' => [
                'labels'  => $years->map(fn($y) => (string)$y),
                'profits' => $years->map(fn($y) => (float)($paymentsYear->get($y, 0) - $expensesYear->get($y, 0))),
            ]
        ];
    }
}
