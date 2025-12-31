<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllUsers()
    {
        return User::with(['branch'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getUserById($id): User
    {
        return User::with(['branch'])->findOrFail($id);
    }

    public function createUser(array $data): User
    {
        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data);

        $user->assignRole($role);

        return $user->fresh('roles');
    }

    public function updateUser($id, array $data): User
    {
        $user = $this->getUserById($id);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user->update($data);

        return $user->fresh('roles');
    }

    public function updatePassword($id, string $newPassword): void
    {
        $user = $this->getUserById($id);
        $user->update([
            'password' => Hash::make($newPassword)
        ]);
    }

    public function deleteUser($id): bool
    {
        $user = $this->getUserById($id);
        return $user->delete();
    }
}
