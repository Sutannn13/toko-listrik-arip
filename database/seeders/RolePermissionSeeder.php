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

        $password = env('ADMIN_SEED_PASSWORD');

        if (empty($password) && app()->environment('production')) {
            $this->command?->error('ADMIN_SEED_PASSWORD env wajib diisi di production. Seeder dibatalkan.');
            return;
        }

        // Fallback untuk local/testing only — JANGAN pakai di production.
        $password = $password ?: 'local-dev-only-change-me!2026';

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'arip@tokolistrik.com'],
            [
                'name' => 'Admin Arip',
                'password' => Hash::make($password),
            ],
        );

        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }
    }
}
