<?php

use App\Models\User;
use App\View\Components\Adminlte\MenuBuilder;
use Illuminate\Routing\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('catalog menu access matches catalog authorization', function (array $permissions, bool $visible, bool $admin) {
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);
    if ($admin) {
        $user->assignRole(Role::findOrCreate('admin', 'web'));
    }
    $this->actingAs($user);
    $items = collect((new MenuBuilder)->items);
    $catalog = $items->firstWhere('label', 'Catálogos web');
    expect($catalog !== null)->toBe($visible);
    if ($visible) {
        $providerIndex = $items->search(fn ($item) => ($item['label'] ?? '') === 'Proveedores');
        expect($items[$providerIndex + 1]['label'])->toBe('Catálogos web')
            ->and($catalog['href'])->toBe(route('web.provider-catalogs.index'));
    }
})->with([
    'catalog reader' => [['providers.view', 'product_provider_prices.view'], true, false],
    'provider permission only' => [['providers.view'], false, false],
    'price permission only' => [['product_provider_prices.view'], false, false],
    'no permissions' => [[], false, false],
    'administrator' => [[], true, true],
]);

test('catalog menu highlights catalog history and comparison routes only', function (string $routeName, bool $active) {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin', 'web'));
    $this->actingAs($user);
    request()->setRouteResolver(fn () => (new Route('GET', '/', fn () => null))->name($routeName));
    $catalog = collect((new MenuBuilder)->items)->firstWhere('label', 'Catálogos web');
    expect($catalog['active'])->toBe($active);
})->with([
    ['web.provider-catalogs.index', true],
    ['web.provider-catalogs.history', true],
    ['web.provider-catalogs.compare', true],
    ['web.providers.index', false],
]);
