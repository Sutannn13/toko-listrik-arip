@extends('layouts.storefront')

@section('title', $product->name . ' - Toko Listrik Arip')
@section('header_subtitle', 'Detail Produk')
@section('main_container_class', 'mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12')

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('home') }}" class="font-medium text-primary-600 transition hover:text-primary-700">Katalog</a>
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
                    <p class="mt-2 text-2xl font-black {{ $product->stock > 0 ? 'text-green-600' : 'text-red-500' }}">
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
                                <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0 sm:border-0 sm:pb-0">
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
            <div class="sticky top-24 rounded-3xl border border-gray-200 bg-white p-6 shadow-xl shadow-gray-200/50 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900">Pembelian</h2>
                <p class="mt-2 text-sm text-gray-500">Masukkan barang ini ke keranjang belanja Anda untuk
                    diproses lebih lanjut.</p>

                @if ($product->is_electronic)
                    <div
                        class="mt-4 flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Produk elektronik: garansi klaim maksimal 7 hari.
                    </div>
                @else
                    <div
                        class="mt-4 flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-3 text-sm font-medium text-gray-700">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01"></path>
                        </svg>
                        Produk non-elektronik: tidak termasuk klaim garansi.
                    </div>
                @endif

                @auth
                    <form method="POST" action="{{ route('home.products.buy', $product->slug) }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="qty" class="mb-2 block text-sm font-bold text-gray-700">Kuantitas
                                ({{ strtoupper($product->unit) }})
                            </label>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                    </path>
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
                                ({{ strtoupper($product->unit) }})
                            </label>
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
            <a href="{{ route('home') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lihat Semua
                &rarr;</a>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($relatedProducts as $related)
                    <article
                        class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100">
                        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                            <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">
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
@endsection
