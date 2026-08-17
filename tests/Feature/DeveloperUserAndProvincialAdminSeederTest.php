<?php

use App\Enums\RoleLabel;
use App\Models\Province;
use App\Models\User;
use App\Services\User\UserDataTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleLabel::cases() as $roleEnum) {
        Role::findOrCreate($roleEnum->value);
    }
    Role::findOrCreate('admin');

    DB::table('provinces')->insert([
        'api_id' => '10',
        'name' => 'Córdoba',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('migration registers daniel tecnonauta and developer support user correctly', function () {
    $migration = require database_path('migrations/2026_08_17_110000_create_provincial_admin_and_developer_users.php');
    $migration->up();

    // 1. Verify Daniel Tecnonauta
    $daniel = User::where('email', 'administradorsedecordoba@nauta.com')->first();
    expect($daniel)->not->toBeNull();
    expect($daniel->name)->toBe('Daniel Tecnonauta');
    expect($daniel->hasRole(RoleLabel::PROVINCIAL_ADMIN->value))->toBeTrue();
    expect($daniel->province_id)->not->toBeNull();

    // 2. Verify Soporte Developer
    $soporte = User::where('email', 'soporte@creatingsoft.net')->first();
    expect($soporte)->not->toBeNull();
    expect($soporte->hasRole('admin'))->toBeTrue();
    expect($soporte->hasRole(RoleLabel::PROVINCIAL_ADMIN->value))->toBeTrue();
});

test('developer support user with both roles can access user management pages', function () {
    $migration = require database_path('migrations/2026_08_17_110000_create_provincial_admin_and_developer_users.php');
    $migration->up();

    $soporte = User::where('email', 'soporte@creatingsoft.net')->first();

    $this->actingAs($soporte);
    $response = $this->get(route('web.users.index'));
    $response->assertStatus(200);
});

test('developer support user is hidden from UserDataTableService', function () {
    $migration = require database_path('migrations/2026_08_17_110000_create_provincial_admin_and_developer_users.php');
    $migration->up();

    $dataTableService = new UserDataTableService();
    $usersData = $dataTableService->getAllUsersForDataTable();

    $emails = array_column($usersData, 'email');
    $emailsString = implode(' ', $emails);

    expect($emailsString)->toContain('administradorsedecordoba@nauta.com');
    expect($emailsString)->not->toContain('soporte@creatingsoft.net');
    expect($emailsString)->not->toContain('ecommerce@system.com');
});
