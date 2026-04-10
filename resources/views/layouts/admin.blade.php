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

    $userManagementUrl = \Illuminate\Support\Facades\Route::has('admin.users.index')
        ? route('admin.users.index')
        : url('/admin/users');

    $systemSettingsUrl = \Illuminate\Support\Facades\Route::has('admin.settings.index')
        ? route('admin.settings.index')
        : url('/admin/settings');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Toko Listrik Arip') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

{{-- Layout ini hanya untuk role admin/super-admin. Role user biasa tidak menggunakan layout ini. --}}

<body x-data="{ sidebarOpen: false, dropdownOpen: false, notifDropdownOpen: false }"
      class="min-h-screen bg-gray-50 font-sans antialiased text-gray-800 dark:bg-dark-bg dark:text-gray-200">

    {{-- ═══════════════════════════════════════════════════════
         OVERLAY — Mobile sidebar backdrop
         ═══════════════════════════════════════════════════════ --}}
    <div x-cloak x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-overlay bg-black/50 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false"
         aria-hidden="true"></div>

    {{-- ═══════════════════════════════════════════════════════
         SIDEBAR — TailAdmin Style
         ═══════════════════════════════════════════════════════ --}}
    <aside id="admin-sidebar"
           class="fixed inset-y-0 left-0 z-sidebar flex w-[var(--ta-sidebar-width)] flex-col
                  border-r border-gray-200 bg-white shadow-sidebar
                  transition-transform duration-[var(--ta-transition)] ease-in-out
                  dark:border-dark-border dark:bg-dark-sidebar
                  lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        {{-- ── Sidebar Header ── --}}
        <div class="flex h-16 items-center justify-between border-b border-gray-200 px-6 dark:border-dark-border">
            <a href="{{ $dashboardUrl }}" class="flex items-center gap-3 group">
                <span
                    class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-md shadow-brand-500/25 transition-shadow group-hover:shadow-brand-500/40">
                    TA
                </span>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Toko Listrik Arip</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $displayRole }} Panel</p>
                </div>
            </a>

            {{-- Close button (mobile only) --}}
            <button type="button"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 lg:hidden dark:hover:bg-dark-hover dark:hover:text-gray-300"
                    @click="sidebarOpen = false"
                    aria-label="Tutup sidebar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- ── Sidebar Navigation ── --}}
        <div class="flex flex-1 flex-col overflow-y-auto px-4 py-5 ta-scrollbar">

            {{-- Section: Menu Utama --}}
            <p class="mb-3 px-3.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">
                Menu Utama
            </p>

            <nav class="space-y-0.5">
                {{-- Dashboard --}}
                <a href="{{ $dashboardUrl }}"
                   class="group ta-nav-item {{ request()->routeIs('admin.dashboard') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                    <svg class="ta-nav-icon {{ request()->routeIs('admin.dashboard') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                @hasanyrole('super-admin|admin')
                    {{-- Kategori --}}
                    <a href="{{ $categoryUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.categories.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.categories.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span>Kategori</span>
                    </a>

                    {{-- Produk --}}
                    <a href="{{ $productUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.products.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.products.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Produk</span>
                    </a>

                    {{-- Pesanan --}}
                    <a href="{{ $ordersUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.pesanan.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.pesanan.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Pesanan</span>
                    </a>

                    {{-- Klaim Garansi --}}
                    <a href="{{ $warrantyClaimsUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.warranty-claims.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.warranty-claims.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Klaim Garansi</span>
                    </a>

                    {{-- Notifikasi --}}
                    <a href="{{ $adminNotificationsUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.notifications.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.notifications.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span>Notifikasi</span>
                        @if ($adminUnreadNotificationCount > 0)
                            <span class="ml-auto inline-flex min-w-[20px] items-center justify-center rounded-full bg-error-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                {{ $adminUnreadNotificationCount }}
                            </span>
                        @endif
                    </a>
                @endhasanyrole

                @role('super-admin')
                    {{-- Section: Sistem --}}
                    <div class="my-4 border-t border-gray-200 pt-4 dark:border-dark-border">
                        <p class="mb-3 px-3.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">
                            Sistem
                        </p>
                    </div>

                    {{-- User Management --}}
                    <a href="{{ $userManagementUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.users.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.users.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>User Management</span>
                    </a>

                    {{-- System Settings --}}
                    <a href="{{ $systemSettingsUrl }}"
                       class="group ta-nav-item {{ request()->routeIs('admin.settings.*') ? 'ta-nav-active' : 'ta-nav-idle' }}">
                        <svg class="ta-nav-icon {{ request()->routeIs('admin.settings.*') ? 'ta-nav-icon-active' : 'ta-nav-icon-idle' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>System Settings</span>
                    </a>
                @endrole
            </nav>

            {{-- ── Sidebar Footer ── --}}
            <div class="mt-auto space-y-2 border-t border-gray-200 pt-4 dark:border-dark-border">
                {{-- User info card --}}
                <div class="flex items-center gap-3 rounded-xl bg-gray-50 px-3.5 py-3 dark:bg-dark-hover">
                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">
                        {{ strtoupper(substr($authUser->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">{{ $authUser->name }}</p>
                        <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $authUser->email }}</p>
                    </div>
                </div>

                {{-- Storefront link --}}
                <a href="{{ route('landing') }}" class="ta-nav-item ta-nav-idle w-full">
                    <svg class="ta-nav-icon ta-nav-icon-idle" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Lihat Toko</span>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="ta-nav-item text-error-500 hover:bg-error-50 hover:text-error-600 dark:text-error-400 dark:hover:bg-error-500/10 w-full text-left">
                        <svg class="ta-nav-icon text-error-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════════════
         MAIN WRAPPER (offset by sidebar on desktop)
         ═══════════════════════════════════════════════════════ --}}
    <div class="lg:ml-[var(--ta-sidebar-width)] min-h-screen flex flex-col transition-[margin] duration-[var(--ta-transition)]">

        {{-- ═══════════════════════════════════════════════════
             HEADER — Sticky top bar
             ═══════════════════════════════════════════════════ --}}
        <header class="sticky top-0 z-header border-b border-gray-200 bg-white/95 backdrop-blur-md dark:border-dark-border dark:bg-dark-sidebar/95">
            <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6">

                {{-- Left: Hamburger + Search --}}
                <div class="flex items-center gap-3 flex-1">
                    {{-- Hamburger (mobile only) --}}
                    <button type="button"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 lg:hidden dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                            @click="sidebarOpen = !sidebarOpen"
                            aria-label="Buka atau tutup sidebar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Search Bar --}}
                    <div class="hidden sm:block w-full max-w-md">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text"
                                   placeholder="Cari di dashboard..."
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
                    <button type="button"
                            @click="darkMode = !darkMode"
                            class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700
                                   dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                            :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                        {{-- Sun icon (shown in dark mode) --}}
                        <svg x-show="darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{-- Moon icon (shown in light mode) --}}
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    {{-- Notification Bell --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button"
                                @click="open = !open"
                                class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700
                                       dark:text-gray-400 dark:hover:bg-dark-hover dark:hover:text-gray-200"
                                aria-label="Notifikasi">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($adminUnreadNotificationCount > 0)
                                <span class="absolute -right-0.5 -top-0.5 grid h-4 w-4 place-items-center rounded-full bg-error-500 text-[9px] font-bold text-white ring-2 ring-white dark:ring-dark-sidebar">
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
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-dark-border">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Notifikasi</h3>
                                @if ($adminUnreadNotificationCount > 0)
                                    <span class="ui-badge ui-badge-info text-[10px]">{{ $adminUnreadNotificationCount }} baru</span>
                                @endif
                            </div>
                            <div class="py-2 max-h-64 overflow-y-auto ta-scrollbar">
                                @if ($adminUnreadNotificationCount > 0)
                                    <p class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                        Anda memiliki {{ $adminUnreadNotificationCount }} notifikasi belum dibaca.
                                    </p>
                                @else
                                    <p class="px-4 py-6 text-center text-xs text-gray-400 dark:text-gray-500">
                                        Tidak ada notifikasi baru.
                                    </p>
                                @endif
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
                        <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2.5 rounded-lg py-1.5 pl-1.5 pr-3 transition hover:bg-gray-50 dark:hover:bg-dark-hover">
                            <div class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">
                                {{ strtoupper(substr($authUser->name, 0, 1)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $authUser->name }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $displayRole }}</p>
                            </div>
                            <svg class="hidden sm:block h-4 w-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Profile Dropdown --}}
                        <div x-cloak x-show="open" @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="ta-dropdown right-0">

                            {{-- User info header --}}
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-dark-border">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $authUser->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $authUser->email }}</p>
                            </div>

                            {{-- Links --}}
                            <div class="py-1.5">
                                <a href="{{ route('profile.edit') }}" class="ta-dropdown-item">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Edit Profile
                                </a>
                                <a href="{{ route('landing') }}" class="ta-dropdown-item">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Lihat Toko
                                </a>
                            </div>

                            {{-- Logout --}}
                            <div class="border-t border-gray-100 py-1.5 dark:border-dark-border">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="ta-dropdown-item w-full text-error-500 hover:text-error-600 dark:text-error-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <svg class="h-5 w-5 shrink-0 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="show = false" class="ml-auto shrink-0 rounded-md p-0.5 hover:bg-success-100 dark:hover:bg-success-700/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="ui-alert ui-alert-error mb-6" x-data="{ show: true }" x-show="show"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <svg class="h-5 w-5 shrink-0 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button @click="show = false" class="ml-auto shrink-0 rounded-md p-0.5 hover:bg-error-100 dark:hover:bg-error-700/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if (session('warning'))
                <div class="ui-alert ui-alert-warning mb-6" x-data="{ show: true }" x-show="show"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <svg class="h-5 w-5 shrink-0 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <span class="flex-1">{{ session('warning') }}</span>
                    <button @click="show = false" class="ml-auto shrink-0 rounded-md p-0.5 hover:bg-warning-100 dark:hover:bg-warning-700/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>
