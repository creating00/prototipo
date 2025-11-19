<?php

namespace App\Enums;

enum PaymentType: int
{
    case Cash = 1;
    case Card = 2;
    case Transfer = 3;
    case Mobile = 4;

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Transfer => 'Bank Transfer',
            self::Mobile => 'Mobile Payment',
        };
    }
}
