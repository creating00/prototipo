<?php

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleLabel::cases() as $roleEnum) {
        Role::findOrCreate($roleEnum->value);
    }
    Role::findOrCreate('admin');

    $this->provinceId = DB::table('provinces')->insertGetId([
        'api_id' => '10',
        'name' => 'Cordoba',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->provincialAdmin = User::factory()->create([
        'province_id' => $this->provinceId,
    ]);
    $this->provincialAdmin->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);

    $this->regularAdmin = User::factory()->create();
    $this->regularAdmin->assignRole('admin');
});

test('only provincial_admin can access user index page', function () {
    // Regular admin denied
    $this->actingAs($this->regularAdmin);
    $response = $this->get(route('web.users.index'));
    $response->assertStatus(403);

    // Provincial admin allowed
    $this->actingAs($this->provincialAdmin);
    $response = $this->get(route('web.users.index'));
    $response->assertStatus(200);
});

test('only provincial_admin can create users', function () {
    // Regular admin attempt
    $this->actingAs($this->regularAdmin);
    $response = $this->postJson(route('web.users.store'), [
        'name' => 'New Test User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);
    $response->assertStatus(403);

    // Provincial admin attempt
    $this->actingAs($this->provincialAdmin);
    $response = $this->post(route('web.users.store'), [
        'name' => 'New Test User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);
    $response->assertRedirect(route('web.users.index'));
    expect(User::where('email', 'newuser@test.com')->exists())->toBeTrue();
});

test('only provincial_admin can delete users', function () {
    $targetUser = User::factory()->create();

    // Regular admin attempt
    $this->actingAs($this->regularAdmin);
    $response = $this->delete(route('web.users.destroy', $targetUser->id));
    $response->assertStatus(403);
    expect(User::find($targetUser->id))->not->toBeNull();

    // Provincial admin attempt
    $this->actingAs($this->provincialAdmin);
    $response = $this->delete(route('web.users.destroy', $targetUser->id));
    $response->assertRedirect(route('web.users.index'));
    expect(User::find($targetUser->id))->toBeNull();
});
