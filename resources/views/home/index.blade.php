@extends('layouts.storefront')

@section('title', 'Toko HS ELECTRIC - Katalog Produk')
@section('header_subtitle', 'Katalog Produk')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 pb-24 lg:pb-8')

@php
    $baseSearchQuery = array_filter([
        'q' => $keyword,
    ]);
    $storeTagline = trim((string) \App\Models\Setting::get('store_tagline', ''));

    // Category icon mapping
    $categoryIcons = [
        'lampu' =>
            '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
        'kabel' =>
            '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
        'saklar' =>
            '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
        'mcb' =>
            '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
        'default' =>
            '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    ];

    $getCategoryIcon = function (string $name) use ($categoryIcons) {
        $lower = strtolower($name);
        foreach ($categoryIcons as $key => $icon) {
            if (str_contains($lower, $key)) {
                return $icon;
            }
        }
        return $categoryIcons['default'];
    };
@endphp

@section('content')
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
            class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 shadow-sm">
            <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="flex-1">{{ session('success') }}</span>
            <button @click="show = false" class="shrink-0 rounded-md p-0.5 hover:bg-green-100"><svg class="h-4 w-4"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-sm">
            <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="flex-1">{{ session('error') }}</span>
            <button @click="show = false" class="shrink-0 rounded-md p-0.5 hover:bg-red-100"><svg class="h-4 w-4"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         HERO BANNER CAROUSEL — Professional promotional banners
         ═══════════════════════════════════════════════════════ --}}
    <div x-data="heroBannerCarousel()" x-init="start()" class="mb-6 sm:mb-8">
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-500 to-teal-500 shadow-lg shadow-primary-500/20">
            <!-- Decorative elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute right-1/4 top-1/4 h-24 w-24 rounded-full bg-white/5 blur-xl"></div>
            </div>

            <div class="relative h-[160px] sm:h-[180px] md:h-[200px]">
                <!-- Slide 1: Main Store -->
                <div x-show="active === 0" x-transition:enter="transition duration-500 ease-out"
                    x-transition:enter-start="translate-x-8 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition duration-400 ease-in"
                    x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="-translate-x-8 opacity-0"
                    class="absolute inset-0 flex items-center px-6 sm:px-10 md:px-14">
                    <div class="flex-1 pr-24 sm:pr-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/70 sm:text-sm">
                            {{ $storeTagline ?: '⚡ Toko Listrik Terpercaya' }}</p>
                        <h2 class="mt-1 text-xl font-black text-white sm:text-2xl md:text-3xl">Pusat Kebutuhan<br>Listrik
                            Anda</h2>
                        <p class="mt-2 text-xs text-white/80 sm:text-sm max-w-md">{{ $totalProducts }} produk tersedia dari
                            {{ $totalCategories }} kategori dengan harga transparan dan garansi resmi.</p>
                    </div>
                    <a href="#catalog-section"
                        class="absolute right-4 top-4 z-10 inline-flex items-center gap-1.5 rounded-full border border-white/55 bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm transition hover:border-white/80 hover:bg-white/15 sm:right-5 sm:top-5 sm:text-xs">
                        Jelajahi Katalog
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <div class="hidden sm:flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                            <svg class="h-10 w-10 md:h-12 md:w-12 text-white" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Warranty -->
                <div x-show="active === 1" x-transition:enter="transition duration-500 ease-out"
                    x-transition:enter-start="translate-x-8 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition duration-400 ease-in"
                    x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="-translate-x-8 opacity-0"
                    class="absolute inset-0 flex items-center px-6 sm:px-10 md:px-14">
                    <div class="flex-1 pr-24 sm:pr-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/70 sm:text-sm">🛡️ Garansi Resmi
                        </p>
                        <h2 class="mt-1 text-xl font-black text-white sm:text-2xl md:text-3xl">Garansi Hingga 1
                            Tahun<br>Produk Elektronik</h2>
                        <p class="mt-2 text-xs text-white/80 sm:text-sm max-w-md">Upload bukti kerusakan, tim admin
                            memproses klaim Anda dalam 48 jam.</p>
                    </div>
                    <a href="{{ route('home.warranty') }}"
                        class="absolute right-4 top-4 z-10 inline-flex items-center gap-1.5 rounded-full border border-white/55 bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm transition hover:border-white/80 hover:bg-white/15 sm:right-5 sm:top-5 sm:text-xs">
                        Klaim Garansi
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <div class="hidden sm:flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                            <svg class="h-10 w-10 md:h-12 md:w-12 text-white" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Slide 3: COD -->
                <div x-show="active === 2" x-transition:enter="transition duration-500 ease-out"
                    x-transition:enter-start="translate-x-8 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition duration-400 ease-in"
                    x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="-translate-x-8 opacity-0"
                    class="absolute inset-0 flex items-center px-6 sm:px-10 md:px-14">
                    <div class="flex-1 pr-24 sm:pr-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/70 sm:text-sm">🚚 Pengiriman</p>
                        <h2 class="mt-1 text-xl font-black text-white sm:text-2xl md:text-3xl">Bayar di Tempat<br>COD
                            Tersedia</h2>
                        <p class="mt-2 text-xs text-white/80 sm:text-sm max-w-md">Pilih COD saat checkout, bayar saat
                            barang sampai di rumah. Zero ribet!</p>
                    </div>
                    <a href="#catalog-section"
                        class="absolute right-4 top-4 z-10 inline-flex items-center gap-1.5 rounded-full border border-white/55 bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm transition hover:border-white/80 hover:bg-white/15 sm:right-5 sm:top-5 sm:text-xs">
                        Belanja Sekarang
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <div class="hidden sm:flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                            <svg class="h-10 w-10 md:h-12 md:w-12 text-white" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Carousel dots -->
                <div class="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1.5">
                    <template x-for="i in total" :key="i">
                        <button type="button" @click="goTo(i - 1)"
                            :class="active === (i - 1) ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/70'"
                            class="h-2 rounded-full transition-all duration-300" :aria-label="'Slide ' + i"></button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         TRUST BADGES — Professional assurance indicators
         ═══════════════════════════════════════════════════════ --}}
    <div class="mb-6 sm:mb-8 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 sm:p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-900 sm:text-sm">Garansi Hingga 1 Tahun</p>
                <p class="text-[10px] text-gray-500 sm:text-xs">Produk Elektronik</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 sm:p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-900 sm:text-sm">COD Tersedia</p>
                <p class="text-[10px] text-gray-500 sm:text-xs">Bayar di tempat</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 sm:p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-900 sm:text-sm">Proses Cepat</p>
                <p class="text-[10px] text-gray-500 sm:text-xs">Admin responsif</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 sm:p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-900 sm:text-sm">100% Original</p>
                <p class="text-[10px] text-gray-500 sm:text-xs">Produk asli bergaransi</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         CATEGORY CHIPS — Horizontal scrollable Tokopedia-style
         ═══════════════════════════════════════════════════════ --}}
    @if ($categories->count() > 0)
        <div class="mb-6 sm:mb-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-gray-900 sm:text-lg">Kategori</h2>
                <span class="text-xs text-gray-500">{{ $totalCategories }} kategori</span>
            </div>

            {{-- Dropdown Kategori — Alpine.js powered --}}
            <div x-data="{ categoryOpen: false }" class="relative">
                <button type="button" @click="categoryOpen = !categoryOpen"
                    @keydown.escape.window="categoryOpen = false"
                    class="flex w-full items-center justify-between gap-3 rounded-xl border bg-white px-4 py-3 text-sm font-semibold transition shadow-sm
                    {{ $activeCategory ? 'border-primary-400 text-primary-700 ring-2 ring-primary-500/20' : 'border-gray-200 text-gray-700 hover:border-primary-200' }}"
                    :aria-expanded="categoryOpen.toString()">
                    <span class="flex items-center gap-2.5">
                        @if ($activeCategory)
                            {!! $getCategoryIcon($activeCategory->name) !!}
                            <span>{{ $activeCategory->name }}</span>
                            <span
                                class="rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-bold text-primary-600">{{ $activeCategory->active_products_count }}
                                produk</span>
                        @else
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span>Semua Kategori</span>
                            <span
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500">{{ $totalProducts }}
                                produk</span>
                        @endif
                    </span>
                    <svg class="h-5 w-5 text-gray-400 transition" :class="categoryOpen ? 'rotate-180' : ''" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- Dropdown Panel --}}
                <div x-cloak x-show="categoryOpen" @click.away="categoryOpen = false"
                    x-transition:enter="ease-out duration-150" x-transition:enter-start="-translate-y-1 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl shadow-gray-200/60">
                    <div class="max-h-64 overflow-y-auto">
                        {{-- Semua Kategori --}}
                        <a href="{{ route('home', array_filter(['q' => $keyword])) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm transition {{ !$activeCategory ? 'bg-primary-50 text-primary-700 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ !$activeCategory ? 'bg-primary-200 text-primary-700' : 'bg-gray-100 text-gray-500' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </span>
                            <span class="flex-1">Semua Kategori</span>
                            <span
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold {{ !$activeCategory ? 'bg-primary-100 text-primary-600' : 'text-gray-500' }}">{{ $totalProducts }}</span>
                            @if (!$activeCategory)
                                <svg class="h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </a>

                        <div class="mx-3 h-px bg-gray-100"></div>

                        {{-- Each category --}}
                        @foreach ($categories as $category)
                            @php $isActive = $activeCategory && $activeCategory->id === $category->id; @endphp
                            <a href="{{ route('home', array_filter(['q' => $keyword, 'category' => $category->id])) }}"
                                class="flex items-center gap-3 px-4 py-3 text-sm transition {{ $isActive ? 'bg-primary-50 text-primary-700 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $isActive ? 'bg-primary-200 text-primary-700' : 'bg-gray-100 text-gray-500' }}">
                                    {!! $getCategoryIcon($category->name) !!}
                                </span>
                                <span class="flex-1">{{ $category->name }}</span>
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold {{ $isActive ? 'bg-primary-100 text-primary-600' : 'text-gray-500' }}">{{ $category->active_products_count }}</span>
                                @if ($isActive)
                                    <svg class="h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         SEARCH BAR — Professional compact search
         ═══════════════════════════════════════════════════════ --}}
    <div id="catalog-section" class="mb-6 sm:mb-8">
        <form method="GET" action="{{ route('home') }}" class="flex gap-2">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" name="q" value="{{ $keyword }}"
                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-4 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    placeholder="Cari lampu, kabel, saklar, MCB...">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->id }}">
                @endif
            </div>
            <button type="submit"
                class="shrink-0 rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700 active:scale-95">
                <svg class="h-5 w-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="hidden sm:inline">Cari Produk</span>
            </button>
            @if ($keyword !== '' || $activeCategory)
                <a href="{{ route('home') }}"
                    class="shrink-0 flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            @endif
        </form>
        @if ($keyword !== '' || $activeCategory)
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                <span>Hasil untuk:</span>
                @if ($keyword !== '')
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700">
                        "{{ $keyword }}"
                        <a href="{{ route('home', $activeCategory ? ['category' => $activeCategory->id] : []) }}"
                            class="ml-0.5 text-gray-400 hover:text-gray-600">&times;</a>
                    </span>
                @endif
                @if ($activeCategory)
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 font-medium text-primary-700">
                        {{ $activeCategory->name }}
                        <a href="{{ route('home', $keyword !== '' ? ['q' => $keyword] : []) }}"
                            class="ml-0.5 text-primary-400 hover:text-primary-600">&times;</a>
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PRODUCT GRID — Tokopedia-style product cards
         ═══════════════════════════════════════════════════════ --}}
    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900 sm:text-lg">Produk Tersedia</h2>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $products->total() }}
                produk</span>
        </div>

        @forelse ($products as $product)
            @if ($loop->first)
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @endif

            @php
                $avgRating = round((float) ($product->reviews_avg_rating ?? 0), 1);
                $reviewCount = (int) ($product->reviews_count ?? 0);
                $stockPercent = $product->stock > 0 ? min(100, ($product->stock / max($product->stock, 50)) * 100) : 0;
                $isBestseller = $reviewCount >= 3 && $avgRating >= 4.0;
                $isLowStock = $product->stock > 0 && $product->stock <= 5;
                $warrantyDays = (int) $product->warranty_days_for_claim;
                $hasWarranty = $warrantyDays > 0;
            @endphp

            <article
                class="group flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-lg overflow-hidden">
                <a href="{{ route('home.products.show', $product->slug) }}"
                    class="block overflow-hidden bg-gray-50 aspect-square relative">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">

                    {{-- Badges --}}
                    <div class="absolute top-2 left-2 flex flex-col gap-1">
                        @if ($isBestseller)
                            <span
                                class="inline-flex items-center gap-0.5 rounded-md bg-amber-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white shadow-sm">
                                <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Terlaris
                            </span>
                        @endif
                        @if ($isLowStock)
                            <span
                                class="inline-flex items-center rounded-md bg-red-500 px-1.5 py-0.5 text-[9px] font-bold uppercase text-white shadow-sm">
                                Stok Terbatas
                            </span>
                        @endif
                    </div>

                    {{-- Out of stock overlay --}}
                    @if ($product->stock < 1)
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-900/50 backdrop-blur-[1px]">
                            <span class="rounded-lg bg-white/90 px-3 py-1.5 text-xs font-bold text-gray-700">Stok
                                Habis</span>
                        </div>
                    @endif
                </a>

                <div class="flex flex-1 flex-col p-3">
                    <h3
                        class="text-xs font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-primary-700 sm:text-sm">
                        {{ $product->name }}
                    </h3>

                    {{-- Category tag --}}
                    <p class="mt-0.5 text-[10px] text-gray-400 font-medium">
                        {{ $product->category->name ?? 'Uncategorized' }}</p>

                    {{-- Warranty indicator --}}
                    <div class="mt-1.5">
                        @if ($hasWarranty)
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Garansi {{ number_format($warrantyDays) }} hari
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 ring-1 ring-gray-200">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01" />
                                </svg>
                                Tanpa garansi
                            </span>
                        @endif
                    </div>

                    {{-- Rating --}}
                    @if ($reviewCount > 0)
                        <div class="mt-1.5 flex items-center gap-1">
                            <div class="flex items-center">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-3 w-3 {{ $i <= round($avgRating) ? 'text-amber-400' : 'text-gray-200' }}"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-[10px] text-gray-500">({{ $reviewCount }})</span>
                        </div>
                    @endif

                    <div class="mt-auto pt-2">
                        {{-- Price --}}
                        <p class="text-sm font-black text-gray-900">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                            <span class="text-[10px] font-medium text-gray-400">/{{ $product->unit }}</span>
                        </p>

                        {{-- Stock indicator --}}
                        @if ($product->stock > 0)
                            <div class="mt-1.5">
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="font-medium {{ $isLowStock ? 'text-red-500' : 'text-gray-500' }}">
                                        {{ $isLowStock ? 'Segera habis' : 'Stok ' . number_format($product->stock) }}
                                    </span>
                                </div>
                                <div class="mt-0.5 h-1 w-full rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isLowStock ? 'bg-red-400' : 'bg-primary-400' }}"
                                        style="width: {{ $stockPercent }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-2.5 flex items-center gap-1.5">
                        <a href="{{ route('home.products.show', $product->slug) }}"
                            class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-primary-700 active:scale-95">
                            Detail
                        </a>

                        @auth
                            <form method="POST" action="{{ route('home.products.buy', $product->slug) }}" class="flex-1">
                                @csrf
                                <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                                    class="inline-flex w-full items-center justify-center gap-1 rounded-lg px-2 py-2 text-xs font-bold transition active:scale-95
                                    {{ $product->stock < 1
                                        ? 'cursor-not-allowed border border-gray-200 bg-gray-50 text-gray-400'
                                        : 'bg-primary-600 text-white shadow-sm shadow-primary-500/20 hover:bg-primary-700' }}">
                                    @if ($product->stock < 1)
                                        Habis
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Beli
                                    @endif
                                </button>
                            </form>
                        @else
                            <button type="button"
                                onclick="alert('Silakan login ke akun Anda terlebih dahulu untuk menambah barang ke keranjang dan melakukan pembayaran.')"
                                class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg px-2 py-2 text-xs font-bold transition active:scale-95
                                {{ $product->stock < 1
                                    ? 'cursor-not-allowed border border-gray-200 bg-gray-50 text-gray-400'
                                    : 'bg-primary-600 text-white shadow-sm shadow-primary-500/20 hover:bg-primary-700' }}">
                                @if ($product->stock < 1)
                                    Habis
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Beli
                                @endif
                            </button>
                        @endauth
                    </div>
                </div>
            </article>

            @if ($loop->last)
                </div>
            @endif
        @empty
            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm mb-4">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Oops! Produk Tidak Ditemukan</h3>
                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                    Kami tidak dapat menemukan produk yang sesuai dengan pencarian Anda. Coba kata kunci yang lebih umum.
                </p>
                <a href="{{ route('home') }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                    Lihat Semua Produk
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        @endforelse

        @if ($products->hasPages())
            <div class="mt-8 rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        function heroBannerCarousel() {
            return {
                active: 0,
                total: 3,
                intervalId: null,
                start() {
                    this.stop();
                    this.intervalId = setInterval(() => {
                        this.active = (this.active + 1) % this.total;
                    }, 5000);
                },
                stop() {
                    if (this.intervalId === null) return;
                    clearInterval(this.intervalId);
                    this.intervalId = null;
                },
                goTo(index) {
                    this.active = index;
                    this.start();
                }
            };
        }
    </script>
@endpush
