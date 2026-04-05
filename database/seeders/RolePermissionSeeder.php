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
        // Bikin Role
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $superAdmin = User::create([
            'name' => 'Super Admin Arip',
            'email' => 'arip@tokolistrik.com',
            'password' => Hash::make('password123'), // Ganti ini nanti di production
        ]);

        $superAdmin->assignRole('super-admin');
    }
}
