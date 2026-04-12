<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $settings = [
            ['key' => 'bank_1_name', 'value' => 'BCA', 'label' => 'Bank 1 - Nama'],
            ['key' => 'bank_1_account', 'value' => '6830717372', 'label' => 'Bank 1 - No. Rekening'],
            ['key' => 'bank_1_holder', 'value' => 'Arip Hidayat', 'label' => 'Bank 1 - Atas Nama'],

            ['key' => 'bank_2_name', 'value' => 'BRI', 'label' => 'Bank 2 - Nama'],
            ['key' => 'bank_2_account', 'value' => '325201027474537', 'label' => 'Bank 2 - No. Rekening'],
            ['key' => 'bank_2_holder', 'value' => 'Arip Hidayat', 'label' => 'Bank 2 - Atas Nama'],

            ['key' => 'bank_3_name', 'value' => 'DANA', 'label' => 'E-Wallet - Nama'],
            ['key' => 'bank_3_account', 'value' => '085718021362', 'label' => 'E-Wallet - Nomor'],
            ['key' => 'bank_3_holder', 'value' => 'Arip Hidayat', 'label' => 'E-Wallet - Atas Nama'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => 'bank',
                    'type' => 'string',
                    'label' => $setting['label'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        $now = now();

        $settings = [
            ['key' => 'bank_1_name', 'value' => 'BCA', 'label' => 'Bank 1 - Nama'],
            ['key' => 'bank_1_account', 'value' => '1234567890', 'label' => 'Bank 1 - No. Rekening'],
            ['key' => 'bank_1_holder', 'value' => 'Arip Hidayat', 'label' => 'Bank 1 - Atas Nama'],

            ['key' => 'bank_2_name', 'value' => 'Mandiri', 'label' => 'Bank 2 - Nama'],
            ['key' => 'bank_2_account', 'value' => '0987654321', 'label' => 'Bank 2 - No. Rekening'],
            ['key' => 'bank_2_holder', 'value' => 'Arip Hidayat', 'label' => 'Bank 2 - Atas Nama'],

            ['key' => 'bank_3_name', 'value' => 'BRI', 'label' => 'Bank 3 - Nama'],
            ['key' => 'bank_3_account', 'value' => '', 'label' => 'Bank 3 - No. Rekening'],
            ['key' => 'bank_3_holder', 'value' => '', 'label' => 'Bank 3 - Atas Nama'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => 'bank',
                    'type' => 'string',
                    'label' => $setting['label'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
};
