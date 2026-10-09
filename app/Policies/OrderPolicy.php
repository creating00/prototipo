<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    protected string $resource = 'orders';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Order $order): bool
    {
        if (! $this->can($user, 'view')) {
            return false;
        }

        if ($order->is_multi_destination) {
            return $this->participatesInTransfer($user, $order);
        }

        if ($user->hasRole('admin') || $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value) || ! $user->branch_id) {
            return true;
        }

        return (int) $order->branch_id === (int) $user->branch_id
            || ($order->isInterBranch() && (int) $order->customer_id === (int) $user->branch_id);
    }

    public function viewOwn(User $user): bool
    {
        return $this->can($user, 'view_own');
    }

    public function createClient(User $user): bool
    {
        return $this->can($user, 'create_client');
    }

    public function createBranch(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value)
            && $this->can($user, 'create_branch');
    }

    public function create_branch(User $user): bool
    {
        return $this->createBranch($user);
    }

    public function update(User $user, Order $order): bool
    {
        if (! $this->can($user, 'update')) {
            return false;
        }

        if ($order->is_multi_destination) {
            return $this->managesTransferOrigin($user, $order);
        }

        if ($user->hasRole('admin') || $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value) || ! $user->branch_id) {
            return true;
        }

        return (int) $order->branch_id === (int) $user->branch_id
            || ($order->isInterBranch() && (int) $order->customer_id === (int) $user->branch_id);
    }

    public function approve(User $user, Order $order): bool
    {
        if (! $this->can($user, 'approve')) {
            return false;
        }

        if ($order->is_multi_destination) {
            return $this->managesTransferOrigin($user, $order);
        }

        if ($user->hasRole('admin') || $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value) || ! $user->branch_id) {
            return true;
        }

        return (int) $order->branch_id === (int) $user->branch_id
            || ($order->isInterBranch() && (int) $order->customer_id === (int) $user->branch_id);
    }

    public function cancel(User $user, Order $order): bool
    {
        if (! $this->can($user, 'cancel')) {
            return false;
        }

        if ($order->is_multi_destination) {
            return $this->managesTransferOrigin($user, $order);
        }

        if ($user->hasRole('admin') || $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value) || ! $user->branch_id) {
            return true;
        }

        return (int) $order->branch_id === (int) $user->branch_id
            || ($order->isInterBranch() && (int) $order->customer_id === (int) $user->branch_id);
    }

    public function sendToStock(User $user, Order $order): bool
    {
        return $this->approve($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->can($user, 'delete') && ($order->is_multi_destination
            ? $this->managesTransferOrigin($user, $order)
            : $this->view($user, $order));
    }

    private function managesTransferOrigin(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        $branchId = $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value)
            ? session('active_branch_id', $user->branch_id)
            : $user->branch_id;
        if ($user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value)
            && (int) ($user->province_id ?? $user->branch?->province_id) !== (int) $order->branch?->province_id) {
            return false;
        }

        return $branchId && $branchId !== 'all' && (int) $branchId === (int) $order->branch_id;
    }

    private function participatesInTransfer(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value)) {
            $provinceId = $user->province_id ?? $user->branch?->province_id;

            return $provinceId && ((int) $order->branch?->province_id === (int) $provinceId
                || $order->items()->whereHas('destinationBranch', fn ($branches) => $branches->where('province_id', $provinceId))->exists());
        }

        return $user->branch_id && ((int) $order->branch_id === (int) $user->branch_id
            || $order->items()->where('destination_branch_id', $user->branch_id)->exists());
    }
}
