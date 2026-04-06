<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    ProfileController,
    HomeController,
    SaleReceiptController,
};
use App\Http\Controllers\Api\ExpenseImportController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductImportController;
use App\Http\Controllers\Api\ProviderImportController;
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
    AnalyticsWebController,
    BankAccountWebController,
    BankWebController,
    NotificationWebController,
    PriceModificationWebController,
    PromotionImageWebController,
    PromotionWebController,
    RepairAmountWebController
};
use App\Models\Sale;

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
        Route::get('products/template', [ProductImportController::class, 'downloadTemplate'])->name('web.products.template');
        Route::get('providers/template', [ProviderImportController::class, 'downloadTemplate'])->name('web.providers.template');
        Route::get('expenses/template', [ExpenseImportController::class, 'downloadTemplate'])->name('web.expenses.template');

        Route::delete('products/bulk-delete', [ProductWebController::class, 'bulkDestroy'])
            ->name('web.products.bulk-delete');

        Route::get('products/export', [ProductImportController::class, 'export'])->name('web.products.export');
        Route::get('providers/export', [ProviderImportController::class, 'export'])->name('web.providers.export');

        Route::post('products/import', [ProductImportController::class, 'import'])->name('web.products.import');
        Route::post('providers/import', [ProviderImportController::class, 'import'])->name('web.providers.import');
        Route::post('expenses/import', [ExpenseImportController::class, 'import'])->name('web.expenses.import');

        Route::patch('categories/{category}/update-target', [CategoryWebController::class, 'updateTarget'])
            ->name('categories.update-target');

        Route::post('promotions/{promotion}/toggle-status', [PromotionImageWebController::class, 'toggleStatus'])
            ->name('web.promotions.toggle-status');

        Route::delete('promotions/bulk-delete', [PromotionImageWebController::class, 'bulkDestroy'])
            ->name('web.promotions.bulk-delete');

        Route::post('orders/{id}/convert', [OrderController::class, 'convert']);

        // 1. Marcar todas como leídas
        Route::post('notifications/mark-all-read', [NotificationWebController::class, 'markAllAsRead'])
            ->name('web.notifications.mark-all-read');

        // 2. Marcar una específica como leída
        Route::patch('notifications/{id}/mark-read', [NotificationWebController::class, 'markAsRead'])
            ->name('web.notifications.mark-read');

        Route::get('notifications/{id}/go', [NotificationWebController::class, 'readAndRedirect'])
            ->name('web.notifications.read-and-redirect');

        // 3. Ruta resource base
        webResource('notifications', NotificationWebController::class);

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
        webResource('analytics', AnalyticsWebController::class);
        webResource('repair-amounts', RepairAmountWebController::class);
        webResource('banks', BankWebController::class);
        webResource('bank-accounts', BankAccountWebController::class);
        //webResource('promotions', PromotionWebController::class);
        webResource('promotions', PromotionImageWebController::class);
        webResource('notifications', NotificationWebController::class);

        Route::get('/audits', [PriceModificationWebController::class, 'index'])
            ->name('web.price-modifications.index');

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

        Route::get('sales/{id}/details', [SaleWebController::class, 'show'])->name('web.sales.show');
        Route::get('/sales/{sale}/ticket', [SaleReceiptController::class, 'ticket'])
            ->name('sales.ticket');
        Route::get('/sales/{sale}/a4', [SaleReceiptController::class, 'a4'])
            ->name('sales.a4');

        Route::get('/sales/{sale}/ticket-html', function (Sale $sale) {
            return view('pdf.sale_ticket', compact('sale'));
        });

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
