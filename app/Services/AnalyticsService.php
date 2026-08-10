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

        $filteredSales = $this->getFilteredSalesTotal($filters);
        $filteredExpenses = $this->getFilteredExpensesTotal($filters);

        return [
            'infoboxes'        => $salesInfo,
            'expenseBoxes'     => $expenseInfo,
            'bankAccountBoxes' => $bankAccountBoxes,
            'filteredSales'    => $filteredSales,
            'filteredExpenses' => $filteredExpenses,
            'filteredBalance'  => $this->getBalanceTotals($filters, $filteredSales, $filteredExpenses),
            'resultBoxes'      => $this->calculateResultBoxes($salesInfo, $expenseInfo, $branchId, $filters),
            'products'         => $this->getTopProducts($filters),
            'clients'          => $this->getTopClients($filters),
            'chartData'        => $this->getMonthlyChartData($branchId),
            'stockReport'      => $this->getStockReport($branchId),
        ];
    }

    private function applyBranchFilter($query, string|int|array|null $branchId, string $column = 'branch_id')
    {
        if (is_array($branchId)) {
            return $query->whereIn($column, $branchId);
        }

        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            return $query->where($column, (int) $branchId);
        }

        return $query;
    }

    private function getSalesInfoboxes(string|int|array|null $branchId, array $filters): array
    {
        $stats = config('analytics.infoboxes');
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);

        $today = [now()->startOfDay(), now()->endOfDay()];
        $month = $hasRange ? [Carbon::parse($filters['start_date'])->startOfDay(), Carbon::parse($filters['end_date'])->endOfDay()] : [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay()];
        $year = [now()->startOfYear()->startOfDay(), now()->endOfYear()->endOfDay()];

        $salesCounts = Sale::forBranch($branchId)
            ->selectRaw("
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as today_count,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as month_count,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as year_count
            ", [
                $today[0], $today[1],
                $month[0], $month[1],
                $year[0], $year[1]
            ])
            ->first();

        $rate = $this->exchangeService->getCurrentDollarRate();
        $usdValue = CurrencyType::USD->value;
        $paymentExpression = "CASE WHEN payments.currency = '{$usdValue}' THEN payments.amount * {$rate} ELSE payments.amount END";

        $paymentSums = Payment::where('paymentable_type', Sale::class)
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
            ->selectRaw("
                SUM(CASE WHEN payments.created_at BETWEEN ? AND ? THEN {$paymentExpression} ELSE 0 END) as today_sum,
                SUM(CASE WHEN payments.created_at BETWEEN ? AND ? THEN {$paymentExpression} ELSE 0 END) as month_sum,
                SUM(CASE WHEN payments.created_at BETWEEN ? AND ? THEN {$paymentExpression} ELSE 0 END) as year_sum
            ", [
                $today[0], $today[1],
                $month[0], $month[1],
                $year[0], $year[1]
            ])
            ->first();

        $stats['sales_today']['number'] = $salesCounts->today_count ?? 0;
        $stats['sales_today']['secondaryNumber'] = $paymentSums->today_sum ?? 0;
        $stats['sales_today']['secondarySuffix'] = '$';

        $stats['sales_month']['number'] = $salesCounts->month_count ?? 0;
        $stats['sales_month']['secondaryNumber'] = $paymentSums->month_sum ?? 0;
        $stats['sales_month']['secondarySuffix'] = '$';

        $stats['sales_year']['number'] = $salesCounts->year_count ?? 0;
        $stats['sales_year']['secondaryNumber'] = $paymentSums->year_sum ?? 0;
        $stats['sales_year']['secondarySuffix'] = '$';

        return $stats;
    }

    private function getExpenseInfoboxes(string|int|array|null $branchId, array $filters): array
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

        $rate = app(CurrencyExchangeService::class)->getCurrentDollarRate();
        $usdValue = CurrencyType::USD->value;
        $expenseExpression = "CASE WHEN currency = {$usdValue} THEN amount * {$rate} ELSE amount END";

        $todayStr = today()->toDateString();
        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();

        $expenseSums = (clone $queryBase)
            ->selectRaw("
                SUM(CASE WHEN date = ? THEN {$expenseExpression} ELSE 0 END) as today_sum,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN {$expenseExpression} ELSE 0 END) as month_sum,
                SUM(CASE WHEN date BETWEEN ? AND ? THEN {$expenseExpression} ELSE 0 END) as year_sum
            ", [
                $todayStr,
                $startDate, $endDate,
                $yearStart, $yearEnd
            ])
            ->first();

        $boxes['expenses_today']['number'] = (float) ($expenseSums->today_sum ?? 0);
        $boxes['expenses_month']['number'] = (float) ($expenseSums->month_sum ?? 0);
        $boxes['expenses_year']['number'] = (float) ($expenseSums->year_sum ?? 0);

        return $boxes;
    }

    private function getBankAccountStats(array $filters): array
    {
        $branchId = $filters['branch_id'];
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);
        
        $start = $hasRange ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $hasRange ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfMonth()->endOfDay();

        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])->get();

        $salesTotals = Payment::where('paymentable_type', Sale::class)
            ->where('payment_type', \App\Enums\PaymentType::Transfer->value)
            ->whereBetween('created_at', [$start, $end])
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
            ->where('payment_method_type', \App\Models\BankAccount::class)
            ->select('payment_method_id')
            ->selectRaw("SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as sales_ars", [CurrencyType::ARS->value])
            ->selectRaw("SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as sales_usd", [CurrencyType::USD->value])
            ->groupBy('payment_method_id')
            ->get()
            ->keyBy('payment_method_id');

        $expensesTotals = Expense::forBranch($branchId)
            ->where('payment_type', \App\Enums\PaymentType::Transfer->value)
            ->whereBetween('date', [$start, $end])
            ->select('bank_account_id')
            ->selectRaw("SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as expenses_ars", [CurrencyType::ARS->value])
            ->selectRaw("SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as expenses_usd", [CurrencyType::USD->value])
            ->groupBy('bank_account_id')
            ->get()
            ->keyBy('bank_account_id');

        $stats = [];
        foreach ($bankAccounts as $account) {
            $sales = $salesTotals->get($account->id);
            $expenses = $expensesTotals->get($account->id);

            $salesArs = (float)($sales?->sales_ars ?? 0);
            $salesUsd = (float)($sales?->sales_usd ?? 0);
            $expensesArs = (float)($expenses?->expenses_ars ?? 0);
            $expensesUsd = (float)($expenses?->expenses_usd ?? 0);

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

    private function getFilteredSalesTotal(array $filters): array
    {
        $branchId = $filters['branch_id'];
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);
        
        $start = $hasRange ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $hasRange ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfMonth()->endOfDay();

        $query = Payment::where('paymentable_type', Sale::class)
            ->whereBetween('created_at', [$start, $end])
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId));

        if (!empty($filters['sales_payment_type'])) {
            $query->where('payment_type', $filters['sales_payment_type']);
            
            if ((int)$filters['sales_payment_type'] === \App\Enums\PaymentType::Transfer->value && !empty($filters['sales_bank_account_id'])) {
                $query->where('payment_method_id', $filters['sales_bank_account_id'])
                      ->where('payment_method_type', \App\Models\BankAccount::class);
            } elseif ((int)$filters['sales_payment_type'] === \App\Enums\PaymentType::Card->value && !empty($filters['sales_bank_id'])) {
                $query->where('payment_method_id', $filters['sales_bank_id'])
                      ->where('payment_method_type', \App\Models\Bank::class);
            }
        }

        $totals = $query->selectRaw("
            SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as ars,
            SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as usd
        ", [CurrencyType::ARS->value, CurrencyType::USD->value])->first();

        return [
            'ars' => (float)($totals->ars ?? 0),
            'usd' => (float)($totals->usd ?? 0),
        ];
    }

    private function getFilteredExpensesTotal(array $filters): array
    {
        $branchId = $filters['branch_id'];
        $hasRange = !empty($filters['start_date']) && !empty($filters['end_date']);
        
        $start = $hasRange ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $hasRange ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfMonth()->endOfDay();

        $query = Expense::forBranch($branchId)
            ->whereBetween('date', [$start, $end]);

        if (!empty($filters['expenses_expense_type_id'])) {
            $query->where('expense_type_id', $filters['expenses_expense_type_id']);
        }

        if (!empty($filters['expenses_payment_type'])) {
            $query->where('payment_type', $filters['expenses_payment_type']);

            if ((int)$filters['expenses_payment_type'] === \App\Enums\PaymentType::Transfer->value && !empty($filters['expenses_bank_account_id'])) {
                $query->where('bank_account_id', $filters['expenses_bank_account_id']);
            }
        }

        $totals = $query->selectRaw("
            SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as ars,
            SUM(CASE WHEN currency = ? THEN amount ELSE 0 END) as usd
        ", [CurrencyType::ARS->value, CurrencyType::USD->value])->first();

        return [
            'ars' => (float)($totals->ars ?? 0),
            'usd' => (float)($totals->usd ?? 0),
        ];
    }

    private function getBalanceTotals(array $filters, array $filteredSales, array $filteredExpenses): array
    {
        if (!empty($filters['balance_payment_type'])) {
            // Ventas específicas para el balance
            $salesFilters = array_merge($filters, [
                'sales_payment_type'    => $filters['balance_payment_type'],
                'sales_bank_account_id' => $filters['balance_bank_account_id'] ?? null,
                'sales_bank_id'         => $filters['balance_bank_id'] ?? null,
            ]);
            $balanceSales = $this->getFilteredSalesTotal($salesFilters);

            // Gastos específicos para el balance
            $expensesFilters = array_merge($filters, [
                'expenses_payment_type'    => $filters['balance_payment_type'],
                'expenses_bank_account_id' => $filters['balance_bank_account_id'] ?? null,
            ]);
            $balanceExpenses = $this->getFilteredExpensesTotal($expensesFilters);

            return [
                'ars' => $balanceSales['ars'] - $balanceExpenses['ars'],
                'usd' => $balanceSales['usd'] - $balanceExpenses['usd'],
            ];
        }

        // Si no hay filtro específico de balance, usar la diferencia de los ya filtrados
        return [
            'ars' => $filteredSales['ars'] - $filteredExpenses['ars'],
            'usd' => $filteredSales['usd'] - $filteredExpenses['usd'],
        ];
    }

    private function getCostOfGoodsSold(string|int|array|null $branchId, array $dates): float
    {
        $start = Carbon::parse($dates[0])->startOfDay();
        $end = Carbon::parse($dates[1])->endOfDay();
        $effectiveBranch = is_array($branchId) ? ($branchId[0] ?? null) : $branchId;

        $saleItems = \App\Models\SaleItem::whereHas('sale', function ($q) use ($branchId, $start, $end) {
            $q->forBranch($branchId)
              ->whereBetween('created_at', [$start, $end])
              ->whereNull('deleted_at');
        })
        ->with([
            'product' => fn($q) => $q->withTrashed(),
            'product.productBranches' => fn($q) => $this->applyBranchFilter($q, $branchId),
            'product.productBranches.prices'
        ])
        ->get();

        return (float) $saleItems->sum(function ($item) use ($effectiveBranch) {
            $cost = $item->product?->purchasePrice($effectiveBranch) ?? 0;
            return $cost * $item->quantity;
        });
    }

    private function calculateResultBoxes(array $salesInfo, array $expenseInfo, string|int|array|null $branchId, array $filters): array
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
        $branchId = $filters['branch_id'] ?? null;

        $query = Product::select('products.name')
            ->selectRaw('SUM(sale_items.quantity) as units')
            ->selectRaw('SUM(sale_items.subtotal) as total')
            ->join('sale_items', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at');

        $this->applyBranchFilter($query, $branchId, 'sales.branch_id');

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
        $branchId = $filters['branch_id'] ?? null;

        $query = Client::forBranch($branchId)
            ->select('clients.full_name as name')
            ->selectRaw('COUNT(DISTINCT sales.id) as orders')
            ->selectRaw("{$this->getConvertedPaymentExpression()} as total")
            ->join('sales', 'sales.customer_id', '=', 'clients.id')
            ->join('payments', function ($join) {

                $join->on('payments.paymentable_id', '=', 'sales.id')
                    ->where('payments.paymentable_type', Sale::class);
            })
            ->where('sales.customer_type', Client::class)
            ->whereNull('sales.deleted_at');

        $this->applyBranchFilter($query, $branchId, 'sales.branch_id');

        return $query->groupBy('clients.id', 'clients.full_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    private function getStockReport(string|int|array|null $branchId)
    {
        // Margen de aviso preventivo sobre el umbral
        $alertMargin = 10;

        $query = ProductBranch::with('product')
            ->whereHas('product')
            ->where('status', '!=', ProductStatus::Discontinued)
            ->whereRaw('stock <= (low_stock_threshold + ?)', [$alertMargin]);

        $this->applyBranchFilter($query, $branchId, 'branch_id');

        return $query->get()
            ->map(fn($pb) => [
                'name'      => $pb->product->name,
                'stock'     => $pb->stock,
                'threshold' => $pb->low_stock_threshold,
                'is_low'    => $pb->stock <= $pb->low_stock_threshold,
                'is_near'   => $pb->stock > $pb->low_stock_threshold
            ]);
    }

    private function getStockReportOld(string|int|array|null $branchId)
    {
        // Margen de aviso preventivo sobre el umbral
        $alertMargin = 10;

        $query = ProductBranch::with(['product' => fn($q) => $q->withTrashed()])
            ->where('status', '!=', ProductStatus::Discontinued)
            ->whereRaw('stock <= (low_stock_threshold + ?)', [$alertMargin]);

        $this->applyBranchFilter($query, $branchId, 'branch_id');

        return $query->get()
            ->filter(fn($pb) => $pb->product !== null) // Seguridad extra
            ->map(fn($pb) => [
                'name'      => $pb->product->name ?? 'Producto no encontrado',
                'stock'     => $pb->stock,
                'threshold' => $pb->low_stock_threshold,
                'is_low'    => $pb->stock <= $pb->low_stock_threshold,
                'is_near'   => $pb->stock > $pb->low_stock_threshold
            ]);
    }

    private function getMonthlyChartData(string|int|array|null $branchId): array
    {
        $currentYear = now()->year;
        $rate = $this->exchangeService->getCurrentDollarRate();
        $usdValue = CurrencyType::USD->value;

        $driver = DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        $monthPaymentExpr = $isSqlite ? 'CAST(strftime("%m", payments.created_at) AS INTEGER)' : 'MONTH(payments.created_at)';
        $monthExpenseExpr = $isSqlite ? 'CAST(strftime("%m", date) AS INTEGER)' : 'MONTH(date)';
        $yearPaymentExpr = $isSqlite ? 'CAST(strftime("%Y", payments.created_at) AS INTEGER)' : 'YEAR(payments.created_at)';
        $yearExpenseExpr = $isSqlite ? 'CAST(strftime("%Y", date) AS INTEGER)' : 'YEAR(date)';

        // --- DATOS MENSUALES (Año actual) ---
        $paymentsMonth = Payment::where('paymentable_type', Sale::class)
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
            ->whereYear('created_at', $currentYear)
            ->selectRaw($monthPaymentExpr . ' as month, ' . $this->getConvertedPaymentExpression() . ' as total')
            ->groupBy('month')->pluck('total', 'month');

        $expensesMonth = Expense::forBranch($branchId)
            ->whereYear('date', $currentYear)
            ->selectRaw($monthExpenseExpr . ' as month, SUM(CASE WHEN currency = ' . "'{$usdValue}'" . ' THEN amount * ' . $rate . ' ELSE amount END) as total')
            ->groupBy('month')->pluck('total', 'month');

        // --- DATOS ANUALES (Últimos 5 años) ---
        $years = collect(range($currentYear - 4, $currentYear));

        $paymentsYear = Payment::where('paymentable_type', Sale::class)
            ->whereHasMorph('paymentable', [Sale::class], fn($q) => $q->forBranch($branchId))
            ->whereBetween('created_at', [now()->subYears(4)->startOfYear(), now()->endOfYear()])
            ->selectRaw($yearPaymentExpr . ' as year, ' . $this->getConvertedPaymentExpression() . ' as total')
            ->groupBy('year')->pluck('total', 'year');

        $expensesYear = Expense::forBranch($branchId)
            ->whereBetween('date', [now()->subYears(4)->startOfYear(), now()->endOfYear()])
            ->selectRaw($yearExpenseExpr . ' as year, SUM(CASE WHEN currency = ' . "'{$usdValue}'" . ' THEN amount * ' . $rate . ' ELSE amount END) as total')
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
