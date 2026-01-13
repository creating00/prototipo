<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\ClientAuthController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SalePaymentController;
use App\Http\Controllers\Api\ExpenseTypeController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ProviderProductController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\ProvinceController;

Route::get('/provinces', [ProvinceController::class, 'index']);
Route::get('/provinces/{id}', [ProvinceController::class, 'show']);
Route::apiResource('branches', BranchController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('expense-types', ExpenseTypeController::class);
Route::apiResource('discounts', DiscountController::class);
Route::post('/profile/change-password', [ProfileApiController::class, 'updatePassword'])
    ->name('api.profile.password.update')
    ->middleware('web', 'auth');
Route::prefix('inventory')->group(function () {
    Route::get('list', [ProductController::class, 'list']);
    Route::get('by-code/{code}', [ProductController::class, 'findByCode']);
});
Route::apiResource('products', ProductController::class);

Route::get('providers/search', [ProviderController::class, 'search'])->name('providers.search');
Route::get('providers/list-basic', [ProviderController::class, 'listBasic'])->name('providers.list-basic');

Route::apiResource('providers', ProviderController::class);
Route::prefix('providers/{provider}')->group(function () {
    // Obtener lista de productos (usada por tu Select dinámico)
    Route::get('products', [ProviderController::class, 'getProducts'])
        ->name('providers.products.index');

    // Gestión de asociación de productos (ProviderProductController)
    Route::post('products', [ProviderProductController::class, 'store'])
        ->name('providers.products.store');

    Route::get('products/{providerProduct}', [ProviderProductController::class, 'show'])
        ->name('providers.products.show');

    Route::put('products/{providerProduct}', [ProviderProductController::class, 'update'])
        ->name('providers.products.update');
});

Route::get('/clients/search', [ClientController::class, 'search']);
Route::apiResource('clients', ClientController::class);
// Rutas públicas para e-commerce (creación de órdenes)
Route::post('orders/ecommerce', [OrderController::class, 'storeFromEcommerce']);
Route::post('orders/{id}/convert', [OrderController::class, 'convert']);

Route::apiResource('orders', OrderController::class)->except(['store']);
Route::post('orders', [OrderController::class, 'store'])->middleware('auth:sanctum');

Route::apiResource('sales', SaleController::class);
Route::post('sales/{sale}/payments', [SalePaymentController::class, 'store']);

Route::apiResource('payments', PaymentController::class);

Route::get('/invoice/generate/{paymentId}', [InvoiceController::class, 'generate'])
    ->name('invoice.generate');

Route::prefix('client')->group(function () {
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('login', [ClientAuthController::class, 'login']);

    // Cambiar a auth:sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [ClientAuthController::class, 'logout']);
        Route::get('me', [ClientAuthController::class, 'me']);
    });
});
