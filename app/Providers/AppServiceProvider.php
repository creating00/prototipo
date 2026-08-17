<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

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

            return $user?->can($permission) ?? false;
        });

        Order::observe(OrderObserver::class);
    }
}
