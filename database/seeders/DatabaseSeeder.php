<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $isProduction = app()->isProduction();
        $allowProductionSeed = filter_var((string) env('ALLOW_PRODUCTION_SEED', 'false'), FILTER_VALIDATE_BOOL);

        if ($isProduction && ! $allowProductionSeed) {
            $this->command?->error('Seeder diblokir di production. Set ALLOW_PRODUCTION_SEED=true untuk melanjutkan.');

            return;
        }

        $this->call([
            RolePermissionSeeder::class,
        ]);

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ],
        );
    }
}
