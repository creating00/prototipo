<?php

namespace App\Services\Sale;

use App\Enums\PaymentType;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Branch;
use App\Models\Client;
use App\Traits\CalculatesTotalFromItems;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaleValidator
{
    use CalculatesTotalFromItems;

    /**
     * Valida los datos de la venta.
     *
     * @param array $data Datos de la venta a validar.
     * @param int|null $ignoreId ID de la venta a ignorar (para actualizaciones).
     * @return array Datos validados.
     * @throws ValidationException
     */
    public function validate(array $data, $ignoreId = null): array
    {
        $validator = Validator::make($data, $this->getValidationRules($data));

        $validator->after(function ($validator) use ($data) {
            $this->addCustomValidations($validator, $data);
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Obtiene todas las reglas de validación combinadas.
     *
     * @param array $data
     * @return array
     */
    protected function getValidationRules(array $data): array
    {
        $rules = array_merge(
            $this->getBasicRules(),
            $this->getItemRules(),
            $this->getPaymentRules($data),
            $this->getCustomerSpecificRules($data),
            $this->getNewPaymentFieldsRules()
        );

        return $rules;
    }

    /**
     * Reglas de validación básicas para la entidad Sale.
     *
     * @return array
     */
    protected function getBasicRules(): array
    {
        return [
            'branch_id'     => 'required|exists:branches,id',
            'user_id'       => 'nullable|exists:users,id',
            'sale_type'     => 'required|integer|in:' . implode(',', array_keys(SaleType::forSelect())),
            'status'        => 'required|integer|in:' . implode(',', array_keys(SaleStatus::forSelect())),
            'customer_type' => 'required|in:App\Models\Client,App\Models\Branch',
            'sale_date'     => 'required|date',
            'notes'         => 'nullable|string|max:500',
        ];
    }

    /**
     * Reglas de validación para los ítems de la venta.
     *
     * @return array
     */
    protected function getItemRules(): array
    {
        return [
            'items'             => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|numeric|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Reglas de validación para los detalles de pago.
     *
     * @param array $data
     * @return array
     */
    protected function getPaymentRules(array $data): array
    {
        $rules = [
            'payment_type'   => 'required|integer|in:' . implode(',', [PaymentType::Cash->value, PaymentType::Transfer->value]),
            'payment_notes'  => 'nullable|string|max:500',
        ];

        // Para ventas entre sucursales, el pago podría ser diferido
        if (isset($data['customer_type']) && $data['customer_type'] === Branch::class) {
            $rules['payment_type'] = 'required|integer|in:' . PaymentType::Transfer->value;
            $rules['amount_received'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    /**
     * Reglas de validación para campos de balance y monto.
     *
     * @return array
     */
    protected function getNewPaymentFieldsRules(): array
    {
        return [
            'amount_received'   => 'nullable|numeric|min:0',
            'change_returned'   => 'nullable|numeric|min:0',
            'remaining_balance' => 'nullable|numeric|min:0',
            'repair_amount' => 'exclude_unless:sale_type,' . SaleType::Repair->value . '|numeric|min:0.01',
        ];
    }

    /**
     * Reglas de validación específicas para el tipo de cliente.
     *
     * @param array $data
     * @return array
     */
    protected function getCustomerSpecificRules(array $data): array
    {
        $rules = [];

        if (isset($data['customer_type'])) {
            if ($data['customer_type'] === Client::class) {
                $rules['client_id'] = 'required|exists:clients,id';
            }

            if ($data['customer_type'] === Branch::class) {
                $rules['branch_recipient_id'] = 'required|exists:branches,id';
            }
        }

        return $rules;
    }

    /**
     * Ejecuta validaciones personalizadas después de las reglas básicas.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param array $data
     * @return void
     */
    protected function addCustomValidations($validator, array $data): void
    {
        $this->validatePaymentAmounts($validator, $data);
        $this->validateInterBranchSale($validator, $data);
    }

    /**
     * Valida la consistencia de los montos de pago (recibido, cambio, balance).
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param array $data
     * @return void
     */
    protected function validatePaymentAmounts($validator, array $data): void
    {
        if (empty($data['items'])) {
            return;
        }

        $total = $this->calculateTotalFromItems($data['items']);

        if (isset($data['amount_received'])) {
            $amountReceived = $data['amount_received'];

            if ($amountReceived < 0) {
                $validator->errors()->add('amount_received', 'El monto recibido no puede ser negativo');
            }

            if (isset($data['change_returned'])) {
                $changeReturned = $data['change_returned'];

                if ($changeReturned < 0) {
                    $validator->errors()->add('change_returned', 'El cambio devuelto no puede ser negativo');
                }

                if ($changeReturned > 0 && $amountReceived <= $total) {
                    $validator->errors()->add('amount_received', 'Si hay cambio devuelto, el monto recibido debe ser mayor al total de la venta');
                }

                if ($changeReturned > $amountReceived) {
                    $validator->errors()->add('change_returned', 'El cambio devuelto no puede ser mayor al monto recibido');
                }
            }
        }

        if (isset($data['remaining_balance'])) {
            $remainingBalance = $data['remaining_balance'];

            if ($remainingBalance < 0) {
                $validator->errors()->add('remaining_balance', 'El saldo pendiente no puede ser negativo');
            }

            if (isset($data['amount_received'])) {
                $expectedBalance = max(0, $total - $data['amount_received']);

                if (abs($remainingBalance - $expectedBalance) > 0.01) {
                    $validator->errors()->add('remaining_balance', sprintf(
                        'El saldo pendiente (%.2f) no coincide. Se esperaba: %.2f (Total: %.2f - Recibido: %.2f)',
                        $remainingBalance,
                        $expectedBalance,
                        $total,
                        $data['amount_received']
                    ));
                }
            }
        }
    }

    /**
     * Valida que una venta entre sucursales no se realice a la misma sucursal de origen.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param array $data
     * @return void
     */
    protected function validateInterBranchSale($validator, array $data): void
    {
        if (!isset($data['customer_type'], $data['branch_id'], $data['branch_recipient_id'])) {
            return;
        }

        if (
            $data['customer_type'] === Branch::class &&
            $data['branch_id'] == $data['branch_recipient_id']
        ) {
            $validator->errors()->add('branch_recipient_id', 'No puede realizar una venta a la misma sucursal');
        }
    }
}
