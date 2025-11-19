<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\InvoiceController;

Route::apiResource('branches', BranchController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('clients', ClientController::class);
Route::apiResource('orders', OrderController::class);
Route::apiResource('payments', PaymentController::class);
Route::get('/invoice/generate/{paymentId}', [InvoiceController::class, 'generate'])
    ->name('invoice.generate');
