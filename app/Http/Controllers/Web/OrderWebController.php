<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;

class OrderWebController extends Controller
{
    public function index()
    {
        return view('admin.order.index');
    }

    public function create()
    {
        $products = Product::all();
        return view('admin.order.create', [
            'products' => $products,
            'order' => null // Pasamos order como null para crear
        ]);
    }

    public function edit($id)
    {
        $products = Product::all();
        $order = Order::with(['client', 'items.product'])->find($id);

        return view('admin.order.edit', [
            'id' => $id,
            'products' => $products,
            'order' => $order // Pasamos la orden completa para editar
        ]);
    }
}
