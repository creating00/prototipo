<?php

namespace App\Rules;

use App\Services\Catalogs\PublicUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class CatalogUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            app(PublicUrl::class)->parts((string) $value);
        } catch (InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }
}
