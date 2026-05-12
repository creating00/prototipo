<?php

use Illuminate\Support\Facades\Route;

/**
 * Helper para definir recursos con extras.
 *
 * @param string $prefix      Prefijo de la ruta (ej: 'orders', 'sales')
 * @param string $controller  Controlador asociado
 * @param array  $extras      Rutas adicionales ['uri' => 'method']
 */
function resourceWithExtras(string $prefix, string $controller, array $extras = [])
{
    Route::prefix($prefix)->as("web.$prefix.")->group(function () use ($prefix, $controller, $extras) {
        // Extras
        foreach ($extras as $uri => $method) {
            Route::get($uri, [$controller, $method])->name($uri);
        }

        // CRUD básico
        Route::get('/', [$controller, 'index'])->name('index');
        Route::post('/', [$controller, 'store'])->name('store');
        Route::get('{' . $prefix . '}/edit', [$controller, 'edit'])->name('edit');
        Route::put('{' . $prefix . '}', [$controller, 'update'])->name('update');
        Route::delete('{' . $prefix . '}', [$controller, 'destroy'])->name('destroy');
    });
}

function webResource(string $name, string $controller)
{
    Route::resource($name, $controller)->names("web.$name");
}
