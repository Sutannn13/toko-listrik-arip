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

    $navBaseClasses =
        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition duration-200';
    $navIdleClasses = 'text-slate-300 hover:bg-slate-800 hover:text-white';
    $navActiveClasses = 'bg-slate-800 text-cyan-200 ring-1 ring-cyan-400/40 shadow-sm';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Toko Listrik Arip') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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
        <div x-cloak x-show="sidebarOpen" x-transition.opacity
            class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-800/80 bg-slate-900 shadow-2xl transition-transform duration-300 ease-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center justify-between border-b border-slate-800 px-5">
                <a href="{{ $dashboardUrl }}" class="flex items-center gap-3">
                    <span
                        class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 text-sm font-bold text-white">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-wide text-white">Toko Listrik Arip</p>
                        <p class="text-[11px] text-slate-400">Super Admin Panel</p>
                    </div>
                </a>

                <button type="button"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                    @click="sidebarOpen = false" aria-label="Tutup sidebar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex h-[calc(100%-4rem)] flex-col px-4 py-5">
                <nav class="space-y-1.5">
                    <a href="{{ $dashboardUrl }}"
                        class="{{ $navBaseClasses }} {{ request()->routeIs('admin.dashboard') ? $navActiveClasses : $navIdleClasses }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    @hasanyrole('super-admin|admin')
                        <a href="{{ $categoryUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.categories.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <span>Kategori</span>
                        </a>

                        <a href="{{ $productUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.products.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V7a2 2 0 00-2-2h-3V3m0 2H9m6 0v2m0-2h2a2 2 0 012 2v6m-8 8H6a2 2 0 01-2-2v-5m0 0V8a2 2 0 012-2h3m-5 8h6m-6 0v5a2 2 0 002 2h5m-7-7l2-2m0 0l2-2m-2 2h8" />
                            </svg>
                            <span>Produk</span>
                        </a>

                        <a href="{{ $ordersUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.pesanan.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2m0 0L7 13h10l2-8H5.4zM7 13l-1.5 6h13M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                            <span>Pesanan</span>
                        </a>

                        <a href="{{ $warrantyClaimsUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.warranty-claims.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Klaim Garansi</span>
                        </a>
                    @endhasanyrole

                    @role('super-admin')
                        <div class="my-4 border-t border-slate-800 pt-4"></div>

                        <a href="{{ $userManagementUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.users.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H11a4 4 0 00-4 4v2m10 0H7m8-10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>User Management</span>
                        </a>

                        <a href="{{ $systemSettingsUrl }}"
                            class="{{ $navBaseClasses }} {{ request()->routeIs('admin.settings.*') ? $navActiveClasses : $navIdleClasses }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317a1 1 0 011.35-.936l.822.329a1 1 0 001.198-.322l.527-.79a1 1 0 011.519-.096l1.414 1.414a1 1 0 01-.096 1.519l-.79.527a1 1 0 00-.322 1.198l.329.822a1 1 0 01-.936 1.35h-.9a1 1 0 00-.949.684l-.286.857a1 1 0 01-.95.684h-2.828a1 1 0 01-.95-.684l-.286-.857a1 1 0 00-.949-.684h-.9a1 1 0 01-.936-1.35l.329-.822a1 1 0 00-.322-1.198l-.79-.527a1 1 0 01-.096-1.519l1.414-1.414a1 1 0 011.519.096l.527.79a1 1 0 001.198.322l.822-.329a1 1 0 011.35.936v.9a1 1 0 00.684.949l.857.286a1 1 0 01.684.95v2.828a1 1 0 01-.684.95l-.857.286a1 1 0 00-.684.949v.9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            <span>System Settings</span>
                        </a>
                    @endrole
                </nav>

                <div class="mt-auto border-t border-slate-800 pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="{{ $navBaseClasses }} {{ $navIdleClasses }} w-full text-left">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V6m-6 15h6a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="relative min-h-screen">
            <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/95 backdrop-blur">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-10">
                    <div class="flex items-center gap-3">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2.5 text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800"
                            @click="sidebarOpen = !sidebarOpen" aria-label="Buka atau tutup sidebar">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Admin Workspace</p>
                            <h1 class="text-lg font-bold text-slate-900">@yield('header', 'Dashboard')</h1>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-full border border-slate-200 bg-white px-2 py-1 shadow-sm">
                        <div
                            class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white">
                            {{ strtoupper(substr($authUser->name, 0, 1)) }}
                        </div>
                        <div class="pr-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $authUser->name }}
                                ({{ $displayRole }})</p>
                            <p class="text-xs text-slate-500">{{ $authUser->email }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                @if (session('success'))
                    <div
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
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
