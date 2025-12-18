<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $payments;

    public function __construct(PaymentService $payments)
    {
        $this->payments = $payments;
    }

    public function index()
    {
        return Payment::with('order')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'     => 'required|exists:orders,id',
            'user_id'      => 'required|exists:users,id',
            'payment_type' => 'required|integer|min:1',
            'amount'       => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            $order = Order::findOrFail($validated['order_id']);
            return $this->payments->createPayment($order, $validated);
        });
    }

    public function show(Payment $payment)
    {
        return $payment->load('order');
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'payment_type' => 'required|integer|min:1',
            'amount'       => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($payment, $validated) {
            return $this->payments->updatePayment($payment, $validated);
        });
    }

    public function destroy(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $deleted = $this->payments->deletePayment($payment);
            return response()->json(['message' => 'Payment deleted']);
        });
    }
}
