<?php

namespace App\Services;

use App\Models\Payment;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Crea un nuevo pago para un modelo
     */
    public function create(Model $paymentable, array $data): Payment
    {
        $validated = $this->validatePaymentData($data);

        return $paymentable->payments()->create([
            'user_id' => $validated['user_id'],
            'payment_type' => $validated['payment_type'],
            'amount' => $validated['amount'],
        ]);
    }

    /**
     * Actualiza un pago existente
     */
    public function update(Payment $payment, array $data): Payment
    {
        $validated = $this->validatePaymentData($data, $payment->id);

        $payment->update([
            'user_id' => $validated['user_id'],
            'payment_type' => $validated['payment_type'],
            'amount' => $validated['amount'],
        ]);

        return $payment->fresh();
    }

    /**
     * Elimina un pago
     */
    public function delete(Payment $payment): void
    {
        $payment->delete();
    }

    /**
     * Obtiene todos los pagos de un modelo
     */
    public function getPayments(Model $paymentable)
    {
        return $paymentable->payments()->with('user')->get();
    }

    /**
     * Calcula el total pagado de un modelo
     */
    public function getTotalPaid(Model $paymentable): float
    {
        return $paymentable->payments()->sum('amount');
    }

    /**
     * Calcula el saldo pendiente
     */
    public function getPendingBalance(Model $paymentable): float
    {
        return max(0, $paymentable->total_amount - $this->getTotalPaid($paymentable));
    }

    /**
     * Verifica si el modelo está completamente pagado
     */
    public function isFullyPaid(Model $paymentable): bool
    {
        return $this->getPendingBalance($paymentable) <= 0;
    }

    /**
     * Valida los datos del pago
     */
    public function validatePaymentData(array $data, $ignoreId = null): array
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'payment_type' => 'required|integer|in:' . implode(',', array_column(PaymentType::cases(), 'value')),
            'amount' => 'required|numeric|min:0.01',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
