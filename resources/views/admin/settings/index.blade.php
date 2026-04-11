@extends('layouts.admin')

@section('title', 'System Settings')
@section('header', 'System Settings')

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST" x-data="{ activeTab: 'store' }">
        @csrf

        {{-- Tab Bar --}}
        <div class="mb-6 flex flex-wrap gap-1 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-sm dark:border-dark-border dark:bg-dark-card">
            @php
                $tabs = [
                    'store'         => ['label' => 'Info Toko',        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    'general'       => ['label' => 'Maintenance',      'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z'],
                    'bank'          => ['label' => 'Bank Transfer',    'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    'hours'         => ['label' => 'Jam Operasional',  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'notifications' => ['label' => 'Notifikasi',       'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                ];
            @endphp

            @foreach ($tabs as $tabKey => $tab)
                <button type="button"
                    @click="activeTab = '{{ $tabKey }}'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all"
                    :class="activeTab === '{{ $tabKey }}'
                        ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/30'
                        : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-white'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $tab['icon'] }}" />
                    </svg>
                    <span class="hidden sm:inline">{{ $tab['label'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- ═══════════════════════════════════
             TAB: INFO TOKO
             ═══════════════════════════════════ --}}
        <div x-show="activeTab === 'store'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card">
                <h2 class="mb-1 text-base font-bold text-gray-800 dark:text-white">Informasi Toko</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Detail toko yang ditampilkan ke pelanggan.</p>

                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach (['store_name', 'store_tagline', 'store_phone', 'store_email'] as $key)
                        @if (isset($settings[$key]))
                            <x-admin.setting-input :setting="$settings[$key]" />
                        @endif
                    @endforeach
                    <div class="sm:col-span-2">
                        @if (isset($settings['store_address']))
                            <x-admin.setting-input :setting="$settings['store_address']" :multiline="true" />
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════
             TAB: MAINTENANCE
             ═══════════════════════════════════ --}}
        <div x-show="activeTab === 'general'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card">
                <h2 class="mb-1 text-base font-bold text-gray-800 dark:text-white">Mode Maintenance</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Aktifkan untuk menutup toko sementara dari publik.</p>

                @if (isset($settings['maintenance_mode']))
                    <div class="mb-6 rounded-xl border border-gray-100 bg-gray-50 p-5 dark:border-dark-border dark:bg-dark-hover">
                        <label class="flex cursor-pointer items-start gap-4">
                            <div class="relative mt-0.5">
                                <input type="hidden" name="maintenance_mode" value="0">
                                <input type="checkbox"
                                    name="maintenance_mode"
                                    value="1"
                                    id="maintenance_mode"
                                    class="sr-only peer"
                                    @if($settings['maintenance_mode']->value === '1') checked @endif>
                                <div class="h-6 w-11 rounded-full bg-gray-300 peer-checked:bg-error-500 transition-colors dark:bg-gray-600 peer-checked:dark:bg-error-500"></div>
                                <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-white">Aktifkan Mode Maintenance</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pengunjung akan melihat halaman maintenance, admin tetap bisa masuk.</p>
                            </div>
                        </label>
                    </div>
                @endif

                @if (isset($settings['maintenance_msg']))
                    <x-admin.setting-input :setting="$settings['maintenance_msg']" :multiline="true" />
                @endif

                <div class="mt-4 rounded-xl border border-warning-200 bg-warning-50 p-4 flex gap-3 dark:border-warning-500/20 dark:bg-warning-500/10">
                    <svg class="h-5 w-5 shrink-0 text-warning-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <p class="text-sm text-warning-800 dark:text-warning-300">
                        <strong>Perhatian:</strong> Mode maintenance akan memblokir seluruh akses pelanggan. Pastikan nonaktifkan kembali setelah selesai.
                    </p>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════
             TAB: BANK TRANSFER
             ═══════════════════════════════════ --}}
        <div x-show="activeTab === 'bank'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card">
                <h2 class="mb-1 text-base font-bold text-gray-800 dark:text-white">Rekening Bank</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Informasi rekening yang ditampilkan kepada pelanggan saat checkout.</p>

                @foreach ([1, 2, 3] as $n)
                    <div class="mb-6 rounded-xl border border-gray-100 bg-gray-50 p-5 dark:border-dark-border dark:bg-dark-hover">
                        <p class="mb-4 text-sm font-bold text-gray-700 dark:text-gray-300">Bank {{ $n }}</p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            @foreach (['bank_' . $n . '_name', 'bank_' . $n . '_account', 'bank_' . $n . '_holder'] as $key)
                                @if (isset($settings[$key]))
                                    <x-admin.setting-input :setting="$settings[$key]" />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══════════════════════════════════
             TAB: JAM OPERASIONAL
             ═══════════════════════════════════ --}}
        <div x-show="activeTab === 'hours'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card">
                <h2 class="mb-1 text-base font-bold text-gray-800 dark:text-white">Jam Operasional</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Jadwal buka & tutup toko yang ditampilkan di halaman utama.</p>

                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach (['hours_weekday', 'hours_saturday', 'hours_sunday', 'hours_note'] as $key)
                        @if (isset($settings[$key]))
                            <x-admin.setting-input :setting="$settings[$key]" />
                        @endif
                    @endforeach
                </div>

                <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        💡 Format jam: <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs dark:bg-blue-900/30">08:00 - 17:00</code>
                        atau ketik <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs dark:bg-blue-900/30">Tutup</code> jika libur.
                    </p>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════
             TAB: NOTIFIKASI
             ═══════════════════════════════════ --}}
        <div x-show="activeTab === 'notifications'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card">
                <h2 class="mb-1 text-base font-bold text-gray-800 dark:text-white">Pengaturan Notifikasi</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Atur jenis notifikasi email yang dikirim ke admin.</p>

                <div class="space-y-4">
                    @foreach (['notif_order_new', 'notif_order_paid', 'notif_claim_new'] as $key)
                        @if (isset($settings[$key]))
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-100 bg-gray-50 px-5 py-4 transition hover:bg-gray-100 dark:border-dark-border dark:bg-dark-hover dark:hover:bg-dark-card">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $settings[$key]->label }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kunci: <code class="text-[10px]">{{ $key }}</code></p>
                                </div>
                                <div class="relative shrink-0">
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input type="checkbox"
                                        name="{{ $key }}"
                                        value="1"
                                        class="sr-only peer"
                                        @if($settings[$key]->value === '1') checked @endif>
                                    <div class="h-6 w-11 rounded-full bg-gray-300 peer-checked:bg-brand-500 transition-colors dark:bg-gray-600"></div>
                                    <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>
                                </div>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Floating Save Button --}}
        <div class="mt-6 flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/25 transition hover:bg-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-500/30">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Simpan Semua Pengaturan
            </button>
        </div>
    </form>
@endsection
