<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\{
    BranchWebController,
    ProductWebController,
    ClientWebController,
    OrderWebController,
    CategoryWebController
};
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->get('/admin', function () {
    return view('admin.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {

    Route::get('/product', [ProductWebController::class, 'index'])->name('product.index');
    Route::get('/product/create', [ProductWebController::class, 'create'])->name('product.create');
    Route::get('/product/{id}/edit', [ProductWebController::class, 'edit'])->name('product.edit');

    Route::get('/client', [ClientWebController::class, 'index'])->name('client.index');
    Route::get('/client/create', [ClientWebController::class, 'create'])->name('client.create');
    Route::get('/client/{id}/edit', [ClientWebController::class, 'edit'])->name('client.edit');

    Route::get('/order', [OrderWebController::class, 'index'])->name('order.index');
    Route::get('/order/create', [OrderWebController::class, 'create'])->name('order.create');
    Route::get('/order/{id}/edit', [OrderWebController::class, 'edit'])->name('order.edit');

    Route::get('/branch', [BranchWebController::class, 'index'])->name('branch.index');
    Route::get('/branch/create', [BranchWebController::class, 'create'])->name('branch.create');
    Route::get('/branch/{id}/edit', [BranchWebController::class, 'edit'])->name('branch.edit');

    Route::get('/category', [CategoryWebController::class, 'index'])->name('category.index');
    Route::get('/category/create', [CategoryWebController::class, 'create'])->name('category.create');
    Route::get('/category/{id}/edit', [CategoryWebController::class, 'edit'])->name('category.edit');
});

require __DIR__ . '/auth.php';
