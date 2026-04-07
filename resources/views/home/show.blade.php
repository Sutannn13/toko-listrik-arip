<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $product->name }} - Toko Listrik Arip</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-36 left-0 h-96 w-96 rounded-full bg-cyan-500/20 blur-3xl"></div>
        <div class="absolute top-1/3 -right-28 h-[24rem] w-[24rem] rounded-full bg-blue-600/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-teal-500/15 blur-3xl"></div>
    </div>

    <div class="relative z-10">
        <header class="sticky top-0 z-30 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
            <div
                class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span
                        class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 text-sm font-extrabold text-white">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-[0.08em] text-white">TOKO LISTRIK ARIP</p>
                        <p class="text-xs text-cyan-200/80">Detail Produk</p>
                    </div>
                </a>

                <a href="{{ route('home.cart') }}"
                    class="hidden rounded-xl border px-4 py-2 text-sm transition sm:block {{ $cartQuantity > 0 ? 'border-cyan-400/60 bg-cyan-500/10 text-cyan-100 hover:border-cyan-300' : 'border-slate-700 bg-slate-900/80 text-slate-300 hover:border-cyan-400/60 hover:text-cyan-200' }}">
                    Keranjang: <span class="font-bold text-cyan-300">{{ number_format($cartQuantity) }}</span> item
                </a>

                <nav class="flex items-center gap-2">
                    @guest
                        <a href="{{ route('login') }}"
                            class="rounded-xl border border-cyan-400/40 px-4 py-2 text-sm font-semibold text-cyan-200 transition hover:border-cyan-300 hover:bg-cyan-400/10">
                            Masuk
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:brightness-110">
                                Daftar
                            </a>
                        @endif
                    @endguest

                    @auth
                        @php
                            $userPrimaryRole = Auth::user()->getRoleNames()->first();
                            $userRoleLabel = \Illuminate\Support\Str::headline(
                                str_replace('-', ' ', $userPrimaryRole ?? 'user'),
                            );
                        @endphp

                        @if (Auth::user()->hasAnyRole(['super-admin', 'admin']))
                            <a href="{{ route('admin.dashboard') }}"
                                class="hidden rounded-xl border border-cyan-400/40 px-4 py-2 text-sm font-semibold text-cyan-200 transition hover:border-cyan-300 hover:bg-cyan-400/10 sm:inline-flex">
                                Admin Panel
                            </a>
                        @endif

                        <a href="{{ route('profile.edit') }}"
                            class="hidden rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-800 sm:inline-flex">
                            {{ Auth::user()->name }} ({{ $userRoleLabel }})
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="rounded-xl border border-rose-400/40 px-4 py-2 text-sm font-semibold text-rose-200 transition hover:border-rose-300 hover:bg-rose-400/10">
                                Logout
                            </button>
                        </form>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            @if (session('success'))
                <div
                    class="mb-6 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mb-6 rounded-2xl border border-red-400/25 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('home') }}" class="text-cyan-300 transition hover:text-cyan-200">Katalog</a>
                <span class="text-slate-500">/</span>
                <span class="text-slate-300">{{ $product->category->name ?? 'Tanpa Kategori' }}</span>
                <span class="text-slate-500">/</span>
                <span class="text-white">{{ $product->name }}</span>
            </div>

            <section class="grid gap-6 lg:grid-cols-[1.25fr,1fr]">
                <article
                    class="rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6 shadow-2xl shadow-slate-950/40 sm:p-8">
                    <p
                        class="mb-3 inline-flex rounded-full border border-cyan-400/40 bg-cyan-500/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-cyan-200">
                        {{ $product->category->name ?? 'Produk' }}
                    </p>

                    <h1 class="text-2xl font-extrabold leading-tight text-white sm:text-4xl">
                        {{ $product->name }}
                    </h1>

                    <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                        {{ $product->description ?: 'Belum ada deskripsi produk untuk item ini.' }}
                    </p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Harga</p>
                            <p class="mt-2 text-xl font-black text-cyan-300">Rp
                                {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Stok</p>
                            <p
                                class="mt-2 text-xl font-black {{ $product->stock > 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                {{ number_format($product->stock) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Satuan</p>
                            <p class="mt-2 text-xl font-black text-slate-100">{{ strtoupper($product->unit) }}</p>
                        </div>
                    </div>

                    @if (is_array($product->specifications) && count($product->specifications) > 0)
                        <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                            <h2 class="text-sm font-bold uppercase tracking-[0.12em] text-slate-200">Spesifikasi</h2>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($product->specifications as $key => $value)
                                    <div class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm">
                                        <span
                                            class="font-semibold text-slate-300">{{ \Illuminate\Support\Str::headline((string) $key) }}:</span>
                                        <span class="text-slate-100">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>

                <aside class="rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-white">Tombol Beli Sederhana</h2>
                    <p class="mt-2 text-sm text-slate-300">
                        Klik beli untuk menambahkan produk ini ke keranjang sederhana (berbasis session).
                    </p>

                    <p class="mt-3 rounded-xl border border-cyan-500/25 bg-cyan-500/10 px-3 py-2 text-xs text-cyan-200">
                        Garansi Toko Arip 7 hari setelah barang diterima.
                    </p>

                    <form method="POST" action="{{ route('home.products.buy', $product->slug) }}"
                        class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label for="qty" class="mb-2 block text-sm font-semibold text-slate-200">Jumlah
                                Beli</label>
                            <input id="qty" name="qty" type="number" min="1"
                                max="{{ max(1, (int) $product->stock) }}" value="1"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-white focus:border-cyan-400 focus:outline-none">
                            @error('qty')
                                <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                            class="inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-bold transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-slate-700 bg-slate-800 text-slate-500' : 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg shadow-cyan-900/30 hover:brightness-110' }}">
                            {{ $product->stock < 1 ? 'Stok Habis' : 'Beli Sekarang' }}
                        </button>
                    </form>

                    <a href="{{ route('home') }}"
                        class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/60 hover:text-cyan-200">
                        Kembali ke Katalog
                    </a>
                </aside>
            </section>

            <section class="mt-8">
                <h2 class="mb-4 text-lg font-bold text-white">Produk Terkait</h2>

                @if ($relatedProducts->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($relatedProducts as $related)
                            <article
                                class="rounded-2xl border border-slate-800/80 bg-slate-900/75 p-4 transition hover:-translate-y-1 hover:border-cyan-400/40 hover:shadow-lg hover:shadow-cyan-900/20">
                                <h3 class="text-base font-bold text-white">{{ $related->name }}</h3>
                                <p class="mt-1 text-xs text-slate-400">{{ strtoupper($related->unit) }}</p>
                                <p class="mt-3 text-lg font-extrabold text-cyan-300">Rp
                                    {{ number_format($related->price, 0, ',', '.') }}</p>
                                <a href="{{ route('home.products.show', $related->slug) }}"
                                    class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/60 hover:text-cyan-200">
                                    Lihat Detail
                                </a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/80 p-5 text-sm text-slate-400">
                        Belum ada produk terkait lainnya untuk ditampilkan.
                    </div>
                @endif
            </section>
        </main>
    </div>
</body>

</html>
