<?php

namespace App\Services\Sale;

use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Branch;
use App\Models\Client;
use App\Traits\CalculatesSubtotalFromItems;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleValidator
{
    use CalculatesSubtotalFromItems;

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
        if (isset($data['customer_id'], $data['customer_type']) && $data['customer_type'] === Branch::class) {
            $data['branch_recipient_id'] = $data['customer_id'];
        }

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
            'source_order_id' => 'nullable|exists:orders,id',
            'discount_id' => 'nullable|exists:discounts,id',
            'discount_amount'   => 'nullable|numeric|min:0',
            'skip_stock_movement' => 'sometimes|boolean',
            'requires_invoice' => 'sometimes|boolean',
            'amount_received'     => 'nullable|numeric|min:0',
            'change_returned'     => 'nullable|numeric|min:0',
            'remaining_balance'   => 'nullable|numeric|min:0',
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
            'items.*.currency' => ['required', Rule::enum(CurrencyType::class)],
            'items.*.unit_price'  => 'required|numeric|min:0',
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
        $allPaymentTypes = array_column(PaymentType::cases(), 'value');
        $typesCsv = implode(',', $allPaymentTypes);

        $isDual = isset($data['enable_dual_payment']) && $data['enable_dual_payment'] == '1';

        return [
            // Pago 1: Siempre requerido si no es una venta a crédito/sucursal diferida
            'payment_type'    => "required|integer|in:$typesCsv",
            'amount_received' => 'required|numeric|min:0',
            'payment_notes'   => 'nullable|string|max:500',

            // Pago 2: Estrictamente dependiente del flag
            'enable_dual_payment' => 'sometimes|boolean',
            'payment_type_2'      => "nullable|required_if:enable_dual_payment,1|integer|in:$typesCsv",
            'amount_received_2'   => "nullable|required_if:enable_dual_payment,1|numeric|min:0",
        ];
    }

    /**
     * Reglas de validación para campos de balance y monto.
     *
     * @return array
     */
    protected function getNewPaymentFieldsRules(): array
    {
        return [
            'repair_amount' => 'exclude_unless:sale_type,' . SaleType::Repair->value . '|numeric|min:0.01',
            'totals' => 'nullable|json',
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
        $this->validateTotals($validator, $data);
        $this->validateInterBranchSale($validator, $data);
        //$this->validateDiscount($validator, $data);
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
        $total = app(SaleTotalResolver::class)->resolve($data);

        if (isset($data['amount_received'])) {
            $amountReceived = round((float)$data['amount_received'], 2);
            $changeReturned = round((float)($data['change_returned'] ?? 0), 2);

            if ($amountReceived < 0) {
                $validator->errors()->add(
                    'amount_received',
                    'El monto recibido no puede ser negativo'
                );
            }

            if (($amountReceived - $total) < $changeReturned) {
                $validator->errors()->add(
                    'amount_received',
                    "Monto insuficiente para el cambio entregado. Total: {$total}, Recibido: {$amountReceived}, Cambio: {$changeReturned}"
                );
            }
        }

        if (isset($data['remaining_balance'])) {
            $expected = round(
                max(0, $total - ($data['amount_received'] ?? 0)),
                2
            );

            if (abs($expected - $data['remaining_balance']) > 0.01) {
                $validator->errors()->add(
                    'remaining_balance',
                    sprintf(
                        'El saldo pendiente (%.2f) no coincide. Se esperaba: %.2f',
                        $data['remaining_balance'],
                        $expected
                    )
                );
            }
        }
    }

    protected function validateDiscount($validator, array $data): void
    {
        if (!isset($data['discount_id'])) {
            return;
        }

        $discount = \App\Models\Discount::where('id', $data['discount_id'])
            ->where('is_active', true)
            ->first();

        if (!$discount) {
            $validator->errors()->add(
                'discount_id',
                'El descuento seleccionado no es válido o no está activo'
            );
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

    protected function validateTotals($validator, array $data): void
    {
        if (!isset($data['totals'])) {
            return;
        }

        $totals = json_decode($data['totals'], true);

        if (!is_array($totals) || empty($totals)) {
            $validator->errors()->add(
                'totals',
                'El formato de totales es inválido.'
            );
            return;
        }

        foreach ($totals as $currencyId => $amount) {
            if (!is_numeric($currencyId) || !is_numeric($amount)) {
                $validator->errors()->add(
                    'totals',
                    'El formato de totales es inválido.'
                );
                return;
            }

            if ($amount < 0) {
                $validator->errors()->add(
                    'totals',
                    'Los totales no pueden ser negativos.'
                );
                return;
            }
        }
    }
}
