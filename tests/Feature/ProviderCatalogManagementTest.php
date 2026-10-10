<?php

use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function catalogManagementUser(?string $role): User
{
    $user = User::factory()->create();
    $permissions = ['providers.view', 'providers.create', 'providers.update', 'providers.delete', 'product_provider_prices.view'];
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);
    if ($role) {
        $user->assignRole(Role::findOrCreate($role, 'web'));
    }

    return $user;
}

function catalogManagementProvider(): Provider
{
    return Provider::create(['business_name' => 'Proveedor', 'tax_id' => (string) Str::uuid(),
        'catalog_url' => 'https://www.i2cmayorista.com/repuestos/', 'catalog_urls' => ['https://www.i2cmayorista.com/search/?q=modulos']]);
}

test('catalog management controls and cleanup require an administrator role even with all permissions', function (?string $role, bool $allowed) {
    $user = catalogManagementUser($role);
    $provider = catalogManagementProvider();
    $preview = ProviderCatalog::create(['provider_id' => $provider->id, 'source_url' => $provider->catalog_url,
        'token' => (string) Str::uuid(), 'status' => 'queued', 'requested_at' => now(), 'products' => []]);
    $this->actingAs($user);
    $page = $this->get(route('web.provider-catalogs.index'))->assertOk();
    $form = $this->get(route('web.providers.edit', $provider))->assertOk();
    if ($allowed) {
        $page->assertSee('Editar URL')->assertSee('data-catalog-clear-form', false);
        $form->assertSee('name="catalog_url"', false)->assertSee('name="catalog_urls_text"', false);
        $this->delete(route('web.provider-catalogs.clear'), ['confirm_clear' => '1'])->assertRedirect();
        expect(ProviderCatalog::count())->toBe(0);
    } else {
        $page->assertDontSee('Editar URL')->assertDontSee('data-catalog-clear-form', false)->assertSee('Consultar ahora');
        $form->assertDontSee('name="catalog_url"', false)->assertDontSee('name="catalog_urls_text"', false);
        $this->deleteJson(route('web.provider-catalogs.clear'), ['confirm_clear' => '1'])->assertForbidden();
        expect($preview->fresh())->not->toBeNull();
    }
})->with([
    'admin' => ['admin', true],
    'provincial admin' => ['provincial_admin', true],
    'seller with all permissions' => ['seller', false],
    'permissions without role' => [null, false],
]);

test('non administrators cannot submit catalog URL fields through web or API', function (string $field, mixed $value) {
    $provider = catalogManagementProvider();
    $this->actingAs(catalogManagementUser('seller'));
    $data = ['business_name' => 'Changed', 'tax_id' => $provider->tax_id, $field => $value];
    $this->putJson(route('web.providers.update', $provider), $data)->assertForbidden();
    $this->putJson('/api/providers/'.$provider->id, $data)->assertForbidden();
    $data['tax_id'] = (string) Str::uuid();
    $this->postJson(route('web.providers.store'), $data)->assertForbidden();
    $this->postJson('/api/providers', $data)->assertForbidden();
    expect($provider->fresh()->business_name)->toBe('Proveedor')
        ->and($provider->fresh()->catalog_url)->toBe('https://www.i2cmayorista.com/repuestos/')
        ->and(Provider::count())->toBe(1);
})->with([
    ['catalog_url', 'https://example.com/catalog'],
    ['catalog_urls', ['https://example.com/category']],
    ['catalog_urls_text', 'https://example.com/category'],
    ['catalog_url', null],
    ['catalog_urls', []],
]);

test('both administrator roles can create and update catalog URLs through web and API', function (string $role) {
    $provider = catalogManagementProvider();
    $this->actingAs(catalogManagementUser($role));
    $data = ['business_name' => 'Changed', 'tax_id' => $provider->tax_id,
        'catalog_url' => 'https://example.com/catalog', 'catalog_urls_text' => 'https://example.com/category'];
    $this->put(route('web.providers.update', $provider), $data)->assertRedirect()->assertSessionHasNoErrors();
    expect($provider->fresh()->catalog_urls)->toBe(['https://example.com/category']);
    $data['catalog_url'] = 'https://example.com/new';
    $this->putJson('/api/providers/'.$provider->id, $data)->assertOk();
    expect($provider->fresh()->catalog_url)->toBe('https://example.com/new');
    $data['tax_id'] = (string) Str::uuid();
    $this->post(route('web.providers.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
    $data['tax_id'] = (string) Str::uuid();
    $this->postJson('/api/providers', $data)->assertCreated();
    expect(Provider::count())->toBe(3);
})->with(['admin', 'provincial_admin']);

test('editing ordinary provider data keeps catalog URLs for non administrators', function () {
    $provider = catalogManagementProvider();
    $this->actingAs(catalogManagementUser('seller'));
    $this->put(route('web.providers.update', $provider), ['business_name' => 'Changed', 'tax_id' => $provider->tax_id])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($provider->fresh()->business_name)->toBe('Changed')
        ->and($provider->fresh()->catalog_url)->toBe('https://www.i2cmayorista.com/repuestos/')
        ->and($provider->fresh()->catalog_urls)->toBe(['https://www.i2cmayorista.com/search/?q=modulos']);
});

test('anonymous API requests cannot write catalog URLs', function () {
    $provider = catalogManagementProvider();
    $data = ['business_name' => 'Changed', 'tax_id' => $provider->tax_id, 'catalog_url' => null];
    $this->putJson('/api/providers/'.$provider->id, $data)->assertForbidden();
    $this->postJson('/api/providers', $data)->assertForbidden();
    expect($provider->fresh()->catalog_url)->not->toBeNull()->and(Provider::count())->toBe(1);
});
