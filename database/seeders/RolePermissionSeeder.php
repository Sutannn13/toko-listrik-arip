<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');

        $isProduction = app()->isProduction();
        $allowProductionSeed = filter_var((string) env('ALLOW_PRODUCTION_SEED', 'false'), FILTER_VALIDATE_BOOL);
        if ($isProduction && ! $allowProductionSeed) {
            $this->command?->error('Seeder diblokir di production. Set ALLOW_PRODUCTION_SEED=true untuk melanjutkan.');

            return;
        }

        $seedEmailFromEnv = env('ADMIN_SEED_EMAIL');
        $seedNameFromEnv = env('ADMIN_SEED_NAME');

        $seedEmail = trim((string) ($seedEmailFromEnv ?? 'arip@tokolistrik.com'));
        $seedName = trim((string) ($seedNameFromEnv ?? 'Admin Arip'));
        $password = (string) env('ADMIN_SEED_PASSWORD', '');

        if ($isProduction && ($seedEmailFromEnv === null || trim((string) $seedEmailFromEnv) === '')) {
            $this->command?->error('ADMIN_SEED_EMAIL wajib diisi eksplisit di production. Seeder dibatalkan.');

            return;
        }

        if ($isProduction && ($seedNameFromEnv === null || trim((string) $seedNameFromEnv) === '')) {
            $this->command?->error('ADMIN_SEED_NAME wajib diisi eksplisit di production. Seeder dibatalkan.');

            return;
        }

        if (! filter_var($seedEmail, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('ADMIN_SEED_EMAIL tidak valid. Seeder dibatalkan.');

            return;
        }

        if ($seedName === '') {
            $this->command?->error('ADMIN_SEED_NAME tidak boleh kosong. Seeder dibatalkan.');

            return;
        }

        if ($password === '' && $isProduction) {
            $this->command?->error('ADMIN_SEED_PASSWORD wajib diisi di production. Seeder dibatalkan.');

            return;
        }

        if ($isProduction && strlen($password) < 12) {
            $this->command?->error('ADMIN_SEED_PASSWORD minimal 12 karakter untuk production. Seeder dibatalkan.');

            return;
        }

        $existingUser = User::query()->where('email', $seedEmail)->first();

        if ($password !== '') {
            $hashedPassword = Hash::make($password);
        } elseif ($existingUser) {
            // Hindari rotasi password tak sengaja saat seeding ulang lokal.
            $hashedPassword = (string) $existingUser->password;
            $this->command?->warn('ADMIN_SEED_PASSWORD kosong. Password user existing dipertahankan.');
        } else {
            $generatedPassword = Str::random(24);
            $hashedPassword = Hash::make($generatedPassword);
            $this->command?->warn('ADMIN_SEED_PASSWORD kosong. Generated password lokal: ' . $generatedPassword);
        }

        $superAdmin = User::query()->updateOrCreate(
            ['email' => $seedEmail],
            [
                'name' => $seedName,
                'password' => $hashedPassword,
            ],
        );

        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }
    }
}
