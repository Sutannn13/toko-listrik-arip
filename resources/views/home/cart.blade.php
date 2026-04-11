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
        <div class="mb-6 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-4 shadow-sm">
            <p class="text-sm font-semibold text-cyan-900">
                Pesanan baru berhasil dibuat: <span class="font-black">{{ session('checkout_order_code') }}</span>
            </p>
            <p class="mt-1 text-xs text-cyan-700">Lanjutkan dengan cek status dan upload bukti pembayaran bila diperlukan.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('home.tracking', ['order_code' => session('checkout_order_code')]) }}"
                    class="ui-btn ui-btn-primary">
                    Cek Status Pesanan
                </a>
                <a href="{{ route('home.transactions') }}" class="ui-btn ui-btn-secondary">
                    Lihat Riwayat Transaksi
                </a>
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Keranjang Belanja</h1>
            <p class="mt-1 text-sm text-gray-600">
                Periksa kembali pesanan Anda sebelum checkout.
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

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <a href="{{ route('home.transactions') }}"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
            Riwayat Transaksi
        </a>
        <a href="{{ route('home.warranty') }}"
            class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
            Pusat Garansi
        </a>
        <a href="{{ route('home.warranty-claims.index') }}"
            class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
            Riwayat Klaim
        </a>
        <a href="{{ route('home.notifications.index') }}"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
            Notifikasi
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
            <div class="space-y-4">
                @foreach ($cartItems as $item)
                    <article
                        class="flex flex-col sm:flex-row gap-4 sm:items-center rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex-1">
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

                        <div class="flex flex-col gap-3 min-w-[200px]">
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

            <aside class="sticky top-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm self-start">
                <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-4">Ringkasan Pesanan
                </h2>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Total Item</span>
                        <span class="font-bold text-gray-900">{{ number_format($totalQuantity) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-base">
                        <span class="text-gray-600 font-medium">Subtotal</span>
                        <span class="text-xl font-black text-primary-600">Rp
                            {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 flex gap-3 text-sm text-blue-800">
                        <svg class="h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>Garansi klaim hanya berlaku untuk produk <strong>elektronik</strong> dengan masa
                            maksimal <strong>7 hari</strong>.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('home.cart.checkout') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">1</span>
                            Data Pelanggan
                        </h3>
                        <div class="grid gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Nama
                                    Lengkap</label>
                                <input type="text" name="customer_name"
                                    value="{{ old('customer_name', auth()->user()->name ?? '') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    placeholder="Nama Anda" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Email</label>
                                <input type="email" name="customer_email"
                                    value="{{ old('customer_email', auth()->user()->email ?? '') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    placeholder="Email aktif" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Nomor HP</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    placeholder="Contoh: 0812xxxxxx" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">2</span>
                            Alamat Pengiriman
                        </h3>

                        <div>
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">3</span>
                            Metode Pembayaran
                        </h3>
                        
                        <div class="grid sm:grid-cols-3 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="bank_transfer" class="peer sr-only" required>
                                <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <div class="rounded-full bg-blue-100 p-2 text-blue-600 peer-checked:bg-primary-200 peer-checked:text-primary-700">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">Transfer Bank</p>
                                            <p class="text-[10px] text-gray-500 mt-1">BCA, Mandiri, BRI</p>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="ewallet" class="peer sr-only" required>
                                <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <div class="rounded-full bg-teal-100 p-2 text-teal-600 peer-checked:bg-primary-200 peer-checked:text-primary-700">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">E-Wallet</p>
                                            <p class="text-[10px] text-gray-500 mt-1">DANA, GoPay, OVO</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="cod" class="peer sr-only" checked required>
                                <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <div class="rounded-full bg-orange-100 p-2 text-orange-600 peer-checked:bg-primary-200 peer-checked:text-primary-700">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">Bayar di Tempat</p>
                                            <p class="text-[10px] text-gray-500 mt-1">Cash On Delivery (COD)</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                        <div class="grid gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50">
                            @auth
                                @if ($userAddresses->isNotEmpty())
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Pilih Alamat
                                            Tersimpan</label>
                                        <select name="address_id"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            <option value="">-- Buat alamat baru --</option>
                                            @foreach ($userAddresses as $address)
                                                <option value="{{ $address->id }}" @selected((int) old('address_id', $defaultAddressId) === (int) $address->id)>
                                                    {{ $address->label ?: 'Alamat' }} -
                                                    {{ $address->recipient_name }} ({{ $address->city }})
                                                    {{ $address->is_default ? '[Default]' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            @endauth

                            <div class="grid gap-3 pt-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Label
                                            (Rumah/Kantor)</label>
                                        <input type="text" name="address_label" value="{{ old('address_label') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Nama
                                            Penerima</label>
                                        <input type="text" name="recipient_name" value="{{ old('recipient_name') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">No. HP
                                        Penerima</label>
                                    <input type="text" name="address_phone" value="{{ old('address_phone') }}"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Alamat
                                        Lengkap</label>
                                    <textarea name="address_line" rows="2"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                        placeholder="Nama jalan, gedung, RT/RW">{{ old('address_line') }}</textarea>
                                </div>

                                <div class="grid gap-3 grid-cols-3">
                                    <div class="col-span-1">
                                        <label
                                            class="mb-1 block text-[10px] sm:text-xs font-semibold text-gray-600">Kota</label>
                                        <input type="text" name="city" value="{{ old('city') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    </div>
                                    <div class="col-span-1">
                                        <label
                                            class="mb-1 block text-[10px] sm:text-xs font-semibold text-gray-600">Provinsi</label>
                                        <input type="text" name="province" value="{{ old('province') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    </div>
                                    <div class="col-span-1">
                                        <label class="mb-1 block text-[10px] sm:text-xs font-semibold text-gray-600">Kode
                                            Pos</label>
                                        <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Catatan/Patokan</label>
                                    <input type="text" name="address_notes" value="{{ old('address_notes') }}"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                        placeholder="Misal: Pagar hitam depan masjid">
                                </div>
                            </div>

                            @auth
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="set_as_default" value="1" @checked(old('set_as_default'))
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4">
                                    Jadikan sebagai alamat default
                                </label>
                            @endauth
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3.5 text-base font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30">
                        Checkout & Bayar
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>
            </aside>
        </section>
    @endif

    @if ($recentOrders->isNotEmpty())
        <section class="mt-12 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Pesanan Terakhir Anda</h2>
                    <p class="text-sm text-gray-500 mt-1">Cek status pesanan atau ajukan klaim garansi untuk
                        produk yang sudah dibeli.</p>
                </div>

                <a href="{{ route('home.warranty-claims.index') }}"
                    class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
                    Lihat Riwayat Klaim
                </a>
            </div>

            <div class="grid gap-6">
                @foreach ($recentOrders as $order)
                    <article class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
                        <div
                            class="bg-gray-100 px-5 py-4 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200">
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-0.5">
                                        Order ID</p>
                                    <p class="text-sm font-bold text-gray-900">{{ $order->order_code }}</p>
                                </div>
                                <div class="hidden sm:block w-px h-8 bg-gray-300"></div>
                                <div class="hidden sm:block">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-0.5">
                                        Tanggal</p>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ optional($order->placed_at)->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-lg px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider {{ $order->status === 'completed' ? 'bg-green-100 text-green-700' : ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                                    Status: {{ $order->status }}
                                </span>
                                <span
                                    class="rounded-lg px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($order->payment_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    Trx: {{ $order->payment_status }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="mb-4 flex flex-wrap gap-x-8 gap-y-2">
                                <p class="text-sm text-gray-600">Total Belanja: <span class="font-bold text-gray-900">Rp
                                        {{ number_format((int) $order->total_amount, 0, ',', '.') }}</span>
                                    ({{ number_format((int) $order->items->sum('quantity')) }} item)
                                </p>
                                @php $latestPayment = $order->payments->first(); @endphp
                                <p class="text-sm text-gray-600">Metode: <span
                                        class="font-semibold text-gray-800">{{ strtoupper($latestPayment?->method ?? 'dummy') }}</span>
                                    (Ref: {{ $latestPayment?->payment_code ?? '-' }})</p>
                            </div>

                            <div class="space-y-3">
                                @foreach ($order->items as $orderItem)
                                    @php
                                        $latestClaim = $orderItem->warrantyClaims->first();
                                        $isWarrantyEligible = (int) $orderItem->warranty_days > 0;
                                        $warrantyActive =
                                            $isWarrantyEligible &&
                                            $orderItem->warranty_expires_at &&
                                            $orderItem->warranty_expires_at->isFuture();
                                        $isOrderOwner = auth()->check() && (int) $order->user_id === (int) auth()->id();
                                        $hasOpenClaim =
                                            $latestClaim &&
                                            in_array(
                                                $latestClaim->status,
                                                ['submitted', 'reviewing', 'approved'],
                                                true,
                                            );
                                    @endphp

                                    <div
                                        class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="flex-1">
                                            <p class="text-sm font-bold text-gray-900">
                                                {{ $orderItem->product_name }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ number_format($orderItem->quantity) }} x Rp
                                                {{ number_format($orderItem->price, 0, ',', '.') }}
                                            </p>
                                            <p
                                                class="mt-1.5 inline-flex items-center gap-1.5 rounded bg-blue-50 px-2 py-1 text-[11px] font-medium text-blue-700">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                @if ($isWarrantyEligible)
                                                    Garansi s/d
                                                    {{ optional($orderItem->warranty_expires_at)->format('d M Y H:i') ?? '-' }}
                                                @else
                                                    Produk non-elektronik (tanpa klaim garansi)
                                                @endif
                                            </p>
                                            @if ($isWarrantyEligible)
                                                <p class="mt-1 text-[11px] text-gray-500">
                                                    Klaim bisa diajukan berulang selama masa garansi masih
                                                    aktif.
                                                </p>
                                                <p class="mt-1 text-[11px] text-gray-600">
                                                    Sisa masa garansi:
                                                    <span data-warranty-countdown
                                                        data-expires-at="{{ optional($orderItem->warranty_expires_at)->toIso8601String() }}"
                                                        class="font-semibold text-blue-700">
                                                        Menghitung...
                                                    </span>
                                                </p>
                                            @endif
                                        </div>

                                        <div class="w-full sm:w-auto text-right">
                                            @if ($latestClaim)
                                                <div
                                                    class="mb-2 inline-flex items-center rounded-lg bg-orange-50 border border-orange-100 px-3 py-1.5 text-xs font-semibold text-orange-700">
                                                    Klaim: {{ $latestClaim->status }}
                                                </div>
                                            @endif

                                            @auth
                                                @if ($isOrderOwner && $isWarrantyEligible && $warrantyActive && !$hasOpenClaim)
                                                    <form method="POST"
                                                        action="{{ route('home.warranty-claims.store', [$order, $orderItem]) }}"
                                                        class="mt-2 text-left space-y-2" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="flex gap-2">
                                                            <input type="text" name="reason"
                                                                class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                placeholder="Alasan klaim rusak/cacat" required>
                                                        </div>

                                                        <input type="file" name="damage_proof" accept="image/*,video/*"
                                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                            required>

                                                        <button type="submit"
                                                            class="w-full rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-gray-800">
                                                            Ajukan Klaim + Upload Bukti
                                                        </button>
                                                    </form>
                                                @elseif ($isOrderOwner && $hasOpenClaim)
                                                    <p class="mt-1 text-xs text-orange-600 font-medium">Sedang
                                                        diproses tim Arip.</p>
                                                @elseif ($isOrderOwner && !$isWarrantyEligible)
                                                    <p class="mt-1 text-xs text-gray-500 font-medium">Garansi hanya
                                                        untuk produk elektronik.</p>
                                                @elseif ($isOrderOwner && !$warrantyActive)
                                                    <p class="mt-1 text-xs text-gray-400 font-medium line-through">
                                                        Garansi Habis</p>
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const countdownElements = document.querySelectorAll('[data-warranty-countdown]');
            if (!countdownElements.length) {
                return;
            }

            const renderCountdown = (element) => {
                const expiresAt = element.getAttribute('data-expires-at');
                if (!expiresAt) {
                    element.textContent = '-';
                    return;
                }

                const deadline = new Date(expiresAt).getTime();
                const now = Date.now();
                const diff = deadline - now;

                if (diff <= 0) {
                    element.textContent = 'Garansi berakhir';
                    element.classList.remove('text-blue-700');
                    element.classList.add('text-red-600');
                    return;
                }

                const totalSeconds = Math.floor(diff / 1000);
                const days = Math.floor(totalSeconds / 86400);
                const hours = Math.floor((totalSeconds % 86400) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                element.textContent = `${days}h ${hours}j ${minutes}m ${seconds}d`;
            };

            const tick = () => {
                countdownElements.forEach(renderCountdown);
            };

            tick();
            window.setInterval(tick, 1000);
        })();
    </script>
@endpush
