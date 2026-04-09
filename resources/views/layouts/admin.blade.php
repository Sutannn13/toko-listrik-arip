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

    $userManagementUrl = \Illuminate\Support\Facades\Route::has('admin.users.index')
        ? route('admin.users.index')
        : url('/admin/users');

    $systemSettingsUrl = \Illuminate\Support\Facades\Route::has('admin.settings.index')
        ? route('admin.settings.index')
        : url('/admin/settings');

    /*
     * Premium SaaS nav styling:
     * - Base: clean spacing with smooth transition
     * - Idle: muted text, subtle hover lift
     * - Active: soft colored backlight glow via shadow + ring — acts as a premium indicator
     *   without being obnoxious. Think: high-end gaming peripheral's status LED.
     */
    $navBaseClasses =
        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 ease-out';
    $navIdleClasses = 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100 hover:shadow-[0_0_12px_rgba(148,163,184,0.08)]';
    $navActiveClasses = 'bg-slate-800/80 text-cyan-300 ring-1 ring-cyan-400/30 shadow-[0_0_20px_rgba(34,211,238,0.12)] backdrop-blur-sm';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Toko Listrik Arip') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

{{-- Layout ini hanya untuk role admin/super-admin. Role user biasa tidak menggunakan layout ini. --}}

<body x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-100 font-sans antialiased text-slate-800">
    <div class="relative min-h-screen overflow-x-hidden">
        {{-- Overlay backdrop (mobile sidebar trigger) --}}
        <div x-cloak x-show="sidebarOpen" x-transition.opacity
            class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- SIDEBAR — Dark Slate with subtle backlight  --}}
        {{-- ═══════════════════════════════════════════ --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-800/60 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 shadow-2xl transition-transform duration-300 ease-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            {{-- Sidebar Header --}}
            <div class="flex h-16 items-center justify-between border-b border-slate-800/60 px-5">
                <a href="{{ $dashboardUrl }}" class="flex items-center gap-3 group">
                    <span
                        class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 text-sm font-bold text-white shadow-md shadow-cyan-500/20 transition-shadow group-hover:shadow-cyan-500/40">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-wide text-white">Toko Listrik Arip</p>
                        <p class="text-[11px] text-slate-500">{{ $displayRole }} Panel</p>
                    </div>
                </a>

                <button type="button"
                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-800 hover:text-slate-300"
                    @click="sidebarOpen = false" aria-label="Tutup sidebar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Sidebar Navigation --}}
            <div class="flex h-[calc(100%-4rem)] flex-col px-4 py-5">
                <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">Menu Utama</p>

                <nav class="space-y-1">
                    <a href="{{ $dashboardUrl }}"
                        class="{{ $navBaseClasses }} {{ request()->routeIs('admin.dashboard') ? $navActiveClasses : $navIdleClasses }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    @hasanyrole('super-admin|admin')
                        <a href="{{ $categoryUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.categories.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <span>Kategori</span>
                        </a>

                        <a href="{{ $productUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.products.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Produk</span>
                        </a>

                        <a href="{{ $ordersUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.pesanan.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>Pesanan</span>
                        </a>

                        <a href="{{ $warrantyClaimsUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.warranty-claims.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Klaim Garansi</span>
                        </a>
                    @endhasanyrole

                    @role('super-admin')
                        <div class="my-4 border-t border-slate-800/50 pt-4">
                            <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">Sistem</p>
                        </div>

                        <a href="{{ $userManagementUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.users.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>User Management</span>
                        </a>

                        <a href="{{ $systemSettingsUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.settings.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>System Settings</span>
                        </a>
                    @endrole
                </nav>

                {{-- Sidebar Footer — User card + Logout --}}
                <div class="mt-auto space-y-3 border-t border-slate-800/50 pt-4">
                    {{-- Quick info card --}}
                    <div class="flex items-center gap-3 rounded-xl bg-slate-800/40 px-3 py-3">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-cyan-400 to-blue-600 text-sm font-bold text-white">
                            {{ strtoupper(substr($authUser->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-200">{{ $authUser->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $authUser->email }}</p>
                        </div>
                    </div>

                    {{-- Storefront link --}}
                    <a href="{{ route('landing') }}" class="{{ $navBaseClasses }} {{ $navIdleClasses }} w-full">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Lihat Toko</span>
                    </a>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="{{ $navBaseClasses }} text-red-400/70 hover:bg-red-950/30 hover:text-red-400 w-full text-left">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ════════════════════════════════ --}}
        {{-- MAIN CONTENT AREA              --}}
        {{-- ════════════════════════════════ --}}
        <div class="relative min-h-screen">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/95 backdrop-blur-sm">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-10">
                    <div class="flex items-center gap-3">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2.5 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700"
                            @click="sidebarOpen = !sidebarOpen" aria-label="Buka atau tutup sidebar">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Admin Workspace</p>
                            <h1 class="text-base font-bold text-slate-900 sm:text-lg">@yield('header', 'Dashboard')</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-full border border-slate-200 bg-white px-2 py-1 shadow-sm">
                        <div class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-cyan-400 to-blue-600 text-xs font-bold text-white">
                            {{ strtoupper(substr($authUser->name, 0, 1)) }}
                        </div>
                        <div class="hidden pr-2 sm:block">
                            <p class="text-sm font-semibold text-slate-800">{{ $authUser->name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $displayRole }}</p>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Body --}}
            <main class="px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                @if (session('success'))
                    <div
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
