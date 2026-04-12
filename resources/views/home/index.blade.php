@extends('layouts.storefront')

@section('title', 'Toko HS ELECTRIC - Katalog Produk')
@section('header_subtitle', 'Katalog Produk')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8')

@php
    $baseSearchQuery = array_filter([
        'q' => $keyword,
    ]);
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

    <!-- Page Header -->
    <section
        class="mb-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8 flex flex-col md:flex-row gap-6 md:items-center justify-between">
        <div>
            <span
                class="mb-2 inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-600">Katalog
                Resmi</span>
            <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Pusat Kebutuhan Listrik</h1>
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

        <div class="mt-5 flex flex-wrap gap-2 pt-4 border-t border-gray-100">
            <a href="{{ route('home', $baseSearchQuery) }}"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ request('category') ? 'border border-gray-200 text-gray-600 hover:bg-gray-50' : 'bg-primary-100 text-primary-700' }}">
                Semua Produk
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('home', array_filter(['q' => $keyword, 'category' => $category->id])) }}"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ (int) request('category') === $category->id ? 'bg-primary-100 text-primary-700' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    {{ $category->name }} <span class="pl-1 text-gray-400">({{ $category->active_products_count }})</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Product Grid -->
    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Produk Tersedia</h2>
            <span class="text-sm font-medium text-gray-500">{{ $products->total() }} produk</span>
        </div>

        @forelse ($products as $product)
            @if ($loop->first)
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @endif

            <article
                class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-primary-200 hover:shadow-lg">
                <a href="{{ route('home.products.show', $product->slug) }}"
                    class="mb-4 block overflow-hidden rounded-xl border border-gray-100 bg-gray-50 aspect-[4/3]">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                </a>

                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-700">
                            {{ $product->name }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ $product->category->name ?? 'Uncategorized' }}</p>

                        @php
                            $avgRating = $product->average_rating;
                            $reviewsCount = $product->reviews_total;
                        @endphp
                        <div class="mt-1.5 flex items-center gap-1.5">
                            <div class="flex items-center text-amber-400">
                                @for ($star = 1; $star <= 5; $star++)
                                    <svg class="h-3.5 w-3.5 {{ $star <= round($avgRating) ? 'fill-current' : 'fill-none text-gray-300' }}"
                                        viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.539 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.719c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-[11px] font-medium text-gray-500">
                                @if ($reviewsCount > 0)
                                    {{ number_format($avgRating, 1) }} ({{ $reviewsCount }} ulasan)
                                @else
                                    Belum ada ulasan
                                @endif
                            </span>
                        </div>
                    </div>
                    <span
                        class="whitespace-nowrap rounded font-bold bg-gray-100 px-2 py-1 text-[10px] uppercase tracking-wider text-gray-600">{{ $product->unit }}</span>
                </div>

                <p class="line-clamp-3 min-h-[60px] text-sm leading-relaxed text-gray-600">
                    {{ $product->description ?: 'Barang berkualitas dari Toko HS ELECTRIC.' }}
                </p>

                <div class="mt-auto pt-4 flex flex-col gap-3">
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                        <p class="text-lg font-black text-gray-900">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                        @if ($product->stock > 0)
                            <p class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Sisa:
                                {{ number_format($product->stock) }}</p>
                        @else
                            <p class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">Habis</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('home.products.show', $product->slug) }}"
                            class="inline-flex w-1/3 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-primary-600">
                            Detail
                        </a>

                        @auth
                            <form method="POST" action="{{ route('home.products.buy', $product->slug) }}" class="w-2/3">
                                @csrf
                                <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-bold transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-400' : 'bg-primary-600 text-white shadow-md shadow-primary-500/20 hover:bg-primary-700' }}">
                                    @if ($product->stock < 1)
                                        Stok Habis
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Keranjang
                                    @endif
                                </button>
                            </form>
                        @else
                            <button type="button"
                                onclick="alert('Silakan login ke akun Anda terlebih dahulu untuk menambah barang ke keranjang dan melakukan pembayaran.')"
                                class="w-2/3 inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-bold transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-400' : 'bg-primary-600 text-white shadow-md shadow-primary-500/20 hover:bg-primary-700' }}">
                                @if ($product->stock < 1)
                                    Stok Habis
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Keranjang
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
