@extends('layouts.storefront')

@section('title', 'Keranjang Belanja - Toko HS ELECTRIC')
@section('header_subtitle', 'Keranjang User')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8')

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

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('checkout_order_code'))
        <div
            class="mb-6 overflow-hidden rounded-2xl border border-cyan-200 bg-gradient-to-br from-slate-50 via-cyan-50 to-sky-100 shadow-sm">
            <div class="border-b border-cyan-100/80 bg-white/70 px-5 py-3 backdrop-blur-sm">
                <p class="text-sm font-bold text-slate-800">
                    Pesanan baru berhasil dibuat: <span
                        class="font-black text-cyan-900">{{ session('checkout_order_code') }}</span>
                </p>
                <p class="mt-1 text-xs text-slate-600">Lanjutkan cek status pesanan dan upload bukti pembayaran bila
                    diperlukan.</p>
            </div>
            <div class="px-5 py-4">
                <div class="mb-4 grid gap-2 sm:grid-cols-3">
                    <div class="rounded-xl border border-cyan-100 bg-white/80 px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status Awal</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">Pesanan Dibuat</p>
                    </div>
                    <div class="rounded-xl border border-cyan-100 bg-white/80 px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Langkah Berikutnya</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">Cek Detail Pesanan</p>
                    </div>
                    <div class="rounded-xl border border-cyan-100 bg-white/80 px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pembayaran</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">Upload Bukti bila diminta</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('home.tracking.show', session('checkout_order_code')) }}"
                        class="ui-btn ui-btn-primary">
                        Cek Status Pesanan
                    </a>
                    <a href="{{ route('home.transactions') }}" class="ui-btn ui-btn-secondary">
                        Lihat Riwayat Transaksi
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Keranjang Belanja</h1>
            <p class="mt-1 text-sm text-gray-600">
                Periksa kembali pesanan Anda sebelum melanjutkan ke pembayaran.
            </p>
        </div>

        <a href="{{ route('home') }}"
            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Katalog
        </a>
    </div>

    @if ($cartItems->isEmpty())
        <section class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Keranjang masih kosong</h2>
            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">Belum ada produk di keranjang Anda. Yuk
                temukan berbagai alat listrik menarik di katalog kami.</p>
            <a href="{{ route('home') }}"
                class="mt-6 inline-flex rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                Mulai Belanja
            </a>
        </section>
    @else
        <section class="grid gap-8 lg:grid-cols-[1.5fr,1fr]">

            {{-- Kiri: Daftar Item Keranjang --}}
            <div class="space-y-4">
                @foreach ($cartItems as $item)
                    <article
                        class="grid grid-cols-[5.5rem,1fr] gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-100 hover:shadow-md sm:grid-cols-[5rem,1fr,auto] sm:items-center">
                        <div
                            class="h-24 w-24 shrink-0 self-start overflow-hidden rounded-xl border border-gray-200 bg-gray-50 sm:h-20 sm:w-20">
                            <img src="{{ $item['image_url'] ?? asset('img/hero-bg.jpg') }}" alt="{{ $item['name'] }}"
                                class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900">{{ $item['name'] }}</h3>
                            <p class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ $item['unit'] }}</p>
                            @if ($item['is_available'] && $item['slug'] !== '')
                                <a href="{{ route('home.products.show', $item['slug']) }}"
                                    class="mt-2 text-sm font-semibold text-primary-600 hover:underline">
                                    Detail produk
                                </a>
                            @endif
                        </div>

                        <div class="col-span-2 flex min-w-0 flex-col gap-3 sm:col-span-1 sm:min-w-[200px] sm:items-end">
                            <div class="text-right">
                                <p class="text-lg font-black text-gray-900">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                </p>
                                <p class="text-xs font-medium text-gray-500 mt-0.5">
                                    Subtotal: Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </p>
                            </div>

                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('home.cart.update', $item['product_id']) }}"
                                    class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="qty" min="1"
                                        max="{{ max(1, (int) $item['stock']) }}" value="{{ $item['qty'] }}"
                                        class="w-16 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-center text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    <button type="submit"
                                        class="rounded-lg bg-gray-100 p-2 text-gray-600 hover:bg-gray-200 transition"
                                        title="Update Kuantitas">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('home.cart.remove', $item['product_id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100 transition"
                                        title="Hapus dari Keranjang">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <div class="text-right mt-1">
                                @if (!$item['is_available'])
                                    <span class="text-xs font-semibold text-red-600">Tidak tersedia</span>
                                @else
                                    <span class="text-xs font-medium text-green-600">Stok:
                                        {{ number_format($item['stock']) }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Kanan: Ringkasan Pesanan --}}
            <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:sticky lg:top-24 lg:self-start">
                <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-4">Ringkasan Pesanan</h2>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Total Item</span>
                        <span class="font-bold text-gray-900">{{ number_format($totalQuantity) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Ongkir / Item</span>
                        <span class="font-semibold text-gray-900">Rp
                            {{ number_format($shippingCostPerItem, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-base">
                        <span class="text-gray-600 font-medium">Subtotal</span>
                        <span class="text-xl font-black text-primary-600">Rp
                            {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Total Ongkir</span>
                        <span class="font-semibold text-gray-900">Rp
                            {{ number_format($shippingCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex items-center justify-between text-base">
                        <span class="text-gray-900 font-bold">Total Bayar</span>
                        <span class="text-xl font-black text-primary-700">Rp
                            {{ number_format($totalAmount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <a href="{{ route('home.checkout') }}"
                    class="w-full mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3.5 text-base font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30">
                    Checkout & Bayar
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>

                <a href="{{ route('home') }}"
                    class="w-full mt-3 inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Lanjut Belanja
                </a>
            </aside>
        </section>
    @endif

@endsection
