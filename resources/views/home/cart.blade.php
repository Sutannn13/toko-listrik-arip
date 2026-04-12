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

    {{-- Header --}}
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
            <div class="space-y-4 lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pr-2">
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

            {{-- Kanan: Form Checkout --}}
            <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:self-start">
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
                        <span class="font-semibold text-gray-900">Rp {{ number_format($shippingCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex items-center justify-between text-base">
                        <span class="text-gray-900 font-bold">Total Bayar</span>
                        <span class="text-xl font-black text-primary-700">Rp
                            {{ number_format($totalAmount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('home.cart.checkout') }}" class="mt-6 space-y-5">
                    @csrf

                    @php
                        $selectedAddressId = old('address_id', $defaultAddressId);
                        $selectedAddressId = filled($selectedAddressId) ? (int) $selectedAddressId : null;
                        $selectedAddressForPreview =
                            $userAddresses->firstWhere('id', $selectedAddressId) ?? $selectedAddress;
                    @endphp

                    {{-- STEP 1: Data Pelanggan --}}
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">1</span>
                            Data Pelanggan
                        </h3>
                        <div class="grid gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Nama Lengkap</label>
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
                                <input type="text" name="customer_phone"
                                    value="{{ old('customer_phone', $selectedAddressForPreview?->phone) }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                    placeholder="Contoh: 0812xxxxxx" required>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 2: Alamat Pengiriman --}}
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">2</span>
                            Alamat Pengiriman
                        </h3>

                        <div class="grid gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50">
                            {{-- Pilih alamat tersimpan (jika ada) --}}
                            @auth
                                @if ($userAddresses->isNotEmpty())
                                    <div class="space-y-3">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Pilih Alamat
                                            Tersimpan</label>
                                        <select name="address_id" id="address_id_select"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            <option value="">+ Pakai alamat baru untuk checkout ini</option>
                                            @foreach ($userAddresses as $address)
                                                <option value="{{ $address->id }}"
                                                    data-label="{{ $address->label ?: 'Alamat' }}"
                                                    data-recipient="{{ $address->recipient_name }}"
                                                    data-phone="{{ $address->phone }}"
                                                    data-location="{{ $address->city }}, {{ $address->province }} {{ $address->postal_code }}"
                                                    data-address-line="{{ $address->address_line }}"
                                                    data-notes="{{ $address->notes }}" @selected((int) old('address_id', $defaultAddressId) === (int) $address->id)>
                                                    {{ $address->label ?: 'Alamat' }} —
                                                    {{ $address->recipient_name }} ({{ $address->city }})
                                                    {{ $address->is_default ? '[Default]' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-gray-400">Jika alamat tersimpan dipilih, form alamat baru
                                            otomatis tidak wajib diisi.</p>

                                        <div id="selected_address_preview"
                                            class="{{ $selectedAddressForPreview ? '' : 'hidden' }} rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-3 text-sm text-cyan-900">
                                            <p class="text-xs font-bold uppercase tracking-wider text-cyan-700">Alamat Dipakai
                                                Checkout</p>
                                            <p class="mt-1 font-semibold" data-address-recipient>
                                                {{ $selectedAddressForPreview?->recipient_name ?? '-' }}
                                                @if ($selectedAddressForPreview?->phone)
                                                    ({{ $selectedAddressForPreview->phone }})
                                                @endif
                                            </p>
                                            <p class="mt-1 text-xs" data-address-line>
                                                {{ $selectedAddressForPreview?->address_line ?? '-' }}
                                            </p>
                                            <p class="text-xs" data-address-location>
                                                @if ($selectedAddressForPreview)
                                                    {{ $selectedAddressForPreview->city }},
                                                    {{ $selectedAddressForPreview->province }}
                                                    {{ $selectedAddressForPreview->postal_code }}
                                                @else
                                                    -
                                                @endif
                                            </p>
                                        </div>

                                        <a href="{{ route('profile.addresses.index') }}"
                                            class="inline-flex items-center gap-2 text-xs font-semibold text-primary-600 hover:text-primary-700 hover:underline">
                                            Kelola alamat tersimpan
                                        </a>
                                    </div>
                                @endif
                            @endauth

                            {{-- Form alamat baru --}}
                            <div id="new_address_fields" class="space-y-3 {{ $selectedAddressId ? 'opacity-60' : '' }}">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Isi Hanya Jika
                                    Ingin Pakai Alamat Baru</p>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Label
                                            (Rumah/Kantor)</label>
                                        <input type="text" name="address_label" value="{{ old('address_label') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                            placeholder="Contoh: Rumah">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Nama Penerima</label>
                                        <input type="text" name="recipient_name" value="{{ old('recipient_name') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                            placeholder="Penerima paket">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold text-gray-700">No. HP Penerima</label>
                                    <input type="text" name="address_phone" value="{{ old('address_phone') }}"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                        placeholder="Contoh: 0812xxxxxx">
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold text-gray-700">Alamat Lengkap</label>
                                    <textarea name="address_line" rows="3"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                        placeholder="Nama jalan, gedung, RT/RW">{{ old('address_line') }}</textarea>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Kota / Kab.</label>
                                        <input type="text" name="city" value="{{ old('city') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                            placeholder="Kota">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Provinsi</label>
                                        <input type="text" name="province" value="{{ old('province') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                            placeholder="Provinsi">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Kode Pos</label>
                                        <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                            placeholder="12345">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold text-gray-700">Catatan/Patokan <span
                                            class="font-normal text-gray-400">(Opsional)</span></label>
                                    <input type="text" name="address_notes" value="{{ old('address_notes') }}"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                        placeholder="Misal: Pagar hitam depan masjid">
                                </div>

                                @auth
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="set_as_default" value="1"
                                            @checked(old('set_as_default'))
                                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        Jadikan sebagai alamat default
                                    </label>
                                @endauth
                            </div>
                        </div>
                    </div>

                    {{-- STEP 3: Metode Pembayaran --}}
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">3</span>
                            Metode Pembayaran
                        </h3>

                        <div class="grid sm:grid-cols-3 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="bank_transfer" class="peer sr-only"
                                    required>
                                <div
                                    class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <div class="rounded-full bg-blue-100 p-2 text-blue-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">Transfer Bank</p>
                                            <p class="text-[10px] text-gray-500 mt-1">
                                                {{ implode(', ', array_filter([\App\Models\Setting::get('bank_1_name'), \App\Models\Setting::get('bank_2_name')])) ?: 'BCA, BRI' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="ewallet" class="peer sr-only"
                                    required>
                                <div
                                    class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <div class="rounded-full bg-teal-100 p-2 text-teal-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">E-Wallet</p>
                                            <p class="text-[10px] text-gray-500 mt-1">
                                                {{ \App\Models\Setting::get('bank_3_name', 'DANA') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="cod" class="peer sr-only" checked
                                    required>
                                <div
                                    class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <div class="rounded-full bg-orange-100 p-2 text-orange-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                                </path>
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

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addressSelect = document.getElementById('address_id_select');
            const newAddressFields = document.getElementById('new_address_fields');
            const selectedAddressPreview = document.getElementById('selected_address_preview');

            if (!addressSelect || !newAddressFields) {
                return;
            }

            const newAddressInputs = Array.from(newAddressFields.querySelectorAll('input, textarea, select'));
            const recipientEl = selectedAddressPreview?.querySelector('[data-address-recipient]');
            const lineEl = selectedAddressPreview?.querySelector('[data-address-line]');
            const locationEl = selectedAddressPreview?.querySelector('[data-address-location]');

            const syncAddressMode = () => {
                const hasSavedAddress = addressSelect.value !== '';
                const selectedOption = addressSelect.options[addressSelect.selectedIndex];

                newAddressFields.classList.toggle('opacity-60', hasSavedAddress);
                newAddressInputs.forEach((input) => {
                    input.disabled = hasSavedAddress;
                });

                if (selectedAddressPreview) {
                    selectedAddressPreview.classList.toggle('hidden', !hasSavedAddress);
                }

                if (!hasSavedAddress || !selectedOption) {
                    return;
                }

                if (recipientEl) {
                    const recipient = selectedOption.dataset.recipient || '-';
                    const phone = selectedOption.dataset.phone ? ` (${selectedOption.dataset.phone})` : '';
                    recipientEl.textContent = `${recipient}${phone}`;
                }

                if (lineEl) {
                    lineEl.textContent = selectedOption.dataset.addressLine || '-';
                }

                if (locationEl) {
                    locationEl.textContent = selectedOption.dataset.location || '-';
                }
            };

            syncAddressMode();
            addressSelect.addEventListener('change', syncAddressMode);
        });
    </script>
@endpush
