<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Toko Listrik Arip - Solusi Kebutuhan Listrik</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased selection:bg-primary-500 selection:text-white">
    <div class="relative z-10 flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 transition-transform hover:scale-105">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-sm font-extrabold text-white shadow-md shadow-primary-500/30">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-widest text-primary-600 uppercase">Toko Listrik Arip</p>
                        <p class="text-xs font-medium text-gray-500">Pasti Nyala, Pasti Murah</p>
                    </div>
                </a>

                <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
                    <a href="{{ route('home.tracking') }}" class="hidden rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary-500 hover:text-primary-600 sm:block">
                        Cek Pesanan
                    </a>
                    
                    <a href="{{ route('home.cart') }}" class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        @if($cartQuantity > 0)
                            <span class="absolute top-0 right-0 grid h-4 w-4 -translate-y-1/4 translate-x-1/4 place-items-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $cartQuantity }}</span>
                        @endif
                    </a>

                    <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>

                    @guest
                        <a href="{{ route('login') }}" class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
                            Masuk
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                                Daftar
                            </a>
                        @endif
                    @endguest

                    @auth
                        <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:flex">
                            <div class="h-5 w-5 overflow-hidden rounded-full bg-primary-100 text-center leading-5 text-primary-700">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            {{ Auth::user()->name }}
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1 w-full relative">
            <!-- Hero Section -->
            <section class="relative overflow-hidden bg-white py-12 sm:py-20 lg:py-24 border-b border-gray-100">
                <div class="mx-auto flex max-w-7xl flex-col items-center px-4 text-center sm:px-6 lg:px-8">
                    <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary-100 bg-primary-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-primary-600">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                        </span>
                        Toko Buka - Online 24 Jam
                    </span>
                    <h1 class="mx-auto max-w-4xl text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        Pusat Alat Listrik <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-500 to-primary-700">Terpercaya</span> & Termurah.
                    </h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
                        Dari toko pinggir jalan menjadi website e-commerce modern. Temukan koleksi lengkap kabel, saklar, lampu, dan kebutuhan kelistrikan lainnya dengan garansi pengembalian 7 hari.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-4">
                        <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-6 py-3.5 text-base font-semibold text-white shadow-xl shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700 hover:shadow-primary-500/40">
                            Mulai Belanja
                        </a>
                        <a href="#kategori" class="inline-flex items-center justify-center rounded-xl border-2 border-gray-200 bg-white px-6 py-3.5 text-base font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                            Lihat Kategori
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="mt-14 w-full max-w-3xl border-t border-gray-200 pt-8">
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-6 text-center sm:grid-cols-3">
                            <div class="mx-auto flex max-w-xs flex-col gap-y-2">
                                <dt class="text-sm uppercase tracking-wider text-gray-500">Produk Tersedia</dt>
                                <dd class="order-first text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalProducts) }}+</dd>
                            </div>
                            <div class="mx-auto flex max-w-xs flex-col gap-y-2">
                                <dt class="text-sm uppercase tracking-wider text-gray-500">Kategori</dt>
                                <dd class="order-first text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalCategories) }}</dd>
                            </div>
                            <div class="mx-auto flex max-w-xs flex-col gap-y-2">
                                <dt class="text-sm uppercase tracking-wider text-gray-500">Garansi Toko</dt>
                                <dd class="order-first text-3xl font-extrabold tracking-tight text-gray-900">7 Hari</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Keunggulan Section -->
            <section class="bg-gray-50 py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-6 sm:grid-cols-3">
                        <div class="flex flex-col rounded-2xl bg-white p-8 shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Produk Original</h3>
                            <p class="mt-2 text-sm text-gray-600">Kami menjamin 100% barang yang kami jual adalah asli dan berstandar SNI demi keamanan kelistrikan Anda.</p>
                        </div>
                        <div class="flex flex-col rounded-2xl bg-white p-8 shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Transaksi Transparan</h3>
                            <p class="mt-2 text-sm text-gray-600">Harga langsung terlihat jelas tanpa biaya tersembunyi. Pembayaran mudah, rekap pesanan langsung tersimpan.</p>
                        </div>
                        <div class="flex flex-col rounded-2xl bg-white p-8 shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Layanan Garansi</h3>
                            <p class="mt-2 text-sm text-gray-600">Belanja tenang dengan asuransi perlindungan. Ajukan klaim garansi produk dengan mudah via web kami.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Categories Section -->
            <section id="kategori" class="bg-white py-16 border-t border-gray-100">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Jelajahi Kategori</h2>
                            <p class="mt-1 text-sm text-gray-500">Temukan barang incaran berdasarkan kelompok kategori.</p>
                        </div>
                        <a href="{{ route('home') }}" class="hidden sm:inline-flex text-sm font-semibold text-primary-600 hover:text-primary-700">
                            Semua Produk &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @forelse ($featuredCategories as $category)
                            <a href="{{ route('home', ['category' => $category->id]) }}" class="group relative flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 p-6 transition-all hover:-translate-y-1 hover:border-primary-200 hover:bg-primary-50 hover:shadow-lg hover:shadow-primary-100">
                                <div class="mb-4 grid h-16 w-16 place-items-center rounded-full bg-white shadow-sm transition-transform group-hover:scale-110">
                                    <svg class="h-8 w-8 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <h3 class="text-center text-sm font-bold text-gray-900 group-hover:text-primary-700">{{ $category->name }}</h3>
                                <p class="mt-1 text-xs text-gray-500">{{ number_format($category->active_products_count) }} Produk</p>
                            </a>
                        @empty
                            <div class="col-span-full rounded-2xl border-2 border-dashed border-gray-200 p-8 text-center text-gray-500">
                                Belum ada kategori aktif.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>

        <footer class="mt-auto bg-gray-900 py-8 text-center text-gray-400">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-sm">&copy; {{ date('Y') }} Toko Listrik Arip. Aman, Murah, Terpercaya.</p>
            </div>
        </footer>
    </div>
</body>

</html>

