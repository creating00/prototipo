<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function createPayment(Order $order, array $data): Payment
    {
        $this->validatePaymentAmount($order, $data['amount']);

        return Payment::create([
            'order_id'     => $order->id,
            'user_id'      => $data['user_id'],
            'payment_type' => $data['payment_type'],
            'amount'       => $data['amount'],
        ]);
    }

    public function updatePayment(Payment $payment, array $data): Payment
    {
        $order = $payment->order;

        $paidExcludingThis = $order->payments()
            ->where('id', '!=', $payment->id)
            ->sum('amount');

        $newTotal = $paidExcludingThis + $data['amount'];

        if ($newTotal > $order->amount_to_charge) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds remaining balance.'
            ]);
        }

        $payment->update($data);
        return $payment->fresh()->load('order');
    }

    public function deletePayment(Payment $payment): bool
    {
        return $payment->delete();
    }

    private function validatePaymentAmount(Order $order, $amount): void
    {
        $amount = floatval($amount);

        $paid = $order->payments()->sum('amount');
        $remaining = $order->amount_to_charge - $paid;

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds remaining balance.'
            ]);
        }
    }
}
