<?php

namespace App\Http\Controllers;

use App\Models\{Sale, Order, Product, Client, Expense, Branch, Provider};
use App\Traits\AuthTrait;

class DashboardController extends Controller
{
    use AuthTrait;
    public function index()
    {
        $cards = config('dashboardCards.cards');

        // Llenamos los valores dinámicamente
        $cards['sales']['value']    = Sale::count();
        $cards['orders']['value']   = Order::count();
        $cards['products']['value'] = Product::count();
        $cards['clients']['value']  = Client::count();
        $cards['branches']['value'] = Branch::count();
        $cards['providers']['value'] = Provider::count();

        // Ejemplo para gastos: sumatoria total formateada
        $totalExpenses = Expense::sum('amount');
        $cards['expenses']['value'] = '$' . number_format($totalExpenses, 0, ',', '.');

        $userId = $this->userId();

        return view('dashboard', compact('cards', 'userId'));
    }
}
