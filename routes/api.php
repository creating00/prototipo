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

use App\Http\Controllers\ApiProductImportController;

Route::apiResource('branches', BranchController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);

Route::get('/clients/search', [ClientController::class, 'search']);
Route::apiResource('clients', ClientController::class);
Route::apiResource('orders', OrderController::class);
Route::apiResource('payments', PaymentController::class);
Route::get('/invoice/generate/{paymentId}', [InvoiceController::class, 'generate'])
    ->name('invoice.generate');

Route::prefix('client')->group(function () {
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('login', [ClientAuthController::class, 'login']);

    // Usamos la guard 'client' que apunta a ClientAccount
    Route::middleware('auth:client')->group(function () {
        Route::post('logout', [ClientAuthController::class, 'logout']);
        Route::get('me', [ClientAuthController::class, 'me']);
    });
});

Route::prefix('api-import')->group(function () {
    Route::get('/check', [ApiProductImportController::class, 'checkApi']);
    Route::post('/products', [ApiProductImportController::class, 'importFromApi']);
});
