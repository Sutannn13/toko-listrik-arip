@extends('layouts.storefront')

@section('title', 'Toko HS ELECTRIC - Solusi Kebutuhan Listrik')
@section('body_class',
    'min-h-screen bg-slate-100 font-sans text-slate-800 antialiased selection:bg-primary-500
    selection:text-white')
@section('show_default_store_actions', 'off')
@section('show_header', 'on')
@section('main_container_class', 'flex-1 w-full flex flex-col')

@section('background')
    <div class="fixed inset-0 z-0">
        <img src="{{ asset('img/hero-bg.jpg') }}" alt="{{ \App\Models\Setting::get('store_name', 'Toko') }} Background"
            class="h-full w-full scale-[1.02] object-cover object-center blur-[1px]"
            onerror="this.src='https://plus.unsplash.com/premium_photo-1678735398755-d6c1df88facb?q=80&w=2940&auto=format&fit=crop';" />
        <div class="absolute inset-0 bg-slate-950/52 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-emerald-950/70 via-slate-900/35 to-slate-900/72"></div>
    </div>
@endsection

@section('header')
    <header x-data="{
        show: true,
        lastScrollY: window.scrollY,
        scrolled() {
            const currentScrollY = window.scrollY;
            if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
                this.show = false;
            } else {
                this.show = true;
            }
            this.lastScrollY = currentScrollY;
        }
    }" @scroll.window="scrolled" :class="show ? 'translate-y-0' : '-translate-y-full'"
        class="fixed top-0 left-0 right-0 z-50 border-b border-white/25 bg-emerald-900/18 backdrop-blur-sm transition-transform duration-300 ease-in-out">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('landing') }}" class="flex items-center transition-transform hover:scale-105">
                <img src="{{ asset('img/gemini_generated_image.png') }}"
                    alt="{{ \App\Models\Setting::get('store_name', 'Toko') }}"
                    class="h-14 w-auto object-contain drop-shadow-[0_2px_8px_rgba(15,20,38,0.38)] sm:h-16">
            </a>

            <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
                @auth
                    <a href="{{ route('home.tracking') }}"
                        class="hidden rounded-lg border border-white/35 bg-white/15 px-4 py-2 text-sm font-semibold text-white backdrop-blur-sm transition hover:border-primary-300 hover:text-primary-100 sm:block">
                        Cek Pesanan
                    </a>

                    <a href="{{ route('home.cart') }}"
                        class="relative rounded-lg p-2 text-white/85 transition hover:bg-white/20 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 drop-shadow-sm" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        @if ($cartQuantity > 0)
                            <span
                                class="absolute top-0 right-0 grid h-4 w-4 -translate-y-1/4 translate-x-1/4 place-items-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $cartQuantity }}</span>
                        @endif
                    </a>

                    <div class="h-6 w-px bg-white/20 hidden sm:block"></div>
                @endauth

                @guest
                    <a href="{{ route('login') }}"
                        class="rounded-lg border border-white/60 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10 backdrop-blur-sm">
                        Masuk
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-primary-600/30 transition hover:bg-primary-400">
                            Daftar
                        </a>
                    @endif
                @endguest

                @auth
                    <a href="{{ route('profile.edit') }}"
                        class="hidden items-center gap-2 rounded-lg border border-white/30 bg-white/12 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20 backdrop-blur-sm sm:flex">
                        <div class="h-5 w-5 overflow-hidden rounded-full bg-primary-500 text-center leading-5 text-white">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        {{ Auth::user()->name }}
                    </a>
                @endauth
            </div>
        </div>
    </header>
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="py-16 sm:py-24 lg:py-32">
        <div
            class="mx-auto flex max-w-7xl flex-col items-center rounded-3xl border border-white/15 bg-slate-950/20 px-4 py-10 text-center shadow-[0_20px_60px_rgba(2,6,23,0.32)] backdrop-blur-[2px] sm:px-6 lg:px-10 lg:py-14">
            <span
                class="mb-5 inline-flex items-center gap-2 rounded-full border border-primary-200/45 bg-white/15 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-primary-100 backdrop-blur-sm">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-300 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-300"></span>
                </span>
                Toko Buka - Online 24 Jam
            </span>
            <h1
                class="mx-auto max-w-4xl text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl drop-shadow-md">
                Pusat Alat Listrik <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-primary-100 to-emerald-100">Terpercaya</span>
                & Termurah.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-100/90 drop-shadow-sm">
                Dari toko pinggir jalan menjadi website e-commerce modern. Temukan koleksi lengkap kabel,
                saklar, lampu, dan kebutuhan kelistrikan lainnya dengan garansi pengembalian 7 hari.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="{{ route('home') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-primary-500 px-6 py-3.5 text-base font-semibold text-white shadow-xl shadow-primary-600/30 transition hover:-translate-y-0.5 hover:bg-primary-400 hover:shadow-primary-500/40">
                    Mulai Belanja
                </a>
                <a href="#kategori"
                    class="inline-flex items-center justify-center rounded-xl border border-white/45 bg-white/15 px-6 py-3.5 text-base font-semibold text-white backdrop-blur-sm transition hover:border-white/70 hover:bg-white/20">
                    Lihat Kategori
                </a>
            </div>

            <!-- Stats -->
            <div class="mt-14 w-full max-w-3xl border-t border-white/40 pt-8">
                <dl class="grid grid-cols-1 gap-x-8 gap-y-6 text-center sm:grid-cols-3">
                    <div class="mx-auto flex max-w-xs flex-col gap-y-2">
                        <dt class="text-sm uppercase tracking-wider text-slate-100/85">Produk Tersedia</dt>
                        <dd class="order-first text-3xl font-extrabold tracking-tight text-white">
                            {{ number_format($totalProducts) }}+</dd>
                    </div>
                    <div class="mx-auto flex max-w-xs flex-col gap-y-2">
                        <dt class="text-sm uppercase tracking-wider text-slate-100/85">Kategori</dt>
                        <dd class="order-first text-3xl font-extrabold tracking-tight text-white">
                            {{ number_format($totalCategories) }}</dd>
                    </div>
                    <div class="mx-auto flex max-w-xs flex-col gap-y-2">
                        <dt class="text-sm uppercase tracking-wider text-slate-100/85">Garansi Toko</dt>
                        <dd class="order-first text-3xl font-extrabold tracking-tight text-white">7 Hari</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <!-- Keunggulan Section (Glassmorphism) -->
    <section class="relative border-y border-slate-200 bg-slate-100 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-3">
                <div
                    class="flex flex-col rounded-2xl border border-slate-200 bg-white p-8 shadow-xl transition duration-300 hover:-translate-y-1 hover:border-primary-300/70 hover:shadow-emerald-900/15">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/20 text-emerald-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Produk Original</h3>
                    <p class="mt-2 text-sm text-slate-600">Kami menjamin 100% barang yang kami jual adalah asli
                        dan berstandar SNI demi keamanan kelistrikan Anda.</p>
                </div>
                <div
                    class="flex flex-col rounded-2xl border border-slate-200 bg-white p-8 shadow-xl transition duration-300 hover:-translate-y-1 hover:border-primary-300/70 hover:shadow-emerald-900/15">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full border border-primary-500/30 bg-primary-500/20 text-primary-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Transaksi Transparan</h3>
                    <p class="mt-2 text-sm text-slate-600">Harga langsung terlihat jelas tanpa biaya
                        tersembunyi. Pembayaran mudah, rekap pesanan langsung tersimpan.</p>
                </div>
                <div
                    class="flex flex-col rounded-2xl border border-slate-200 bg-white p-8 shadow-xl transition duration-300 hover:-translate-y-1 hover:border-primary-300/70 hover:shadow-emerald-900/15">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full border border-primary-400/35 bg-primary-400/20 text-primary-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Layanan Garansi</h3>
                    <p class="mt-2 text-sm text-slate-600">Belanja tenang dengan asuransi perlindungan. Ajukan
                        klaim garansi produk dengan mudah via web kami.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section (Glassmorphism) -->
    <section id="kategori" class="relative border-t border-slate-200 bg-slate-100 py-16 pb-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-300/35 sm:p-8">
                <div class="mb-8 flex items-end justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Jelajahi Kategori</h2>
                        <p class="mt-1 text-sm font-medium text-slate-600">Temukan barang incaran berdasarkan kelompok
                            kategori.
                        </p>
                    </div>
                    <a href="{{ route('home') }}"
                        class="hidden sm:inline-flex text-sm font-bold text-primary-700 underline decoration-primary-300 underline-offset-4 transition hover:text-primary-800">
                        Semua Produk &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @forelse ($featuredCategories as $category)
                        <a href="{{ route('home', ['category' => $category->id]) }}"
                            class="group relative flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-6 transition-all hover:-translate-y-1 hover:border-primary-300/70 hover:shadow-lg hover:shadow-emerald-900/15">
                            <div
                                class="mb-4 grid h-16 w-16 place-items-center rounded-full border border-slate-200 bg-slate-100/95 shadow-inner transition-transform group-hover:scale-110 group-hover:border-primary-300/70">
                                <svg class="h-8 w-8 text-primary-600 group-hover:text-primary-700 transition"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3
                                class="text-center text-base font-extrabold text-slate-900 group-hover:text-primary-700 transition">
                                {{ $category->name }}</h3>
                            <p class="mt-1 text-sm font-medium text-slate-600">
                                {{ number_format($category->active_products_count) }} Produk</p>
                        </a>
                    @empty
                        <div
                            class="col-span-full rounded-2xl border-2 border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                            Belum ada kategori aktif.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection

@section('footer')
    @include('layouts.partials.flowbite-footer', ['footerClass' => 'bg-slate-900/95'])
@endsection
