<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'arip@tokolistrik.com'],
            [
                'name' => 'Super Admin Arip',
                'password' => Hash::make('password123'),
            ],
        );

        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }
    }
}
