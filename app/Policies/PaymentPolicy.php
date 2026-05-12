<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy extends BasePolicy
{
    protected string $resource = 'payments';

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $this->can($user, 'cancel')
            && $this->sameBranch($user, $payment)
            && $payment->deleted_at === null;
    }
}
