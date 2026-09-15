<?php

use App\Enums\CurrencyType;
use App\Enums\PaymentType;
use App\Enums\PriceType;
use App\Enums\ProductStatus;
use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductBranchPrice;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();
    \Illuminate\Support\Facades\Http::fake(['dolarapi.com/*' => \Illuminate\Support\Facades\Http::response(['venta' => 1000])]);
    Role::firstOrCreate(['name' => RoleLabel::PROVINCIAL_ADMIN->value]);
    $province = DB::table('provinces')->insertGetId(['api_id' => '14', 'name' => 'Cordoba']);
    $otherProvince = DB::table('provinces')->insertGetId(['api_id' => '82', 'name' => 'Santa Fe']);
    $this->branches = collect([
        Branch::create(['name' => 'Centro', 'province_id' => $province]),
        Branch::create(['name' => 'General Paz', 'province_id' => $province]),
        Branch::create(['name' => 'Rosario', 'province_id' => $otherProvince]),
    ]);
    $this->user = User::factory()->create(['province_id' => $province, 'branch_id' => $this->branches[0]->id]);
    $this->user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);
    $this->actingAs($this->user);
    $category = Category::create(['name' => 'Categoria']);
    $product = Product::create(['name' => 'Producto compartido', 'code' => 'CONTEXT', 'category_id' => $category->id]);
    foreach ($this->branches as $index => $branch) {
        $amount = ($index + 1) * 1000;
        $pb = ProductBranch::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'stock' => 2, 'low_stock_threshold' => 5, 'status' => ProductStatus::Available]);
        ProductBranchPrice::create(['product_branch_id' => $pb->id, 'type' => PriceType::PURCHASE, 'amount' => ($index + 1) * 100, 'currency' => CurrencyType::ARS]);
        $sale = Sale::create(['branch_id' => $branch->id, 'user_id' => $this->user->id, 'internal_number' => 'CONTEXT-'.$index, 'sale_type' => 1, 'status' => 1, 'total_amount' => $amount, 'amount_received' => $amount, 'change_returned' => 0, 'remaining_balance' => 0, 'customer_id' => $this->user->id, 'customer_type' => User::class, 'sale_date' => now()]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => $amount, 'subtotal' => $amount]);
        Payment::create(['paymentable_type' => Sale::class, 'paymentable_id' => $sale->id, 'user_id' => $this->user->id, 'payment_type' => PaymentType::Cash->value, 'amount' => $amount, 'currency' => CurrencyType::ARS->value, 'branch_id' => $branch->id]);
        Expense::create(['user_id' => $this->user->id, 'branch_id' => $branch->id, 'amount' => $amount / 10, 'currency' => CurrencyType::ARS->value, 'payment_type' => PaymentType::Cash->value, 'date' => today()]);
    }
});

test('header switches discard stale report branches and calculate the selected provincial scope', function () {
    foreach (['all', (string) $this->branches[1]->id, (string) $this->branches[0]->id, 'all'] as $context) {
        $previous = route('web.analytics.index', ['branch_id' => $this->branches[0]->id, 'start_date' => today()->toDateString()]);
        $switch = $this->from($previous)->post(route('web.branch-context.switch'), ['branch_id' => $context]);
        $url = route('web.analytics.index', ['start_date' => today()->toDateString()]);
        $switch->assertRedirect($url);
        $response = $this->get($url)->assertOk();
        $amount = $context === 'all' ? 3000.0 : ($context === (string) $this->branches[1]->id ? 2000.0 : 1000.0);
        $response->assertViewHas('filteredSales', fn ($data) => $data['ars'] === $amount);
        $response->assertViewHas('filteredExpenses', fn ($data) => $data['ars'] === $amount / 10);
        $response->assertViewHas('filteredBalance', fn ($data) => $data['ars'] === $amount * 0.9);
        $response->assertViewHas('resultBoxes', fn ($data) => $data['real_profit_month']['number'] === $amount * 0.9);
        $response->assertViewHas('infoboxes', fn ($data) => (int) $data['sales_today']['number'] === ($context === 'all' ? 2 : 1));
        $response->assertViewHas('stockReport', fn ($data) => $data->count() === ($context === 'all' ? 2 : 1));
        $response->assertViewHas('chartData', fn ($data) => (float) $data['monthly']['payments']->sum() === $amount);
        $response->assertViewHas('branches', fn ($data) => ! $data->has($this->branches[2]->id));
    }
});

test('report filters cannot escape the province or override a specific header context', function () {
    $this->withSession(['active_branch_id' => 'all', 'analytics_branch_id' => $this->branches[2]->id]);
    $this->get(route('web.analytics.index'))->assertOk()->assertViewHas('filteredSales', fn ($data) => $data['ars'] === 3000.0);
    $this->get(route('web.analytics.index', ['branch_id' => $this->branches[2]->id]))->assertForbidden();
    $this->get(route('web.analytics.index', ['branch_id' => $this->branches[1]->id]))->assertOk()->assertViewHas('filteredSales', fn ($data) => $data['ars'] === 2000.0);
    $this->withSession(['active_branch_id' => $this->branches[1]->id]);
    $this->get(route('web.analytics.index', ['branch_id' => 'all']))->assertOk()->assertViewHas('filteredSales', fn ($data) => $data['ars'] === 2000.0);
});

test('provincial analytics with no accessible branches returns empty statistics', function () {
    $this->user->update(['province_id' => null, 'branch_id' => null]);
    $this->withSession(['active_branch_id' => 'all']);
    $this->get(route('web.analytics.index'))->assertOk()->assertViewHas('filteredSales', fn ($data) => $data['ars'] === 0.0);
});
