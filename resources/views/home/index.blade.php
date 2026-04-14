@extends('layouts.storefront')

@section('title', 'Toko HS ELECTRIC - Katalog Produk')
@section('header_subtitle', 'Katalog Produk')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8')

@php
    $baseSearchQuery = array_filter([
        'q' => $keyword,
    ]);
    $storeTagline = trim((string) \App\Models\Setting::get('store_tagline', ''));
@endphp

@section('content')
    @if (session('success'))
        <div
            class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div x-data="catalogWarrantyBanner()" x-init="start()" x-show="isOpen" x-cloak class="mb-6 block sm:mb-8">
        <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <button type="button" @click="close()"
                class="absolute right-2.5 top-2.5 z-20 inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                aria-label="Tutup banner">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <div class="relative h-[96px] sm:h-[88px]">
                <div x-show="active === 0" x-transition:enter="transform transition duration-500 ease-out"
                    x-transition:enter-start="translate-y-8 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transform transition duration-400 ease-in"
                    x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="-translate-y-8 opacity-0"
                    class="absolute inset-0 flex items-center justify-between gap-3 bg-white px-4 pr-12 sm:gap-4 sm:px-5 sm:pr-12">
                    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-600 sm:h-12 sm:w-12">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold uppercase tracking-wide text-teal-700 sm:text-sm">Garansi Resmi
                            </p>
                            <p class="line-clamp-2 text-xs font-semibold text-gray-800 sm:text-sm">Klaim garansi produk
                                elektronik hingga 7 hari</p>
                        </div>
                    </div>

                    <a href="{{ route('home.warranty') }}"
                        class="inline-flex flex-shrink-0 items-center rounded-full border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700 transition hover:bg-teal-100 sm:px-4 sm:py-2 sm:text-sm">
                        Klaim →
                    </a>
                </div>

                <div x-show="active === 1" x-transition:enter="transform transition duration-500 ease-out"
                    x-transition:enter-start="translate-y-8 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transform transition duration-400 ease-in"
                    x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="-translate-y-8 opacity-0"
                    class="absolute inset-0 flex items-center justify-between gap-3 bg-white px-4 pr-12 sm:gap-4 sm:px-5 sm:pr-12">
                    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 sm:h-12 sm:w-12">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 8h2a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2h2m10 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold uppercase tracking-wide text-cyan-700 sm:text-sm">Review Admin
                                Cepat</p>
                            <p class="line-clamp-2 text-xs font-semibold text-gray-800 sm:text-sm">Upload bukti kerusakan,
                                tim admin memproses maksimal 48 jam</p>
                        </div>
                    </div>

                    <a href="{{ route('home.warranty-claims.index') }}"
                        class="inline-flex flex-shrink-0 items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 sm:px-4 sm:py-2 sm:text-sm">
                        Lihat Klaim →
                    </a>
                </div>

                <div class="absolute bottom-2 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1.5">
                    <button type="button" @click="goTo(0)" :class="active === 0 ? 'w-5 bg-teal-500' : 'w-2 bg-gray-300'"
                        class="h-2 rounded-full transition-all duration-300" aria-label="Slide banner 1"></button>
                    <button type="button" @click="goTo(1)" :class="active === 1 ? 'w-5 bg-cyan-500' : 'w-2 bg-gray-300'"
                        class="h-2 rounded-full transition-all duration-300" aria-label="Slide banner 2"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <section
        class="mb-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8 flex flex-col md:flex-row gap-6 md:items-center justify-between">
        <div>
            <span
                class="mb-2 inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-600">Katalog
                Resmi</span>
            <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Pusat Kebutuhan Listrik</h1>
            @if ($storeTagline !== '')
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-primary-700">{{ $storeTagline }}</p>
            @endif
            <p class="mt-2 max-w-2xl text-sm text-gray-600">Jelajahi ratusan produk alat listrik dengan harga
                transparan dan stok riil.</p>
        </div>
        <div class="flex gap-4">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 min-w-[120px] text-center">
                <p class="text-xs font-bold uppercase text-gray-500">Kategori</p>
                <p class="mt-1 text-2xl font-black text-primary-600">{{ $totalCategories }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 min-w-[120px] text-center">
                <p class="text-xs font-bold uppercase text-gray-500">Produk</p>
                <p class="mt-1 text-2xl font-black text-primary-600">{{ $totalProducts }}</p>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="mb-8 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
            <h2 class="text-lg font-bold text-gray-900">Pencarian & Filter</h2>
            @if ($activeCategory)
                <span class="rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-700">
                    Kategori: {{ $activeCategory->name }}
                </span>
            @endif
        </div>

        <form method="GET" action="{{ route('home') }}" class="grid gap-3 lg:grid-cols-[1.5fr,1fr,auto,auto]">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" name="q" value="{{ $keyword }}"
                    class="w-full pl-10 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    placeholder="Cari nama produk, kabel, saklar...">
            </div>

            <select name="category"
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>
                        {{ $category->name }} ({{ $category->active_products_count }})
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="rounded-xl w-full sm:w-auto bg-primary-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                Cari Produk
            </button>

            <a href="{{ route('home') }}"
                class="rounded-xl w-full sm:w-auto border border-gray-300 bg-white px-6 py-2.5 text-center text-sm font-semibold text-gray-700 transition hover:bg-gray-50 flex items-center justify-center">
                Reset
            </a>
        </form>
    </section>

    <!-- Product Grid -->
    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Produk Tersedia</h2>
            <span class="text-sm font-medium text-gray-500">{{ $products->total() }} produk</span>
        </div>

        @forelse ($products as $product)
            @if ($loop->first)
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @endif

            <article
                class="group flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-primary-200 hover:shadow-lg overflow-hidden">
                <a href="{{ route('home.products.show', $product->slug) }}"
                    class="block overflow-hidden bg-gray-50 aspect-square relative">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                </a>

                <div class="p-3 flex flex-col flex-1">
                    <h3 class="text-sm font-bold text-gray-900 leading-snug line-clamp-2 group-hover:text-primary-700">
                        {{ $product->name }}
                    </h3>
                    <p class="mt-0.5 text-[11px] text-gray-500">{{ $product->category->name ?? 'Uncategorized' }}</p>

                    <div class="mt-auto pt-2">
                        <p class="text-sm font-black text-gray-900">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                        <div class="mt-1 flex items-center justify-between">
                            @if ($product->stock > 0)
                                <span class="text-[10px] font-semibold text-green-600">Stok:
                                    {{ number_format($product->stock) }}</span>
                            @else
                                <span class="text-[10px] font-semibold text-red-600">Habis</span>
                            @endif
                            <span class="text-[10px] font-bold uppercase text-gray-400">{{ $product->unit }}</span>
                        </div>
                    </div>

                    <div class="mt-2 flex items-center gap-1.5">
                        <a href="{{ route('home.products.show', $product->slug) }}"
                            class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-primary-600">
                            Detail
                        </a>

                        @auth
                            <form method="POST" action="{{ route('home.products.buy', $product->slug) }}" class="flex-1">
                                @csrf
                                <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                                    class="inline-flex w-full items-center justify-center gap-1 rounded-lg px-2 py-1.5 text-xs font-bold transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-400' : 'bg-primary-600 text-white shadow-sm hover:bg-primary-700' }}">
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
                                class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg px-2 py-1.5 text-xs font-bold transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-400' : 'bg-primary-600 text-white shadow-sm hover:bg-primary-700' }}">
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
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Oops! Produk Tidak Ditemukan</h3>
                <p class="mt-2 text-gray-500 max-w-md mx-auto">
                    Kami tidak dapat menemukan produk yang sesuai dengan pencarian Anda. Coba kata kunci yang lebih umum.
                </p>
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
        function catalogWarrantyBanner() {
            return {
                isOpen: true,
                active: 0,
                total: 2,
                intervalId: null,
                start() {
                    this.stop();
                    this.intervalId = setInterval(() => {
                        if (!this.isOpen) {
                            return;
                        }

                        this.active = (this.active + 1) % this.total;
                    }, 5000);
                },
                stop() {
                    if (this.intervalId === null) {
                        return;
                    }

                    clearInterval(this.intervalId);
                    this.intervalId = null;
                },
                goTo(index) {
                    this.active = index;
                    this.start();
                },
                close() {
                    this.isOpen = false;
                    this.stop();
                }
            };
        }
    </script>
@endpush
