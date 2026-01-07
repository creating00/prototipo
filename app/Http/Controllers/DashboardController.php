<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ProviderOrderStatus;
use App\Models\{Sale, Order, Product, Client, Expense, Branch, Discount, Payment, PriceModification, ProductBranch, Provider, ProviderOrder};
use App\Traits\AuthTrait;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    use AuthTrait;
    public function index()
    {
        $branchId = $this->currentBranchId();

        $cards = config('dashboardCards.cards');

        // Llenamos los valores dinámicamente
        // $cards['sales']['value'] = Sale::forBranch($branchId)->count();

        $totalSalesToday = Payment::where('paymentable_type', Sale::class)
            ->whereHasMorph(
                'paymentable',
                [Sale::class],
                function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->whereDate('created_at', today());
                }
            )
            ->sum('amount');

        $salesTodayCount = Sale::forBranch($branchId)
            ->today()
            ->count();

        $cards['sales']['description'] = "Ventas realizadas hoy: {$salesTodayCount}";
        $cards['sales']['value'] = '$' . number_format($totalSalesToday, 0, ',', '.');

        $pendingOrdersCount = Order::forBranch($branchId)
            ->pending()
            ->count();

        $cards['orders']['value'] = Order::forBranch($branchId)->count();
        $cards['orders']['description'] = "Pedidos pendientes: {$pendingOrdersCount}";

        $cards['products']['value'] = ProductBranch::where('branch_id', $branchId) ->distinct('product_id') ->count('product_id');
        $cards['clients']['value']  = Client::count();
        $cards['branches']['value'] = Branch::count();
        $cards['providers']['value'] = Provider::count();

        $pendingProviderOrdersCount = ProviderOrder::forBranch($branchId)
            ->pending()
            ->count();
        $cards['provider-orders']['description'] = "Pedidos pendientes: {$pendingProviderOrdersCount}";
        $cards['provider-orders']['value'] = ProviderOrder::forBranch($branchId)->count();

        $cards['discounts']['value'] = Discount::active()->count();
        $cards['audits']['value'] = PriceModification::forBranch($branchId)->count();

        // $totalExpenses = Expense::forBranch($branchId)->sum('amount');
        $totalMonthlyExpenses = Expense::forBranch($branchId)
            ->thisMonth()
            ->sum('amount');
        $monthName = Carbon::now()->translatedFormat('F');

        $cards['expenses']['value'] = '$' . number_format($totalMonthlyExpenses, 0, ',', '.');
        $cards['expenses']['description'] = "Gastos del mes: {$monthName}";

        $userId = $this->userId();

        return view('dashboard', compact('cards', 'userId'));
    }
}
