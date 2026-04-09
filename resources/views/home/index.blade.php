<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Toko Listrik Arip - Katalog Produk</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $baseSearchQuery = array_filter([
        'q' => $keyword,
    ]);
@endphp

<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased selection:bg-primary-500 selection:text-white">
    <div class="relative z-10 flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white shadow-sm">
            <div
                class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 transition-transform hover:scale-105">
                    <span
                        class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-sm font-extrabold text-white shadow-md shadow-primary-500/30">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-widest text-primary-600 uppercase">Toko Listrik Arip</p>
                        <p class="text-xs font-medium text-gray-500">Pasti Nyala, Pasti Murah</p>
                    </div>
                </a>

                <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
                    @auth
                        <a href="{{ route('home.tracking') }}"
                            class="hidden rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary-500 hover:text-primary-600 sm:block">
                            Cek Pesanan
                        </a>

                        <a href="{{ route('home.cart') }}"
                            class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            @if ($cartQuantity > 0)
                                <span
                                    class="absolute top-0 right-0 grid h-4 w-4 -translate-y-1/4 translate-x-1/4 place-items-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $cartQuantity }}</span>
                            @endif
                        </a>

                        <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}"
                            class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
                            Masuk
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="hidden sm:inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                                Daftar
                            </a>
                        @endif
                    @endguest

                    @auth
                        @php
                            $userPrimaryRole = Auth::user()->getRoleNames()->first();
                        @endphp
                        @if (Auth::user()->hasAnyRole(['super-admin', 'admin']))
                            <a href="{{ route('admin.dashboard') }}"
                                class="hidden rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 sm:inline-flex">
                                Admin Panel
                            </a>
                        @endif
                        <a href="{{ route('profile.edit') }}"
                            class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:flex">
                            <div
                                class="h-5 w-5 overflow-hidden rounded-full bg-primary-100 text-center leading-5 text-primary-700">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            {{ Auth::user()->name }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                            @csrf
                            <button type="submit"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1 w-full mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div
                    class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-sm">
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

                <form method="GET" action="{{ route('home') }}"
                    class="grid gap-3 lg:grid-cols-[1.5fr,1fr,auto,auto]">
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
                            {{ $category->name }} <span
                                class="pl-1 text-gray-400">({{ $category->active_products_count }})</span>
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
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-700">
                                    {{ $product->name }}</h3>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $product->category->name ?? 'Uncategorized' }}</p>
                            </div>
                            <span
                                class="whitespace-nowrap rounded font-bold bg-gray-100 px-2 py-1 text-[10px] uppercase tracking-wider text-gray-600">{{ $product->unit }}</span>
                        </div>

                        <p class="line-clamp-3 min-h-[60px] text-sm leading-relaxed text-gray-600">
                            {{ $product->description ?: 'Barang berkualitas dari Toko Listrik Arip.' }}
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
                                    <form method="POST" action="{{ route('home.products.buy', $product->slug) }}"
                                        class="w-2/3">
                                        @csrf
                                        <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-bold transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-400' : 'bg-primary-600 text-white shadow-md shadow-primary-500/20 hover:bg-primary-700' }}">
                                            @if ($product->stock < 1)
                                                Stok Habis
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
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
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
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
    </main>

    <footer class="mt-auto bg-gray-900 py-8 text-center text-gray-400">
        <div
            class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-sm">&copy; {{ date('Y') }} Toko Listrik Arip. Hak Cipta Dilindungi.</p>
            <div class="flex gap-4">
                <a href="#" class="hover:text-white transition">Tentang Kami</a>
                <a href="#" class="hover:text-white transition">Syarat & Ketentuan</a>
            </div>
        </div>
    </footer>
    </div>
</body>

</html>
