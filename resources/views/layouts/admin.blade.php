@php
    abort_unless(
        auth()->check() &&
            auth()
                ->user()
                ->hasAnyRole(['super-admin', 'admin']),
        403,
    );

    $authUser = Auth::user();
    $primaryRole = $authUser?->getRoleNames()->first();
    $displayRole = \Illuminate\Support\Str::headline(str_replace('-', ' ', $primaryRole ?? 'user'));
    $normalizedAdminPhotoPath = str_replace('\\', '/', (string) $authUser?->profile_photo_path);
    $adminProfilePhotoUrl =
        $normalizedAdminPhotoPath !== '' &&
        (\Illuminate\Support\Facades\Storage::disk('local')->exists($normalizedAdminPhotoPath) ||
            \Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedAdminPhotoPath))
            ? route('profile.photo', $authUser) . '?v=' . ($authUser->updated_at?->timestamp ?? now()->timestamp)
            : null;

    $dashboardUrl = route('admin.dashboard');
    $categoryUrl = route('admin.categories.index');
    $productUrl = route('admin.products.index');

    $ordersUrl = \Illuminate\Support\Facades\Route::has('admin.orders.index')
        ? route('admin.orders.index')
        : (\Illuminate\Support\Facades\Route::has('admin.pesanan.index')
            ? route('admin.pesanan.index')
            : url('/admin/orders'));

    $warrantyClaimsUrl = \Illuminate\Support\Facades\Route::has('admin.warranty-claims.index')
        ? route('admin.warranty-claims.index')
        : url('/admin/warranty-claims');

    $adminNotificationsUrl = \Illuminate\Support\Facades\Route::has('admin.notifications.index')
        ? route('admin.notifications.index')
        : url('/admin/notifications');

    $notificationsTableExists = \Illuminate\Support\Facades\Schema::hasTable('notifications');

    $adminUnreadNotificationCount =
        $notificationsTableExists && $authUser ? $authUser->unreadNotifications()->count() : 0;

    $adminNotificationPreviews = collect();
    if ($notificationsTableExists && $authUser) {
        $adminNotificationPreviews = $authUser
            ->notifications()
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($notification) use ($adminNotificationsUrl) {
                $payload = is_array($notification->data) ? $notification->data : [];
                $title = trim((string) ($payload['title'] ?? 'Pembaruan sistem'));
                $message = trim((string) ($payload['message'] ?? 'Ada notifikasi baru untuk ditinjau.'));
                $route = trim((string) ($payload['route'] ?? ''));
                $referenceCode = trim((string) ($payload['order_code'] ?? ($payload['claim_code'] ?? '')));

                if ($route === '') {
                    $route = $adminNotificationsUrl;
                }

                return [
                    'open_route' => route('admin.notifications.open', ['notification' => $notification->id]),
                    'title' => $title !== '' ? $title : 'Pembaruan sistem',
                    'message' => \Illuminate\Support\Str::limit($message, 120),
                    'route' => $route,
                    'time' => optional($notification->created_at)->diffForHumans() ?? '-',
                    'reference' => $referenceCode,
                    'is_unread' => $notification->read_at === null,
                ];
            });
    }

    $hasUserManagementRoute = \Illuminate\Support\Facades\Route::has('admin.users.index');
    $hasSystemSettingsRoute = \Illuminate\Support\Facades\Route::has('admin.settings.index');

    $userManagementUrl = $hasUserManagementRoute ? route('admin.users.index') : '#';
    $systemSettingsUrl = $hasSystemSettingsRoute ? route('admin.settings.index') : '#';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
    :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ \App\Models\Setting::get('store_name', 'Toko Listrik') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|plus-jakarta-sans:400,500,600,700,800&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

{{-- Layout ini hanya untuk role admin/super-admin. Role user biasa tidak menggunakan layout ini. --}}

<body x-data="{
    sidebarOpen: false,
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    comingSoonToast: false,
    comingSoonLabel: '',
    confirmModalOpen: false,
    confirmModalTitle: '',
    confirmModalContext: '',
    confirmModalMessage: '',
    confirmModalImpact: '',
    confirmModalNote: '',
    confirmModalConfirmText: 'Ya, Lanjutkan',
    confirmModalCancelText: 'Tinjau Ulang',
    confirmModalTone: 'danger',
    confirmModalOnConfirm: null,
    showComingSoon(label) {
        this.comingSoonLabel = label;
        this.comingSoonToast = true;
        setTimeout(() => this.comingSoonToast = false, 2500);
    },
    openConfirmModal(options = {}) {
        const resolvedTone = options.tone ?? 'danger';

        this.confirmModalTitle = options.title ?? 'Konfirmasi Persetujuan';
        this.confirmModalContext = options.context ?? 'Permintaan ini membutuhkan persetujuan Anda.';
        this.confirmModalMessage = options.message ?? 'Tinjau detail tindakan sebelum melanjutkan proses.';
        this.confirmModalImpact = options.impact ?? '';
        this.confirmModalConfirmText = options.confirmText ?? 'Ya, Lanjutkan';
        this.confirmModalCancelText = options.cancelText ?? 'Tinjau Ulang';
        this.confirmModalTone = resolvedTone;
        this.confirmModalNote = options.note ?? (
            resolvedTone === 'danger' ?
            'Aksi ini bersifat permanen dan tidak dapat dibatalkan.' :
            'Pastikan data sudah diverifikasi sebelum dilanjutkan.'
        );
        this.confirmModalOnConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        this.confirmModalOpen = true;

        this.$nextTick(() => {
            this.$refs.confirmModalPrimaryButton?.focus();
        });
    },
    closeConfirmModal() {
        this.confirmModalOpen = false;
        this.confirmModalOnConfirm = null;
    },
    runConfirmModalAction() {
        if (typeof this.confirmModalOnConfirm === 'function') {
            this.confirmModalOnConfirm();
        }
        this.closeConfirmModal();
    },
    askFormConfirmation(formElement, options = {}) {
        this.openConfirmModal({
            ...options,
            onConfirm: () => formElement.submit(),
        });
    },
    syncMobileScrollLock() {
        const shouldLockBodyScroll =
            (this.sidebarOpen && window.innerWidth < 1024) || this.confirmModalOpen;

        document.documentElement.classList.toggle('overflow-hidden', shouldLockBodyScroll);
        document.body.classList.toggle('overflow-hidden', shouldLockBodyScroll);
    },
}" x-init="$watch('sidebarCollapsed', val => localStorage.setItem('sidebarCollapsed', val));
$watch('sidebarOpen', () => syncMobileScrollLock());
$watch('confirmModalOpen', () => syncMobileScrollLock());
syncMobileScrollLock();"
    @resize.window="if (window.innerWidth >= 1024 && sidebarOpen) sidebarOpen = false; syncMobileScrollLock()"
    class="min-h-screen bg-gray-50 font-sans antialiased text-gray-800 dark:bg-dark-bg dark:text-gray-200">

    {{-- ═══════════════════════════════════════════════════════
         COMING SOON TOAST — Appears when clicking disabled links
         ═══════════════════════════════════════════════════════ --}}
    <div x-cloak x-show="comingSoonToast" x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 left-1/2 z-toast -translate-x-1/2">
        <div
            class="flex items-center gap-3 rounded-xl border border-brand-200 bg-white px-5 py-3 shadow-tailadmin-lg dark:border-dark-border dark:bg-dark-card">
            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-50 dark:bg-brand-500/10">
                <svg class="h-4 w-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-white" x-text="comingSoonLabel"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Fitur ini akan segera hadir.</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         CONFIRMATION MODAL — Professional system dialog
         ═══════════════════════════════════════════════════════ --}}
    <div x-cloak x-show="confirmModalOpen" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" x-on:keydown.escape.window="closeConfirmModal()"
        class="fixed inset-0 z-modal flex items-center justify-center p-4">

        <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-sm" @click="closeConfirmModal()" aria-hidden="true">
        </div>

        <div x-cloak x-show="confirmModalOpen" x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-tailadmin-lg dark:border-dark-border dark:bg-dark-card"
            role="dialog" aria-modal="true" @click.stop>

            <div class="mb-4 flex items-start gap-3">
                <div class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-xl"
                    :class="confirmModalTone === 'danger'
                        ?
                        'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400' :
                        'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400'">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">
                        Sistem Konfirmasi
                    </p>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="confirmModalTitle"></h3>
                    <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400" x-text="confirmModalContext">
                    </p>
                    <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-300"
                        x-text="confirmModalMessage"></p>

                    <template x-if="confirmModalImpact !== ''">
                        <div
                            class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-dark-border dark:bg-dark-hover/40">
                            <p
                                class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Dampak Operasional
                            </p>
                            <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300"
                                x-text="confirmModalImpact"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <p class="mr-auto max-w-[55%] text-[11px] leading-relaxed text-gray-500 dark:text-gray-400"
                    x-text="confirmModalNote"></p>

                <button type="button" @click="closeConfirmModal()"
                    class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300 dark:hover:bg-dark-hover"
                    x-text="confirmModalCancelText"></button>

                <button type="button" @click="runConfirmModalAction()" x-ref="confirmModalPrimaryButton"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
                    :class="confirmModalTone === 'danger'
                        ?
                        'bg-error-500 hover:bg-error-600' :
                        'bg-brand-500 hover:bg-brand-600'"
                    x-text="confirmModalConfirmText"></button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         OVERLAY — Mobile sidebar backdrop
         ═══════════════════════════════════════════════════════ --}}
    <div x-cloak x-show="sidebarOpen && window.innerWidth < 1024"
        x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-overlay bg-black/50 lg:hidden cursor-pointer" @click="sidebarOpen = false"
        @touchmove.prevent style="touch-action: none;" aria-hidden="true"></div>

    {{-- ═══════════════════════════════════════════════════════
         SIDEBAR — TailAdmin Style (Collapsible on Desktop)
         ═══════════════════════════════════════════════════════ --}}
    <aside id="admin-sidebar"
        class="fixed inset-y-0 left-0 z-[60] lg:z-sidebar flex flex-col
                  border-r border-gray-200 bg-white shadow-sidebar
                  transition-all duration-[var(--ta-transition)] ease-in-out
                  dark:border-dark-border dark:bg-dark-sidebar"
        :class="{
            'translate-x-0': sidebarOpen,
            '-translate-x-full lg:translate-x-0': !sidebarOpen,
            'w-[var(--ta-sidebar-width)]': !sidebarCollapsed,
            'lg:w-20': sidebarCollapsed,
            'w-[var(--ta-sidebar-width)]': !sidebarCollapsed || sidebarOpen
        }">

        {{-- ── Sidebar Header ── --}}
        <div class="flex h-16 items-center border-b border-gray-200 px-4 dark:border-dark-border"
            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between px-6'">
            <a href="{{ $dashboardUrl }}" class="flex items-center gap-3 group"
                :class="sidebarCollapsed ? 'lg:gap-0' : ''">
                <img src="{{ asset('img/gemini_generated_image.png') }}"
                    alt="{{ \App\Models\Setting::get('store_name', 'Toko') }}"
                    class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 object-contain shadow-md dark:border-dark-border">
                <div :class="sidebarCollapsed ? 'lg:hidden' : ''" class="overflow-hidden transition-all duration-200">
                    <p class="text-sm font-bold text-gray-900 dark:text-white whitespace-nowrap">
                        {{ \App\Models\Setting::get('store_name', 'Toko Listrik') }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $displayRole }} Panel
                    </p>
                </div>
            </a>

            {{-- Close button (mobile only) --}}
            <button type="button"
                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 lg:hidden dark:hover:bg-dark-hover dark:hover:text-gray-300"
                @click="sidebarOpen = false" aria-label="Tutup sidebar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- ── Sidebar Navigation ── --}}
        <div class="flex flex-1 flex-col overflow-y-auto px-3 py-5 ta-scrollbar"
            :class="sidebarCollapsed ? 'lg:px-2' : 'px-4'">

            {{-- Section: Menu Utama --}}
            <p class="mb-3 px-3.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500"
                :class="sidebarCollapsed ? 'lg:hidden' : ''">
                Menu Utama
            </p>

            <nav class="space-y-0.5">
                {{-- Dashboard --}}
                <a href="{{ $dashboardUrl }}"
                    class="group ta-nav-item {{ request()->routeIs('admin.dashboard') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    :title="sidebarCollapsed ? 'Dashboard' : ''">
                    <svg class="ta-nav-icon {{ request()->routeIs('admin.dashboard') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z" />
                    </svg>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Dashboard</span>
                </a>

                @hasanyrole('super-admin|admin')
                    {{-- Kategori --}}
                    <a href="{{ $categoryUrl }}"
                        class="group ta-nav-item {{ request()->routeIs('admin.categories.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :title="sidebarCollapsed ? 'Kategori' : ''">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.categories.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Kategori</span>
                    </a>

                    {{-- Produk --}}
                    <a href="{{ $productUrl }}"
                        class="group ta-nav-item {{ request()->routeIs('admin.products.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :title="sidebarCollapsed ? 'Produk' : ''">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.products.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Produk</span>
                    </a>

                    {{-- Pesanan --}}
                    <a href="{{ $ordersUrl }}"
                        class="group ta-nav-item {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.pesanan.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :title="sidebarCollapsed ? 'Pesanan' : ''">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.pesanan.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Pesanan</span>
                    </a>

                    {{-- Klaim Garansi --}}
                    <a href="{{ $warrantyClaimsUrl }}"
                        class="group ta-nav-item {{ request()->routeIs('admin.warranty-claims.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :title="sidebarCollapsed ? 'Klaim Garansi' : ''">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.warranty-claims.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Klaim Garansi</span>
                    </a>

                    {{-- Notifikasi --}}
                    <a href="{{ $adminNotificationsUrl }}"
                        class="group ta-nav-item {{ request()->routeIs('admin.notifications.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :title="sidebarCollapsed ? 'Notifikasi' : ''">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.notifications.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Notifikasi</span>
                        @if ($adminUnreadNotificationCount > 0)
                            <span
                                class="ml-auto inline-flex min-w-[20px] items-center justify-center rounded-full bg-error-500 px-1.5 py-0.5 text-[10px] font-bold text-white"
                                :class="sidebarCollapsed ?
                                    'lg:ml-0 lg:absolute lg:-right-1 lg:-top-1 lg:min-w-[16px] lg:px-1 lg:text-[8px]' :
                                    ''">
                                {{ $adminUnreadNotificationCount }}
                            </span>
                        @endif
                    </a>
                @endhasanyrole

                @role('super-admin')
                    {{-- Section: Sistem --}}
                    <div class="my-4 border-t border-gray-200 pt-4 dark:border-dark-border">
                        <p class="mb-3 px-3.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500"
                            :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            Sistem
                        </p>
                    </div>

                    {{-- User Management — Coming Soon if route doesn't exist --}}
                    @if ($hasUserManagementRoute)
                        <a href="{{ $userManagementUrl }}"
                            class="group ta-nav-item {{ request()->routeIs('admin.users.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                            :title="sidebarCollapsed ? 'User Management' : ''">
                        @else
                            <button type="button" @click="showComingSoon('User Management')"
                                class="group ta-nav-item ta-nav-idle w-full relative"
                                :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                                :title="sidebarCollapsed ? 'User Management — Coming Soon' : ''">
                    @endif
                    <svg class="ta-nav-icon {{ request()->routeIs('admin.users.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">User Management</span>
                    @unless ($hasUserManagementRoute)
                        <span
                            class="ml-auto rounded-md bg-warning-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-warning-600 dark:bg-warning-700/20 dark:text-warning-400"
                            :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            Soon
                        </span>
                    @endunless
                    @if ($hasUserManagementRoute)
                        </a>
                    @else
                        </button>
                    @endif

                    {{-- System Settings — Coming Soon if route doesn't exist --}}
                    @if ($hasSystemSettingsRoute)
                        <a href="{{ $systemSettingsUrl }}"
                            class="group ta-nav-item {{ request()->routeIs('admin.settings.*') ? 'ta-nav-active' : 'ta-nav-idle' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                            :title="sidebarCollapsed ? 'System Settings' : ''">
                        @else
                            <button type="button" @click="showComingSoon('System Settings')"
                                class="group ta-nav-item ta-nav-idle w-full relative"
                                :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                                :title="sidebarCollapsed ? 'System Settings — Coming Soon' : ''">
                    @endif
                    <svg class="ta-nav-icon {{ request()->routeIs('admin.settings.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">System Settings</span>
                    @unless ($hasSystemSettingsRoute)
                        <span
                            class="ml-auto rounded-md bg-warning-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-warning-600 dark:bg-warning-700/20 dark:text-warning-400"
                            :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            Soon
                        </span>
                    @endunless
                    @if ($hasSystemSettingsRoute)
                        </a>
                    @else
                        </button>
                    @endif
                @endrole
            </nav>

            {{-- ── Sidebar Footer ── --}}
            <div class="mt-auto space-y-2 border-t border-gray-200 pt-4 dark:border-dark-border">
                {{-- User info card --}}
                <div class="flex items-center gap-3 rounded-xl bg-gray-50 px-3.5 py-3 dark:bg-dark-hover"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-2 lg:py-2' : ''">
                    <div
                        class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">
                        @if ($adminProfilePhotoUrl)
                            <img src="{{ $adminProfilePhotoUrl }}" alt="Foto profil {{ $authUser->name }}"
                                class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($authUser->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">{{ $authUser->name }}
                        </p>
                        <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $authUser->email }}</p>
                    </div>
                </div>

                {{-- Storefront link --}}
                <a href="{{ route('home') }}" class="ta-nav-item ta-nav-idle w-full"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    :title="sidebarCollapsed ? 'Lihat Toko' : ''">
                    <svg class="ta-nav-icon ta-nav-icon-idle" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Lihat Toko</span>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="ta-nav-item text-error-500 hover:bg-error-50 hover:text-error-600 dark:text-error-400 dark:hover:bg-error-500/10 w-full text-left"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                        :title="sidebarCollapsed ? 'Logout' : ''">
                        <svg class="ta-nav-icon text-error-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Logout</span>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ═══════════════════════════════════════════════════════
         MAIN WRAPPER (offset by sidebar on desktop)
         ═══════════════════════════════════════════════════════ --}}
    <div class="min-h-screen flex flex-col transition-[margin] duration-[var(--ta-transition)]"
        :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-[var(--ta-sidebar-width)]'">

        {{-- ═══════════════════════════════════════════════════
             HEADER — Sticky top bar
             ═══════════════════════════════════════════════════ --}}
        <header
            class="sticky top-0 z-header border-b border-gray-200 bg-white/95 backdrop-blur-md dark:border-dark-border dark:bg-dark-sidebar/95">
            <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6">

                {{-- Left: Hamburger + Search --}}
                <div class="flex items-center gap-3 flex-1">
                    {{-- Hamburger — visible on MOBILE (toggles sidebar open) --}}
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 lg:hidden dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                        @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Desktop: Collapse toggle also in header for quick access --}}
                    <button type="button"
                        class="hidden lg:inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <svg class="h-5 w-5 transition-transform duration-300"
                            :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>

                    {{-- Search Bar --}}
                    <div class="hidden sm:block w-full max-w-md">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" placeholder="Cari di dashboard..."
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-10 pr-4 text-sm text-gray-700 transition
                                          placeholder:text-gray-400
                                          focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20
                                          dark:border-dark-border dark:bg-dark-input dark:text-gray-200 dark:placeholder:text-gray-500
                                          dark:focus:border-brand-500 dark:focus:bg-dark-card">
                        </div>
                    </div>
                </div>

                {{-- Right: Actions --}}
                <div class="flex items-center gap-1 sm:gap-2">

                    {{-- Dark Mode Toggle --}}
                    <button type="button" @click="darkMode = !darkMode"
                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700
                                   dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                        :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                        {{-- Sun icon (shown in dark mode) --}}
                        <svg x-show="darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{-- Moon icon (shown in light mode) --}}
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    {{-- Notification Bell --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                            class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700
                                       dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                            aria-label="Notifikasi">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($adminUnreadNotificationCount > 0)
                                <span
                                    class="absolute -right-0.5 -top-0.5 grid h-4 w-4 place-items-center rounded-full bg-error-500 text-[9px] font-bold text-white ring-2 ring-white dark:ring-dark-sidebar">
                                    {{ $adminUnreadNotificationCount > 9 ? '9+' : $adminUnreadNotificationCount }}
                                </span>
                            @endif
                        </button>

                        {{-- Notification Dropdown --}}
                        <div x-cloak x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="ta-dropdown w-72 sm:w-80 right-0">
                            <div
                                class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-dark-border">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Notifikasi</h3>
                                @if ($adminUnreadNotificationCount > 0)
                                    <span
                                        class="ui-badge ui-badge-info text-[10px]">{{ $adminUnreadNotificationCount }}
                                        baru</span>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto ta-scrollbar">
                                @if ($adminUnreadNotificationCount > 0)
                                    <p class="px-4 pt-3 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                        Anda memiliki {{ $adminUnreadNotificationCount }} notifikasi belum dibaca.
                                    </p>
                                @endif

                                @forelse ($adminNotificationPreviews as $preview)
                                    <a href="{{ $preview['open_route'] }}"
                                        class="block border-b border-gray-100/80 transition hover:bg-gray-50 dark:border-dark-border dark:hover:bg-dark-hover">
                                        <div class="px-4 py-3">
                                            <div class="flex items-start gap-2">
                                                <span
                                                    class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $preview['is_unread'] ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-500' }}"></span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="text-xs font-semibold text-gray-800 dark:text-white">
                                                            {{ $preview['title'] }}</p>
                                                        <p
                                                            class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500">
                                                            {{ $preview['time'] }}</p>
                                                    </div>

                                                    <p
                                                        class="mt-1 text-[11px] leading-relaxed text-gray-600 dark:text-gray-300">
                                                        {{ $preview['message'] }}</p>

                                                    @if ($preview['reference'] !== '')
                                                        <p
                                                            class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                                            Ref: {{ $preview['reference'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <p class="px-4 py-6 text-center text-xs text-gray-400 dark:text-gray-500">
                                        Belum ada notifikasi admin.
                                    </p>
                                @endforelse
                            </div>
                            <div class="border-t border-gray-100 dark:border-dark-border">
                                <a href="{{ $adminNotificationsUrl }}"
                                    class="block px-4 py-2.5 text-center text-xs font-semibold text-brand-500 transition hover:bg-gray-50 dark:hover:bg-dark-hover">
                                    Lihat Semua Notifikasi
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="hidden sm:block h-6 w-px bg-gray-200 dark:bg-dark-border"></div>

                    {{-- User Profile Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                            class="flex items-center gap-2.5 rounded-lg py-1.5 pl-1.5 pr-3 transition hover:bg-gray-50 dark:hover:bg-dark-hover">
                            <div
                                class="grid h-8 w-8 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">
                                @if ($adminProfilePhotoUrl)
                                    <img src="{{ $adminProfilePhotoUrl }}" alt="Foto profil {{ $authUser->name }}"
                                        class="h-full w-full object-cover">
                                @else
                                    {{ strtoupper(substr($authUser->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $authUser->name }}
                                </p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $displayRole }}</p>
                            </div>
                            <svg class="hidden sm:block h-4 w-4 text-gray-400 transition-transform"
                                :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Profile Dropdown --}}
                        <div x-cloak x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1" class="ta-dropdown right-0">

                            {{-- User info header --}}
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-dark-border">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $authUser->name }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $authUser->email }}</p>
                            </div>

                            {{-- Links --}}
                            <div class="py-1.5">
                                <a href="{{ route('profile.edit') }}" class="ta-dropdown-item">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Edit Profile
                                </a>
                                <a href="{{ route('home') }}" class="ta-dropdown-item">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Lihat Toko
                                </a>
                            </div>

                            {{-- Logout --}}
                            <div class="border-t border-gray-100 py-1.5 dark:border-dark-border">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="ta-dropdown-item w-full text-error-500 hover:text-error-600 dark:text-error-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- ═══════════════════════════════════════════════════
             MAIN CONTENT AREA
             ═══════════════════════════════════════════════════ --}}
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

            {{-- Page Header (breadcrumb area) --}}
            @hasSection('header')
                <div class="mb-6">
                    <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 mb-1">
                        <a href="{{ $dashboardUrl }}" class="hover:text-brand-500 transition">Home</a>
                        <span>/</span>
                        <span class="text-gray-600 dark:text-gray-300">@yield('header')</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white sm:text-2xl">@yield('header', 'Dashboard')</h1>
                </div>
            @endif

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="ui-alert ui-alert-success mb-6" x-data="{ show: true }" x-show="show"
                    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">
                    <svg class="h-5 w-5 shrink-0 text-success-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="show = false"
                        class="ml-auto shrink-0 rounded-md p-0.5 hover:bg-success-100 dark:hover:bg-success-700/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="ui-alert ui-alert-error mb-6" x-data="{ show: true }" x-show="show"
                    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">
                    <svg class="h-5 w-5 shrink-0 text-error-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button @click="show = false"
                        class="ml-auto shrink-0 rounded-md p-0.5 hover:bg-error-100 dark:hover:bg-error-700/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('warning'))
                <div class="ui-alert ui-alert-warning mb-6" x-data="{ show: true }" x-show="show"
                    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">
                    <svg class="h-5 w-5 shrink-0 text-warning-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <span class="flex-1">{{ session('warning') }}</span>
                    <button @click="show = false"
                        class="ml-auto shrink-0 rounded-md p-0.5 hover:bg-warning-100 dark:hover:bg-warning-700/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>
