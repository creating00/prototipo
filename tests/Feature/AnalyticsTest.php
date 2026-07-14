<?php

use App\Models\{Branch, Category, Product, ProductBranch, ProductBranchPrice, Sale, SaleItem, Payment, Expense, BankAccount, Bank, User};
use App\Services\AnalyticsService;
use App\Enums\{CurrencyType, PriceType, PaymentType, ProductStatus};
use Carbon\Carbon;

test('analytics service methods work correctly and calculate correct values', function () {
    // 1. Create a user, province, branch, bank, bank account, and category
    $user = User::factory()->create();
    $province = \Illuminate\Support\Facades\DB::table('provinces')->insertGetId([
        'api_id' => '12',
        'name' => 'Province Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $branch = Branch::create(['name' => 'Branch Test', 'address' => 'Test Address', 'province_id' => $province]);
    $bank = Bank::create(['name' => 'Bank Test']);
    $bankAccount = BankAccount::create([
        'user_id' => $user->id,
        'bank_id' => $bank->id,
        'alias' => 'TestAlias',
        'account_number' => '12345678',
        'cbu' => '1111111111111111111111'
    ]);
    $category = Category::create(['name' => 'Category Test']);

    // 2. Create products with prices
    $product1 = Product::create(['name' => 'Product 1', 'category_id' => $category->id, 'code' => 'P1']);
    $pb1 = ProductBranch::create([
        'product_id' => $product1->id,
        'branch_id' => $branch->id,
        'stock' => 50,
        'low_stock_threshold' => 10,
        'status' => ProductStatus::Available
    ]);
    ProductBranchPrice::create([
        'product_branch_id' => $pb1->id,
        'type' => PriceType::PURCHASE,
        'amount' => 100.0,
        'currency' => CurrencyType::ARS
    ]);

    $product2 = Product::create(['name' => 'Product 2', 'category_id' => $category->id, 'code' => 'P2']);
    $pb2 = ProductBranch::create([
        'product_id' => $product2->id,
        'branch_id' => $branch->id,
        'stock' => 50,
        'low_stock_threshold' => 10,
        'status' => ProductStatus::Available
    ]);
    ProductBranchPrice::create([
        'product_branch_id' => $pb2->id,
        'type' => PriceType::PURCHASE,
        'amount' => 200.0,
        'currency' => CurrencyType::ARS
    ]);

    // 3. Create sales and payments
    $sale = Sale::create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'internal_number' => 'SALE-001',
        'sale_type' => 1,
        'status' => 1,
        'amount_received' => 1000.0,
        'change_returned' => 0.0,
        'remaining_balance' => 0.0,
        'total_amount' => 1000.0,
        'customer_id' => $user->id,
        'customer_type' => User::class,
        'sale_date' => now(),
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product1->id,
        'quantity' => 2,
        'unit_price' => 200.0,
        'subtotal' => 400.0,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product2->id,
        'quantity' => 3,
        'unit_price' => 200.0,
        'subtotal' => 600.0,
    ]);

    // Payment for sale (transfer to bankAccount)
    Payment::create([
        'paymentable_type' => Sale::class,
        'paymentable_id' => $sale->id,
        'user_id' => $user->id,
        'payment_type' => PaymentType::Transfer->value,
        'payment_method_id' => $bankAccount->id,
        'payment_method_type' => BankAccount::class,
        'amount' => 1000.0,
        'currency' => CurrencyType::ARS->value,
        'branch_id' => $branch->id,
    ]);

    // 4. Create expense
    Expense::create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'amount' => 300.0,
        'currency' => CurrencyType::ARS->value,
        'payment_type' => PaymentType::Transfer->value,
        'bank_account_id' => $bankAccount->id,
        'date' => today(),
    ]);

    // Initialize AnalyticsService
    $service = app(AnalyticsService::class);

    $filters = [
        'branch_id' => $branch->id,
        'start_date' => today()->startOfDay()->toDateString(),
        'end_date' => today()->endOfDay()->toDateString(),
    ];

    // Assert getBranchStats calculations
    $salesTotal = $service->getBranchStats($filters);
    
    // We expect filteredSales to have ars: 1000, usd: 0
    expect($salesTotal['filteredSales']['ars'])->toBe(1000.0);
    expect($salesTotal['filteredSales']['usd'])->toBe(0.0);

    // We expect filteredExpenses to have ars: 300, usd: 0
    expect($salesTotal['filteredExpenses']['ars'])->toBe(300.0);
    expect($salesTotal['filteredExpenses']['usd'])->toBe(0.0);

    // We expect bankAccountBoxes to have sales_ars: 1000, expenses_ars: 300, net_ars: 700
    $accountBox = collect($salesTotal['bankAccountBoxes'])->firstWhere('account.id', $bankAccount->id);
    expect($accountBox)->not->toBeNull();
    expect($accountBox['sales_ars'])->toBe(1000.0);
    expect($accountBox['expenses_ars'])->toBe(300.0);
    expect($accountBox['net_ars'])->toBe(700.0);

    // We expect real_profit_month inside resultBoxes to be:
    // Sales Month (1000) - COGS Month (Product1 COGS: 100 * 2 = 200, Product2 COGS: 200 * 3 = 600, total = 800) = 200
    expect($salesTotal['resultBoxes']['real_profit_month']['number'])->toBe(200.0);
});
