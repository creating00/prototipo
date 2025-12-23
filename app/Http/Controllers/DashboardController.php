<?php

namespace App\Http\Controllers;

use App\Models\{Sale, Order, Product, Client, Expense, Branch, Provider};

class DashboardController extends Controller
{
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

        return view('dashboard', compact('cards'));
    }
}
