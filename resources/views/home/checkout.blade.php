@extends('layouts.storefront')

@section('title', 'Checkout - Toko HS ELECTRIC')
@section('header_subtitle', 'Checkout')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-7xl px-4 pt-4 pb-28 sm:px-6 sm:py-8 lg:px-8')

@section('content')
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

    {{-- Progress Steps --}}
    <div class="mb-8 w-full overflow-x-auto pb-2 scrollbar-hide">
        <div class="flex items-center justify-center min-w-max md:min-w-0 gap-2 sm:gap-4 px-2">
            <a href="{{ route('home.cart') }}"
                class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm text-gray-500 hover:text-primary-600 transition">
                <span
                    class="grid h-6 w-6 sm:h-8 sm:w-8 place-items-center rounded-full bg-gray-200 text-[10px] sm:text-xs font-bold text-gray-600 shrink-0">1</span>
                <span class="font-medium">Keranjang</span>
            </a>
            <div class="h-px w-4 sm:w-16 bg-primary-400 shrink-0"></div>
            <div class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm">
                <span
                    class="grid h-6 w-6 sm:h-8 sm:w-8 place-items-center rounded-full bg-primary-600 text-[10px] sm:text-xs font-bold text-white shadow-md shadow-primary-500/30 shrink-0">2</span>
                <span class="font-bold text-primary-700">Checkout</span>
            </div>
            <div class="h-px w-4 sm:w-16 bg-gray-200 shrink-0"></div>
            <div class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm text-gray-400">
                <span
                    class="grid h-6 w-6 sm:h-8 sm:w-8 place-items-center rounded-full bg-gray-100 text-[10px] sm:text-xs font-bold text-gray-400 shrink-0">3</span>
                <span class="font-medium">Selesai</span>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Checkout</h1>
            <p class="mt-1 text-sm text-gray-600">
                Lengkapi data pengiriman dan pilih metode pembayaran.
            </p>
        </div>

        <a href="{{ route('home.cart') }}"
            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Kembali ke Keranjang
        </a>
    </div>

    <form method="POST" action="{{ route('home.cart.checkout') }}">
        @csrf
        <section class="grid gap-8 lg:grid-cols-[1.5fr,1fr]">

            {{-- Kiri: Form Data --}}
            <div class="space-y-6">

                {{-- Ringkasan Item --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                        <span
                            class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </span>
                        Ringkasan Pesanan ({{ $totalQuantity }} item)
                    </h2>
                    <div class="space-y-3">
                        @foreach ($cartItems as $item)
                            <div
                                class="flex items-center justify-between gap-3 py-2 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                <div class="min-w-0 flex flex-1 items-center gap-3">
                                    <div
                                        class="h-12 w-12 overflow-hidden rounded-lg border border-gray-100 bg-gray-50 shrink-0">
                                        <img src="{{ $item['image_url'] ?? asset('img/hero-bg.jpg') }}"
                                            alt="{{ $item['name'] }}" class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $item['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $item['qty'] }} × Rp
                                            {{ number_format($item['price'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-gray-900 shrink-0">Rp
                                    {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Data Pelanggan --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                        <span
                            class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">1</span>
                        Data Pelanggan
                    </h2>
                    <div class="grid gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Nama Lengkap</label>
                            <input type="text" name="customer_name"
                                value="{{ old('customer_name', auth()->user()->name ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                placeholder="Nama Anda" required>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-700">Email</label>
                                <input type="email" name="customer_email"
                                    value="{{ old('customer_email', auth()->user()->email ?? '') }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                    placeholder="Email aktif" required>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-700">Nomor HP</label>
                                <input type="text" name="customer_phone"
                                    value="{{ old('customer_phone', $selectedAddress?->phone) }}"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                                    placeholder="Contoh: 0812xxxxxx" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alamat Pengiriman --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                        <span
                            class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">2</span>
                        Alamat Pengiriman
                    </h2>

                    @php
                        $selectedAddressId = old('address_id', $defaultAddressId);
                        $selectedAddressId = filled($selectedAddressId) ? (int) $selectedAddressId : null;
                        $selectedAddressForPreview =
                            $userAddresses->firstWhere('id', $selectedAddressId) ?? $selectedAddress;
                    @endphp

                    <div class="space-y-4">
                        @auth
                            @if ($userAddresses->isNotEmpty())
                                <div class="space-y-3">
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Pilih Alamat Tersimpan</label>
                                    <select name="address_id" id="address_id_select"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all">
                                        <option value="">+ Pakai alamat baru</option>
                                        @foreach ($userAddresses as $address)
                                            <option value="{{ $address->id }}" data-label="{{ $address->label ?: 'Alamat' }}"
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
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Isi Hanya Jika Ingin
                                Pakai Alamat Baru</p>

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
                                    <input type="checkbox" name="set_as_default" value="1" @checked(old('set_as_default'))
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    Jadikan sebagai alamat default
                                </label>
                            @endauth
                        </div>
                    </div>
                </div>

                {{-- Metode Pembayaran --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                        <span
                            class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">3</span>
                        Metode Pembayaran
                    </h2>

                    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="payment_method" value="bank_transfer" class="peer sr-only"
                                required>
                            <div
                                class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <div class="rounded-full bg-blue-100 p-2.5 text-blue-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                            <input type="radio" name="payment_method" value="ewallet" class="peer sr-only" required>
                            <div
                                class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <div class="rounded-full bg-teal-100 p-2.5 text-teal-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                            <input type="radio" name="payment_method" value="bayargg" class="peer sr-only" required>
                            <div
                                class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 hover:bg-gray-50 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus:ring-2 peer-focus:ring-primary-500 transition-all">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <div class="rounded-full bg-indigo-100 p-2.5 text-indigo-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Bayar.gg</p>
                                        <p class="text-[10px] text-gray-500 mt-1">QRIS otomatis, tanpa upload bukti</p>
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
                                    <div class="rounded-full bg-orange-100 p-2.5 text-orange-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            </div>

            {{-- Kanan: Sticky Total & Tombol Bayar --}}
            <aside class="lg:sticky lg:top-24 lg:self-start space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Total Pembayaran</h2>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Subtotal ({{ $totalQuantity }} item)</span>
                            <span class="font-semibold text-gray-900">Rp
                                {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Ongkir ({{ $totalQuantity }} × Rp
                                {{ number_format($shippingCostPerItem, 0, ',', '.') }})</span>
                            <span class="font-semibold text-gray-900">Rp
                                {{ number_format($shippingCost, 0, ',', '.') }}</span>
                        </div>
                        <div class="border-t border-gray-100 pt-3 flex items-center justify-between">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-black text-primary-700">Rp
                                {{ number_format($totalAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-4 text-base font-bold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary-500/30 active:scale-[0.98]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Bayar Sekarang
                    </button>
                </div>

                {{-- Security Badge --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-green-100 p-1.5 text-green-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-700">Transaksi Aman</p>
                            <p class="text-[10px] text-gray-500">Data Anda dilindungi dan diproses secara aman.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </form>
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
