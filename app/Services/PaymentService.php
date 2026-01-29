<?php

namespace App\Services;

use App\Models\Payment;
use App\Enums\PaymentType;
use App\Services\Payments\PaymentFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Crea un nuevo pago para un modelo
     */
    // public function create(Model $paymentable, array $data): Payment
    // {
    //     $validated = $this->validatePaymentData($data);

    //     return $paymentable->payments()->create([
    //         'user_id' => $validated['user_id'],
    //         'payment_type' => $validated['payment_type'],
    //         'amount' => $validated['amount'],
    //     ]);
    // }

    public function create(Model $paymentable, array $data): Payment
    {
        $validated = $this->validatePaymentData($data);
        $methodData = PaymentFactory::build($validated);

        return $paymentable->payments()->create([
            'branch_id'    => $validated['branch_id'] ?? ($paymentable->branch_id ?? null),
            'user_id'      => $validated['user_id'],
            'payment_type' => $validated['payment_type'],
            'amount'       => $validated['amount'],
            'notes'        => $validated['notes'] ?? null,
            'currency'     => $data['currency'] ?? \App\Enums\CurrencyType::ARS,
            ...$methodData, // Aquí entra el payment_method_id y type de la Factory
        ]);
    }

    /**
     * Actualiza un pago existente
     */
    // public function update(Payment $payment, array $data): Payment
    // {
    //     $validated = $this->validatePaymentData($data, $payment->id);

    //     $payment->update([
    //         'user_id' => $validated['user_id'],
    //         'payment_type' => $validated['payment_type'],
    //         'amount' => $validated['amount'],
    //     ]);

    //     return $payment->fresh();
    // }

    public function update(Payment $payment, array $data): Payment
    {
        $validated = $this->validatePaymentData($data, $payment->id);

        $methodData = PaymentFactory::build($validated);

        $payment->update([
            'user_id' => $validated['user_id'],
            'payment_type' => $validated['payment_type'],
            'amount' => $validated['amount'],
            ...$methodData,
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
        return Validator::make($data, [
            'user_id'             => 'required|exists:users,id',
            'payment_type'        => 'required|integer|in:' . implode(',', array_column(PaymentType::cases(), 'value')),
            'amount'              => 'required|numeric|min:0',
            'bank_id'             => 'nullable|exists:banks,id',
            'bank_account_id'     => 'nullable|exists:bank_accounts,id',
            'payment_method_type' => 'nullable|string',
            'notes'               => 'nullable|string|max:500',
            'branch_id'           => 'nullable|exists:branches,id',
        ])->validate();
    }
}
