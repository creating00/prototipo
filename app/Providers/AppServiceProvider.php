<?php

namespace App\Providers;

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
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
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
    }
}
