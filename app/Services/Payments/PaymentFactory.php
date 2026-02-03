<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Enums\PaymentType;
use App\Models\Bank;
use App\Models\BankAccount;

class PaymentFactory
{
    public static function build(array $data): array
    {
        $type = PaymentType::from($data['payment_type']);

        return match ($type) {
            PaymentType::Cash => [
                'payment_method_type' => null,
                'payment_method_id'   => null,
            ],
            PaymentType::Transfer => self::transfer($data),
            PaymentType::Card,
            PaymentType::Check => self::bankBased($data),
        };
    }

    private static function transfer(array $data): array
    {
        return [
            // Asigna null si no existe la llave
            'payment_method_type' => $data['payment_method_type'] ?? BankAccount::class,
            'payment_method_id'   => $data['bank_account_id'] ?? null,
        ];
    }

    private static function bankBased(array $data): array
    {
        return [
            // Asigna null si no existe la llave
            'payment_method_type' => $data['payment_method_type'] ?? Bank::class,
            'payment_method_id'   => $data['bank_id'] ?? null,
        ];
    }
}
