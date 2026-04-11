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
                'key' => 'social_instagram_url',
                'value' => 'https://instagram.com/tokohselectric',
                'group' => 'store',
                'type' => 'string',
                'label' => 'URL Instagram',
            ],
            [
                'key' => 'social_facebook_url',
                'value' => 'https://facebook.com/tokohselectric',
                'group' => 'store',
                'type' => 'string',
                'label' => 'URL Facebook',
            ],
            [
                'key' => 'social_tiktok_url',
                'value' => 'https://www.tiktok.com/@tokohselectric',
                'group' => 'store',
                'type' => 'string',
                'label' => 'URL TikTok',
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
        DB::table('system_settings')->whereIn('key', [
            'social_instagram_url',
            'social_facebook_url',
            'social_tiktok_url',
        ])->delete();
    }
};
