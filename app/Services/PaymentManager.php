<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;

class PaymentManager
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Procesa un pago y actualiza el estado del modelo si es necesario
     */
    public function processPayment(Model $paymentable, array $paymentData): Payment
    {
        $payment = $this->paymentService->create($paymentable, $paymentData);

        // Actualizar estado si está completamente pagado
        if ($this->paymentService->isFullyPaid($paymentable)) {
            $this->markAsPaid($paymentable);
        }

        return $payment;
    }

    /**
     * Obtiene el resumen de pagos
     */
    public function getPaymentSummary(Model $paymentable): array
    {
        return [
            'total_amount' => $paymentable->total_amount,
            'total_paid' => $this->paymentService->getTotalPaid($paymentable),
            'pending_balance' => $this->paymentService->getPendingBalance($paymentable),
            'is_fully_paid' => $this->paymentService->isFullyPaid($paymentable),
            'payments' => $this->paymentService->getPayments($paymentable),
        ];
    }

    /**
     * Marca el modelo como pagado
     */
    protected function markAsPaid(Model $paymentable): void
    {
        // Este método puede ser sobrescrito por clases específicas
        // Por ejemplo: OrderPaymentManager, SalePaymentManager
        if (method_exists($paymentable, 'markAsPaid')) {
            $paymentable->markAsPaid();
        }
    }
}
