<?php

use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\User;
use App\Services\Catalogs\CatalogProcessingService;
use App\Services\Catalogs\ProductData;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

function generalCatalogUser(array $permissions = ['providers.view', 'product_provider_prices.view']): User
{
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function generalCatalogHistory(User $user, string $providerName, array $products, ?Provider $provider = null, ?string $url = null): \App\Models\ProviderCatalogSnapshot
{
    $provider ??= Provider::create(['business_name' => $providerName, 'tax_id' => (string) Str::uuid(), 'catalog_url' => 'https://example.com/catalog']);
    if ($url && ! in_array($url, $provider->catalogUrls(), true)) {
        $provider->update(['catalog_urls' => [...($provider->catalog_urls ?? []), $url]]);
    }
    $preview = ProviderCatalog::updateOrCreate(['provider_id' => $provider->id, 'source_key' => hash('sha256', $url ?? $provider->catalog_url)],
        ['source_url' => $url ?? $provider->catalog_url, 'token' => (string) Str::uuid(), 'products' => array_map(fn ($product) => ProductData::make($product), $products),
            'status' => 'complete', 'requested_at' => now(), 'completed_at' => now()]);

    return app(CatalogProcessingService::class)->process($provider->id, $preview->token, $user->id);
}

test('general search fills one candidate per supplier but leaves ambiguous products unselected', function () {
    $user = generalCatalogUser();
    $a = generalCatalogHistory($user, 'A', [['name' => 'Lenovo T14 16GB', 'price' => '10']]);
    $b = generalCatalogHistory($user, 'B', [['name' => 'Notebook Lenovo T14 16GB', 'price' => '0', 'image' => 'https://cdn.example.com/t14.jpg']]);
    $c = generalCatalogHistory($user, 'C', [['name' => 'Lenovo T14 8GB'], ['name' => 'Lenovo T14 32GB']]);
    $this->actingAs($user)->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $a->products()->first()->id, 'q' => 'Lenovo T14']))
        ->assertOk()->assertJsonCount(3, 'groups')->assertJsonPath('groups.0.provider_id', (string) $a->provider_id)
        ->assertJsonPath('groups.1.selected', (string) $b->products()->first()->id)->assertJsonPath('groups.1.options.0.price', '0')
        ->assertJsonPath('groups.1.options.0.image', 'https://cdn.example.com/t14.jpg')
        ->assertJsonPath('groups.2.provider_id', (string) $c->provider_id)->assertJsonPath('groups.2.selected', null)
        ->assertJsonCount(2, 'groups.2.options')->assertJsonPath('groups.1.requires_review', true)->assertJsonPath('groups.2.requires_review', true);
    $this->get(route('web.provider-catalogs.index'))->assertOk()->assertSee('id="catalog-general-search"', false);
});

test('general search uses latest processed versions per URL and does not treat historical prices as distinct candidates', function () {
    $user = generalCatalogUser();
    $a = generalCatalogHistory($user, 'A', [['name' => 'Lenovo T14']]);
    $old = generalCatalogHistory($user, 'B', [['name' => 'Lenovo T14', 'price' => '10']]);
    $new = generalCatalogHistory($user, 'B', [['name' => 'Lenovo T14', 'price' => '20']], $old->provider);
    // Repeated category publishes the same product data, without inventing equivalence.
    $category = generalCatalogHistory($user, 'B', [['name' => 'Lenovo T14', 'price' => '20']], $old->provider, 'https://example.com/category');
    $old->provider->delete();
    $this->actingAs($user)->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $a->products()->first()->id]))
        ->assertOk()->assertJsonCount(2, 'groups')->assertJsonCount(1, 'groups.1.options')
        ->assertJsonPath('groups.1.options.0.price', '20')->assertJsonPath('groups.1.selected', (string) $category->products()->first()->id);
});

test('general search preserves the chosen historical item and requires all search words in product name or code', function () {
    $user = generalCatalogUser();
    $a = generalCatalogHistory($user, 'A', [['name' => 'Lenovo T14', 'price' => '10']]);
    generalCatalogHistory($user, 'A', [['name' => 'Lenovo T14', 'price' => '20']], $a->provider);
    generalCatalogHistory($user, 'B', [['name' => 'Lenovo T15'], ['name' => 'T14', 'code' => 'LENOVO-X']]);
    generalCatalogHistory($user, 'Lenovo T14', [['name' => 'Cable HDMI']]);
    $id = $a->products()->first()->id;
    $this->actingAs($user)->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $id, 'q' => 'Lenovo T14']))
        ->assertOk()->assertJsonCount(2, 'groups')->assertJsonPath('groups.0.selected', (string) $id)
        ->assertJsonPath('groups.0.options.0.price', '10')->assertJsonCount(1, 'groups.1.options')->assertJsonPath('groups.1.options.0.code', 'LENOVO-X');
});

test('general search reports provider and candidate limits without auto selecting truncated results', function () {
    $user = generalCatalogUser();
    $a = generalCatalogHistory($user, 'A', [['name' => 'Lenovo T14']]);
    $products = [];
    for ($i = 0; $i < 55; $i++) {
        $products[] = ['name' => 'Lenovo T14 variant '.$i];
    }
    generalCatalogHistory($user, 'B', $products);
    foreach (range(1, 8) as $i) {
        generalCatalogHistory($user, 'C'.$i, [['name' => 'Lenovo T14']]);
    }
    $this->actingAs($user)->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $a->products()->first()->id, 'q' => 'Lenovo T14']))
        ->assertOk()->assertJsonCount(8, 'groups')->assertJsonPath('providers_truncated', true)
        ->assertJsonCount(20, 'groups.1.options')->assertJsonPath('groups.1.has_more', true)->assertJsonPath('groups.1.selected', null);
});

test('general search treats SQL wildcard characters literally and keeps absent fields absent', function () {
    $user = generalCatalogUser();
    $a = generalCatalogHistory($user, 'A', [['name' => '100%_X']]);
    generalCatalogHistory($user, 'B', [['name' => '100%_X']]);
    generalCatalogHistory($user, 'C', [['name' => '10000X']]);
    $this->actingAs($user)->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $a->products()->first()->id, 'q' => '100%_X']))
        ->assertOk()->assertJsonCount(2, 'groups')->assertJsonPath('groups.1.options.0.price', null)->assertJsonPath('groups.1.options.0.currency', null);
});

test('general search enforces access and rejects invalid or removed selections', function () {
    $user = generalCatalogUser();
    $a = generalCatalogHistory($user, 'A', [['name' => 'Lenovo T14']]);
    $id = $a->products()->first()->id;
    $this->actingAs(generalCatalogUser(['providers.view']))->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $id]))->assertForbidden();
    $this->actingAs($user)->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => 999999]))->assertNotFound();
    $this->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => $id, 'q' => str_repeat('a', 101)]))->assertUnprocessable();
    $this->getJson(route('web.provider-catalogs.suggestions', ['entry_id' => [$id]]))->assertUnprocessable();
});
