<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $product->name }} - Toko Listrik Arip</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

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
                        <p class="text-xs font-medium text-gray-500">Detail Produk</p>
                    </div>
                </a>

                <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
                    @auth
                        <a href="{{ route('home.tracking') }}"
                            class="hidden rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary-500 hover:text-primary-600 sm:block">
                            Cek Pesanan
                        </a>

                        <a href="{{ route('home.cart') }}"
                            class="relative rounded-lg p-2 transition {{ $cartQuantity > 0 ? 'bg-primary-50 text-primary-600' : 'text-gray-500 hover:bg-gray-100 hover:text-primary-600' }}">
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

        <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            @if (session('success'))
                <div
                    class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('home') }}"
                    class="font-medium text-primary-600 transition hover:text-primary-700">Katalog</a>
                <span class="text-gray-400">/</span>
                <span class="font-medium text-gray-500">{{ $product->category->name ?? 'Tanpa Kategori' }}</span>
                <span class="text-gray-400">/</span>
                <span class="font-bold text-gray-900">{{ $product->name }}</span>
            </div>

            <section class="grid gap-8 lg:grid-cols-[1.5fr,1fr] xl:gap-12">
                <!-- Deskripsi Produk -->
                <article class="flex flex-col">
                    <div class="mb-6 inline-block">
                        <span
                            class="inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-bold uppercase tracking-widest text-primary-700">
                            {{ $product->category->name ?? 'Produk Resmi' }}
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold leading-tight text-gray-900 sm:text-4xl lg:text-5xl">
                        {{ $product->name }}
                    </h1>

                    <p class="mt-6 text-base leading-relaxed text-gray-600 sm:text-lg">
                        {{ $product->description ?: 'Barang berkualitas & berstandar SNI dari Toko Listrik Arip.' }}
                    </p>

                    <div class="mt-8 grid gap-4 grid-cols-2 sm:grid-cols-3">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Harga Spesial</p>
                            <p class="mt-2 text-2xl font-black text-primary-600">Rp
                                {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Stok Toko</p>
                            <p
                                class="mt-2 text-2xl font-black {{ $product->stock > 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ number_format($product->stock) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm col-span-2 sm:col-span-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Satuan Beli</p>
                            <p class="mt-2 text-2xl font-black text-gray-900">{{ strtoupper($product->unit) }}</p>
                        </div>
                    </div>

                    @if (is_array($product->specifications) && count($product->specifications) > 0)
                        <div class="mt-8 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-700">Spesifikasi
                                    Lengkap</h2>
                            </div>
                            <div class="px-6 py-4">
                                <dl class="grid gap-y-4 gap-x-6 sm:grid-cols-2">
                                    @foreach ($product->specifications as $key => $value)
                                        <div
                                            class="border-b border-gray-100 pb-3 last:border-0 last:pb-0 sm:border-0 sm:pb-0">
                                            <dt class="text-xs font-bold text-gray-500 mb-1">
                                                {{ \Illuminate\Support\Str::headline((string) $key) }}</dt>
                                            <dd class="text-sm font-semibold text-gray-900">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    @endif
                </article>

                <!-- Box Checkout / Cart -->
                <aside class="flex flex-col">
                    <div
                        class="sticky top-24 rounded-3xl border border-gray-200 bg-white p-6 shadow-xl shadow-gray-200/50 sm:p-8">
                        <h2 class="text-xl font-bold text-gray-900">Pembelian</h2>
                        <p class="mt-2 text-sm text-gray-500">Masukkan barang ini ke keranjang belanja Anda untuk
                            diproses lebih lanjut.</p>

                        <div
                            class="mt-4 flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Garansi pengembalian 7 hari.
                        </div>

                        @auth
                            <form method="POST" action="{{ route('home.products.buy', $product->slug) }}"
                                class="mt-6 space-y-5">
                                @csrf
                                <div>
                                    <label for="qty" class="mb-2 block text-sm font-bold text-gray-700">Kuantitas
                                        ({{ strtoupper($product->unit) }})</label>
                                    <div class="flex items-center">
                                        <input id="qty" name="qty" type="number" min="1"
                                            max="{{ max(1, (int) $product->stock) }}" value="1"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition text-center font-bold">
                                    </div>
                                    @error('qty')
                                        <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-base font-bold shadow-md transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-500 shadow-none' : 'bg-primary-600 text-white shadow-primary-500/20 hover:bg-primary-700 hover:shadow-primary-500/40' }}">
                                    @if ($product->stock < 1)
                                        Stok Habis
                                    @else
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Tambah ke Keranjang
                                    @endif
                                </button>
                            </form>
                        @endauth

                        @guest
                            <div class="mt-6 space-y-5">
                                <div>
                                    <label for="qty-guest" class="mb-2 block text-sm font-bold text-gray-700">Kuantitas
                                        ({{ strtoupper($product->unit) }})</label>
                                    <input id="qty-guest" type="number" value="1" disabled
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-base text-gray-500 shadow-sm transition text-center font-bold cursor-not-allowed">
                                </div>
                                <button type="button"
                                    onclick="alert('Peringatan: Anda harus masuk/login ke sistem terlebih dahulu sebelum dapat menambahkan barang ke keranjang belanja.')"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-base font-bold shadow-md transition bg-gray-600 text-white shadow-gray-500/20 hover:bg-gray-700">
                                    Tambah ke Keranjang
                                </button>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-gray-500">Atau <a href="{{ route('login') }}"
                                            class="text-primary-600 hover:underline">Masuk</a> sekarang</p>
                                </div>
                            </div>
                        @endguest

                        <a href="{{ route('home') }}"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                            Kembali Belanja
                        </a>
                    </div>
                </aside>
            </section>

            <!-- Terkait -->
            <section class="mt-16 border-t border-gray-200 pt-12">
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="text-2xl font-extrabold text-gray-900">Produk Terkait</h2>
                    <a href="{{ route('home') }}"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lihat Semua &rarr;</a>
                </div>

                @if ($relatedProducts->isNotEmpty())
                    <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($relatedProducts as $related)
                            <article
                                class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100">
                                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                    <h3
                                        class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">
                                        {{ $related->name }}</h3>
                                    <span
                                        class="rounded bg-gray-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-600">{{ $related->unit }}</span>
                                </div>

                                <div class="mt-auto pt-4 flex flex-col gap-3">
                                    <p class="text-lg font-black text-primary-600 border-t border-gray-100 pt-3">
                                        Rp {{ number_format($related->price, 0, ',', '.') }}
                                    </p>
                                    <a href="{{ route('home.products.show', $related->slug) }}"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-gray-800">
                                        Lihat Detail
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div
                        class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center text-gray-500 font-medium">
                        Belum ada produk dari kategori yang sama.
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
