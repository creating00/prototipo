<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    ProfileController,
    HomeController,
};
use App\Http\Controllers\Web\{
    BranchWebController,
    CategoryWebController,
    ClientWebController,
    OrderWebController,
    ProductWebController,
    ProviderWebController,
    SaleWebController,
    ExpenseWebController,
    ExpenseTypeWebController,
    ProviderProductWebController,
    UserWebController,
    ProviderOrderWebController,
    DiscountWebController,
    AnalyticsController
};

// Página principal
Route::get('/', [HomeController::class, 'index']);

// Grupo autenticado y verificado
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

    // Asegúrate de importar el controlador arriba o usar el path completo
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Perfil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('web')->group(function () {
        webResource('categories', CategoryWebController::class);
        webResource('branches', BranchWebController::class);
        webResource('products', ProductWebController::class);
        webResource('providers', ProviderWebController::class);
        webResource('clients', ClientWebController::class);
        webResource('expenses', ExpenseWebController::class);
        webResource('expense-types', ExpenseTypeWebController::class);
        webResource('users', UserWebController::class);
        webResource('discounts', DiscountWebController::class);
        webResource('provider-orders', ProviderOrderWebController::class);
        webResource('analytics', AnalyticsController::class);

        Route::prefix('provider-orders/{id}')->group(function () {
            Route::post('send', [ProviderOrderWebController::class, 'send'])
                ->name('web.provider-orders.send');

            Route::post('receive', [ProviderOrderWebController::class, 'receive'])
                ->name('web.provider-orders.receive');

            Route::get('details', [ProviderOrderWebController::class, 'details'])
                ->name('web.provider-orders.details');
        });

        Route::prefix('providers/{provider}')->group(function () {
            Route::post('products', [ProviderProductWebController::class, 'store'])
                ->name('web.providers.products.store');

            Route::get('provider-products/{providerProduct}/edit', [ProviderProductWebController::class, 'edit'])
                ->name('web.providers.products.edit');

            Route::prefix('provider-products/{providerProduct}')->group(function () {
                Route::get('prices', [ProviderProductWebController::class, 'prices'])->name('provider-products.prices');
                Route::post('prices', [ProviderProductWebController::class, 'storePrice'])->name('provider-products.prices.store');
            });

            Route::put('provider-products/{providerProduct}', [ProviderProductWebController::class, 'update'])
                ->name('web.providers.products.update');
        });

        Route::get('orders/purchases', [OrderWebController::class, 'purchases'])->name('web.orders.purchases');
        Route::get('orders/purchases/{id}/details', [OrderWebController::class, 'purchaseDetails'])
            ->name('web.orders.purchases.details');
        Route::get('orders/{id}/details', [OrderWebController::class, 'show'])->name('web.orders.show');
        Route::post('orders/{id}/receive', [OrderWebController::class, 'receive'])->name('web.orders.receive');

        resourceWithExtras('orders', OrderWebController::class, [
            'create-client' => 'createClient',
            'create-branch' => 'createBranch',
        ]);

        resourceWithExtras('sales', SaleWebController::class, [
            'create-client' => 'createClient',
            'create-branch' => 'createBranch',
            'export'        => 'export',
        ]);
    });
});

// Rutas de prueba (solo en local)
if (app()->environment('local')) {
    Route::get('/test-datatable', fn() => view('test-datatable'));
    Route::post('/form-test', fn() => back()->with('success', 'Formulario recibido correctamente'))->name('form.test');
    Route::get('/test-view', fn() => view('order._item_row'));
    Route::get('/debug-views', fn() => dd(scandir(resource_path('views/order'))));
}

require __DIR__ . '/auth.php';
