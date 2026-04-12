<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $settings = [
            [
                'key' => 'shipping_cost_per_item',
                'value' => '5000',
                'group' => 'general',
                'type' => 'string',
                'label' => 'Ongkir per Item (Rp)',
            ],
            [
                'key' => 'store_maps_url',
                'value' => 'https://maps.app.goo.gl/X7gjCXMrEXZn5KB8A?g_st=iw',
                'group' => 'store',
                'type' => 'string',
                'label' => 'URL Google Maps',
            ],
            [
                'key' => 'store_phone',
                'value' => '085718021362',
                'group' => 'store',
                'type' => 'string',
                'label' => 'No. WhatsApp',
            ],
            [
                'key' => 'store_email',
                'value' => 'hselectric90@gmail.com',
                'group' => 'store',
                'type' => 'string',
                'label' => 'Email Toko',
            ],
            [
                'key' => 'hours_weekday',
                'value' => '09:00 - 20:00',
                'group' => 'hours',
                'type' => 'string',
                'label' => 'Jam Buka (Senin–Jumat)',
            ],
            [
                'key' => 'hours_saturday',
                'value' => '09:00 - 20:00',
                'group' => 'hours',
                'type' => 'string',
                'label' => 'Jam Buka (Sabtu)',
            ],
            [
                'key' => 'hours_sunday',
                'value' => '09:00 - 20:00',
                'group' => 'hours',
                'type' => 'string',
                'label' => 'Jam Buka (Minggu)',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            );
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('system_settings')
            ->whereIn('key', ['shipping_cost_per_item', 'store_maps_url'])
            ->delete();

        $settings = [
            [
                'key' => 'store_phone',
                'value' => '08123456789',
                'group' => 'store',
                'type' => 'string',
                'label' => 'No. WhatsApp',
            ],
            [
                'key' => 'store_email',
                'value' => 'admin@tokolistrik.com',
                'group' => 'store',
                'type' => 'string',
                'label' => 'Email Toko',
            ],
            [
                'key' => 'hours_weekday',
                'value' => '08:00 - 17:00',
                'group' => 'hours',
                'type' => 'string',
                'label' => 'Jam Buka (Senin–Jumat)',
            ],
            [
                'key' => 'hours_saturday',
                'value' => '08:00 - 15:00',
                'group' => 'hours',
                'type' => 'string',
                'label' => 'Jam Buka (Sabtu)',
            ],
            [
                'key' => 'hours_sunday',
                'value' => 'Tutup',
                'group' => 'hours',
                'type' => 'string',
                'label' => 'Jam Buka (Minggu)',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            );
        }
    }
};
