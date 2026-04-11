<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // general | store | bank | hours | notifications
            $table->string('type')->default('string');   // string | boolean | json
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        $now = now();
        $defaults = [
            // --- GENERAL / STORE INFO ---
            ['key' => 'store_name',        'value' => 'Toko HS ELECTRIC',               'group' => 'store',         'type' => 'string',  'label' => 'Nama Toko'],
            ['key' => 'store_tagline',     'value' => 'Solusi Listrik Rumah & Industri', 'group' => 'store',         'type' => 'string',  'label' => 'Tagline Toko'],
            ['key' => 'store_address',     'value' => 'Jl. Listrik No. 1, Jakarta',      'group' => 'store',         'type' => 'string',  'label' => 'Alamat Toko'],
            ['key' => 'store_phone',       'value' => '08123456789',                     'group' => 'store',         'type' => 'string',  'label' => 'No. WhatsApp'],
            ['key' => 'store_email',       'value' => 'admin@tokolistrik.com',            'group' => 'store',         'type' => 'string',  'label' => 'Email Toko'],
            // --- MAINTENANCE ---
            ['key' => 'maintenance_mode',  'value' => '0',                               'group' => 'general',       'type' => 'boolean', 'label' => 'Mode Maintenance'],
            ['key' => 'maintenance_msg',   'value' => 'Toko sedang dalam perbaikan. Kami akan kembali segera.', 'group' => 'general', 'type' => 'string', 'label' => 'Pesan Maintenance'],
            // --- BANK TRANSFER ---
            ['key' => 'bank_1_name',       'value' => 'BCA',                             'group' => 'bank',          'type' => 'string',  'label' => 'Bank 1 — Nama'],
            ['key' => 'bank_1_account',    'value' => '1234567890',                      'group' => 'bank',          'type' => 'string',  'label' => 'Bank 1 — No. Rekening'],
            ['key' => 'bank_1_holder',     'value' => 'Arip Hidayat',                    'group' => 'bank',          'type' => 'string',  'label' => 'Bank 1 — Atas Nama'],
            ['key' => 'bank_2_name',       'value' => 'Mandiri',                         'group' => 'bank',          'type' => 'string',  'label' => 'Bank 2 — Nama'],
            ['key' => 'bank_2_account',    'value' => '0987654321',                      'group' => 'bank',          'type' => 'string',  'label' => 'Bank 2 — No. Rekening'],
            ['key' => 'bank_2_holder',     'value' => 'Arip Hidayat',                    'group' => 'bank',          'type' => 'string',  'label' => 'Bank 2 — Atas Nama'],
            ['key' => 'bank_3_name',       'value' => 'BRI',                             'group' => 'bank',          'type' => 'string',  'label' => 'Bank 3 — Nama'],
            ['key' => 'bank_3_account',    'value' => '',                                'group' => 'bank',          'type' => 'string',  'label' => 'Bank 3 — No. Rekening'],
            ['key' => 'bank_3_holder',     'value' => '',                                'group' => 'bank',          'type' => 'string',  'label' => 'Bank 3 — Atas Nama'],
            // --- JAM OPERASIONAL ---
            ['key' => 'hours_weekday',     'value' => '08:00 - 17:00',                   'group' => 'hours',         'type' => 'string',  'label' => 'Jam Buka (Senin–Jumat)'],
            ['key' => 'hours_saturday',    'value' => '08:00 - 15:00',                   'group' => 'hours',         'type' => 'string',  'label' => 'Jam Buka (Sabtu)'],
            ['key' => 'hours_sunday',      'value' => 'Tutup',                           'group' => 'hours',         'type' => 'string',  'label' => 'Jam Buka (Minggu)'],
            ['key' => 'hours_note',        'value' => '',                                'group' => 'hours',         'type' => 'string',  'label' => 'Catatan Jam Operasional'],
            // --- NOTIFIKASI ---
            ['key' => 'notif_order_new',   'value' => '1',                               'group' => 'notifications', 'type' => 'boolean', 'label' => 'Email: Pesanan Baru Masuk'],
            ['key' => 'notif_order_paid',  'value' => '1',                               'group' => 'notifications', 'type' => 'boolean', 'label' => 'Email: Pembayaran Diterima'],
            ['key' => 'notif_claim_new',   'value' => '1',                               'group' => 'notifications', 'type' => 'boolean', 'label' => 'Email: Klaim Garansi Baru'],
        ];

        foreach ($defaults as $setting) {
            DB::table('system_settings')->insert(array_merge($setting, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
