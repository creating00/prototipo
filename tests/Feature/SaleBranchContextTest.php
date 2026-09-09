<?php

use App\Enums\RoleLabel;
use App\Models\Branch;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('sale creation page defaults origin branch to active branch context for provincial admin', function () {
    $province = Province::create([
        'name' => 'Córdoba',
        'api_id' => '14',
        'name_long' => 'Provincia de Córdoba',
    ]);

    $branch1 = Branch::create([
        'province_id' => $province->id,
        'name' => 'General Paz',
        'phone' => '123456',
        'address' => 'Av. General Paz 100',
    ]);

    $branch2 = Branch::create([
        'province_id' => $province->id,
        'name' => 'Sucursal Córdoba',
        'phone' => '654321',
        'address' => 'Colón 500',
    ]);

    Role::findOrCreate(RoleLabel::PROVINCIAL_ADMIN->value);

    // Usuario asignado originalmente a Branch 1 (General Paz)
    $user = User::factory()->create([
        'branch_id' => $branch1->id,
        'province_id' => $province->id,
    ]);
    $user->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    // Simular que el Administrador Provincial selecciona Branch 2 (Sucursal Córdoba) en el Navbar Switcher
    $response = $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch2->id])
        ->get(route('web.sales.create-client'));

    $response->assertStatus(200);

    // Verificar que en el HTML renderizado la opción seleccionada (selected) sea la Sucursal Córdoba (Branch 2)
    $response->assertSee('<option value="' . $branch2->id . '" selected>', false);
});
