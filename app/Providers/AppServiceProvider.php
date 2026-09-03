<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Blade::componentNamespace(
        //     'App\\View\\Components\\AdminLte',
        //     'adminlte'
        // );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability, array $arguments = []) {
            $target = $arguments[0] ?? null;

            $isOrderTarget = $target === \App\Models\Order::class
                || $target instanceof \App\Models\Order
                || str_starts_with($ability, 'orders.');

            if ($isOrderTarget && in_array($ability, ['create_branch', 'createBranch', 'orders.create_branch'], true)) {
                return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
            }

            // Bloqueo centralizado de ABM en modo consolidado ("all") para módulos no permitidos
            if (session('active_branch_id') === 'all') {
                $allowedPrefixes = ['products.', 'product_branches.', 'product_branch_prices.', 'analytics.', 'reports.'];
                $allowedTargets = [
                    \App\Models\Product::class,
                    \App\Models\ProductBranch::class,
                    \App\Models\ProductBranchPrice::class,
                ];

                $isAllowedModule = false;
                foreach ($allowedPrefixes as $prefix) {
                    if (str_starts_with($ability, $prefix)) {
                        $isAllowedModule = true;
                        break;
                    }
                }
                if (! $isAllowedModule && in_array($target, $allowedTargets, true)) {
                    $isAllowedModule = true;
                }
                if (! $isAllowedModule && ($target instanceof \App\Models\Product || $target instanceof \App\Models\ProductBranch || $target instanceof \App\Models\ProductBranchPrice)) {
                    $isAllowedModule = true;
                }

                $mutationAbilities = ['create', 'update', 'delete', 'store', 'edit', 'destroy', 'approve', 'cancel', 'adjust', 'refund', 'moderate'];
                $isMutation = false;
                foreach ($mutationAbilities as $mut) {
                    if (str_ends_with($ability, ".{$mut}") || $ability === $mut) {
                        $isMutation = true;
                        break;
                    }
                }

                if ($isMutation && ! $isAllowedModule) {
                    return false;
                }
            }

            $isRepairAmountTarget = $target === \App\Models\RepairAmount::class
                || $target instanceof \App\Models\RepairAmount
                || str_starts_with($ability, 'repair_amounts.');

            if ($isRepairAmountTarget && in_array($ability, ['create', 'update', 'delete', 'repair_amounts.create', 'repair_amounts.update', 'repair_amounts.delete'])) {
                return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
            }

            $isUserTarget = $target === \App\Models\User::class
                || $target instanceof \App\Models\User
                || str_starts_with($ability, 'users.');

            if ($isUserTarget) {
                return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
            }

            if ($user->hasRole('admin') || $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value)) {
                return true;
            }
        });
        /**
         * Reemplaza: @can('update', Model::class)
         */
        Blade::if('canAction', function (string $ability, string $modelClass) {
            return Gate::check($ability, $modelClass);
        });

        /**
         * Reemplaza: @can('update', $model)
         */
        Blade::if('canRow', function (string $ability, $model) {
            return Gate::check($ability, $model);
        });

        /**
         * Permisos planos (Spatie)
         * Reemplaza: @can('products.update')
         */
        Blade::if('canResource', function (string $permission): bool {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (! $user) {
                return false;
            }

            if (session('active_branch_id') === 'all') {
                $allowedPrefixes = ['products.', 'product_branches.', 'product_branch_prices.', 'analytics.', 'reports.'];
                $isAllowedModule = false;
                foreach ($allowedPrefixes as $prefix) {
                    if (str_starts_with($permission, $prefix)) {
                        $isAllowedModule = true;
                        break;
                    }
                }

                $isMutation = str_ends_with($permission, '.create')
                    || str_ends_with($permission, '.update')
                    || str_ends_with($permission, '.delete')
                    || str_ends_with($permission, '.cancel')
                    || str_ends_with($permission, '.approve')
                    || str_ends_with($permission, '.adjust')
                    || str_ends_with($permission, '.refund');

                if ($isMutation && ! $isAllowedModule) {
                    return false;
                }
            }

            return $user->can($permission);
        });

        Order::observe(OrderObserver::class);
    }
}
