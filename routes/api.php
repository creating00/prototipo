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

Route::apiResource('branches', BranchController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('expense-types', ExpenseTypeController::class);

Route::prefix('inventory')->group(function () {
    Route::get('list', [ProductController::class, 'list']);
    Route::get('by-code/{code}', [ProductController::class, 'findByCode']);
});

Route::apiResource('products', ProductController::class);

Route::get('/clients/search', [ClientController::class, 'search']);
Route::apiResource('clients', ClientController::class);
// Rutas públicas para e-commerce (creación de órdenes)
Route::post('orders/ecommerce', [OrderController::class, 'storeFromEcommerce']);

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
