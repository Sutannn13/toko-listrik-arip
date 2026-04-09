<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Keranjang Belanja - Toko Listrik Arip</title>

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
                        <p class="text-xs font-medium text-gray-500">Keranjang User</p>
                    </div>
                </a>

                <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
                    @auth
                    <a href="{{ route('home.tracking') }}" class="hidden rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary-500 hover:text-primary-600 sm:block">
                        Cek Pesanan
                    </a>
                    @endauth
                    
                    <a href="{{ route('home.cart') }}" class="relative rounded-lg p-2 transition {{ $cartQuantity > 0 ? 'bg-primary-50 text-primary-600' : 'text-gray-500 hover:bg-gray-100 hover:text-primary-600' }}">
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
                            <a href="{{ route('register') }}" class="hidden sm:inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                                Daftar
                            </a>
                        @endif
                    @endguest

                    @auth
                        @php
                            $userPrimaryRole = Auth::user()->getRoleNames()->first();
                        @endphp

                        @if (Auth::user()->hasAnyRole(['super-admin', 'admin']))
                            <a href="{{ route('admin.dashboard') }}" class="hidden rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 sm:inline-flex">
                                Admin Panel
                            </a>
                        @endif

                        <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:flex">
                            <div class="h-5 w-5 overflow-hidden rounded-full bg-primary-100 text-center leading-5 text-primary-700">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            {{ Auth::user()->name }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1 w-full mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 shadow-sm">
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

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Keranjang Belanja</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Periksa kembali pesanan Anda sebelum checkout.
                    </p>
                </div>

                <a href="{{ route('home') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Katalog
                </a>
            </div>

            @if ($cartItems->isEmpty())
                <section class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Keranjang masih kosong</h2>
                    <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">Belum ada produk di keranjang Anda. Yuk temukan berbagai alat listrik menarik di katalog kami.</p>
                    <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                        Mulai Belanja
                    </a>
                </section>
            @else
                <section class="grid gap-8 lg:grid-cols-[1.5fr,1fr]">
                    <div class="space-y-4">
                        @foreach ($cartItems as $item)
                            <article class="flex flex-col sm:flex-row gap-4 sm:items-center rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-900">{{ $item['name'] }}</h3>
                                    <p class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $item['unit'] }}</p>
                                    @if ($item['is_available'] && $item['slug'] !== '')
                                        <a href="{{ route('home.products.show', $item['slug']) }}" class="mt-2 text-sm font-semibold text-primary-600 hover:underline">
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
                                        <form method="POST" action="{{ route('home.cart.update', $item['product_id']) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="qty" min="1" max="{{ max(1, (int) $item['stock']) }}" value="{{ $item['qty'] }}" class="w-16 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-center text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            <button type="submit" class="rounded-lg bg-gray-100 p-2 text-gray-600 hover:bg-gray-200 transition" title="Update Kuantitas">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('home.cart.remove', $item['product_id']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100 transition" title="Hapus dari Keranjang">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="text-right mt-1">
                                         @if(!$item['is_available'])
                                             <span class="text-xs font-semibold text-red-600">Tidak tersedia</span>
                                         @else
                                             <span class="text-xs font-medium text-green-600">Stok: {{ number_format($item['stock']) }}</span>
                                         @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="sticky top-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm self-start">
                        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-4">Ringkasan Pesanan</h2>
                        
                        <div class="mt-4 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Total Item</span>
                                <span class="font-bold text-gray-900">{{ number_format($totalQuantity) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-base">
                                <span class="text-gray-600 font-medium">Subtotal</span>
                                <span class="text-xl font-black text-primary-600">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 flex gap-3 text-sm text-blue-800">
                                <svg class="h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p>Setiap produk memiliki garansi toko selama <strong>7 hari</strong> setelah diterima.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('home.cart.checkout') }}" class="mt-6 space-y-5">
                            @csrf

                            <div>
                                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">1</span>
                                    Data Pelanggan
                                </h3>
                                <div class="grid gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Nama Lengkap</label>
                                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Nama Anda" required>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Email</label>
                                        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Email aktif" required>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Nomor HP</label>
                                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Contoh: 0812xxxxxx" required>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="grid h-6 w-6 place-items-center rounded-full bg-primary-100 text-xs font-bold text-primary-600">2</span>
                                    Alamat Pengiriman
                                </h3>

                                <div class="grid gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50">
                                    @auth
                                        @if ($userAddresses->isNotEmpty())
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-gray-600">Pilih Alamat Tersimpan</label>
                                                <select name="address_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                                    <option value="">-- Buat alamat baru --</option>
                                                    @foreach ($userAddresses as $address)
                                                        <option value="{{ $address->id }}" @selected((int) old('address_id', $defaultAddressId) === (int) $address->id)>
                                                            {{ $address->label ?: 'Alamat' }} - {{ $address->recipient_name }} ({{ $address->city }}) {{ $address->is_default ? '[Default]' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    @endauth

                                    <div class="grid gap-3 pt-2">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-gray-600">Label (Rumah/Kantor)</label>
                                                <input type="text" name="address_label" value="{{ old('address_label') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-gray-600">Nama Penerima</label>
                                                <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-gray-600">No. HP Penerima</label>
                                            <input type="text" name="address_phone" value="{{ old('address_phone') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-gray-600">Alamat Lengkap</label>
                                            <textarea name="address_line" rows="2" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Nama jalan, gedung, RT/RW">{{ old('address_line') }}</textarea>
                                        </div>

                                        <div class="grid gap-3 grid-cols-3">
                                            <div class="col-span-1">
                                                <label class="mb-1 block text-[10px] sm:text-xs font-semibold text-gray-600">Kota</label>
                                                <input type="text" name="city" value="{{ old('city') }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            </div>
                                            <div class="col-span-1">
                                                <label class="mb-1 block text-[10px] sm:text-xs font-semibold text-gray-600">Provinsi</label>
                                                <input type="text" name="province" value="{{ old('province') }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            </div>
                                            <div class="col-span-1">
                                                <label class="mb-1 block text-[10px] sm:text-xs font-semibold text-gray-600">Kode Pos</label>
                                                <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-gray-600">Catatan/Patokan</label>
                                            <input type="text" name="address_notes" value="{{ old('address_notes') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Misal: Pagar hitam depan masjid">
                                        </div>
                                    </div>

                                    @auth
                                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" name="set_as_default" value="1" @checked(old('set_as_default')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4">
                                            Jadikan sebagai alamat default
                                        </label>
                                    @endauth
                                </div>
                            </div>

                            <button type="submit" class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3.5 text-base font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30">
                                Checkout & Bayar
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </form>
                    </aside>
                </section>
            @endif

            @if ($recentOrders->isNotEmpty())
                <section class="mt-12 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Pesanan Terakhir Anda</h2>
                        <p class="text-sm text-gray-500 mt-1">Cek status pesanan atau ajukan klaim garansi untuk produk yang sudah dibeli.</p>
                    </div>

                    <div class="grid gap-6">
                        @foreach ($recentOrders as $order)
                            <article class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
                                <div class="bg-gray-100 px-5 py-4 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200">
                                    <div class="flex items-center gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-0.5">Order ID</p>
                                            <p class="text-sm font-bold text-gray-900">{{ $order->order_code }}</p>
                                        </div>
                                        <div class="hidden sm:block w-px h-8 bg-gray-300"></div>
                                        <div class="hidden sm:block">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-0.5">Tanggal</p>
                                            <p class="text-sm font-medium text-gray-900">{{ optional($order->placed_at)->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="rounded-lg px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider {{ $order->status === 'completed' ? 'bg-green-100 text-green-700' : ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                                            Status: {{ $order->status }}
                                        </span>
                                        <span class="rounded-lg px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($order->payment_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                            Trx: {{ $order->payment_status }}
                                        </span>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="mb-4 flex flex-wrap gap-x-8 gap-y-2">
                                        <p class="text-sm text-gray-600">Total Belanja: <span class="font-bold text-gray-900">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</span> ({{ number_format((int) $order->items->sum('quantity')) }} item)</p>
                                        @php $latestPayment = $order->payments->first(); @endphp
                                        <p class="text-sm text-gray-600">Metode: <span class="font-semibold text-gray-800">{{ strtoupper($latestPayment?->method ?? 'dummy') }}</span> (Ref: {{ $latestPayment?->payment_code ?? '-' }})</p>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach ($order->items as $orderItem)
                                            @php
                                                $latestClaim = $orderItem->warrantyClaims->first();
                                                $warrantyActive = $orderItem->warranty_expires_at && $orderItem->warranty_expires_at->isFuture();
                                                $isOrderOwner = auth()->check() && (int) $order->user_id === (int) auth()->id();
                                                $hasOpenClaim = $latestClaim && in_array($latestClaim->status, ['submitted', 'reviewing', 'approved'], true);
                                            @endphp

                                            <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                                <div class="flex-1">
                                                    <p class="text-sm font-bold text-gray-900">{{ $orderItem->product_name }}</p>
                                                    <p class="mt-0.5 text-xs text-gray-500">
                                                        {{ number_format($orderItem->quantity) }} x Rp {{ number_format($orderItem->price, 0, ',', '.') }}
                                                    </p>
                                                    <p class="mt-1.5 inline-flex items-center gap-1.5 rounded bg-blue-50 px-2 py-1 text-[11px] font-medium text-blue-700">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        Garansi s/d {{ optional($orderItem->warranty_expires_at)->format('d M Y H:i') ?? '-' }}
                                                    </p>
                                                </div>

                                                <div class="w-full sm:w-auto text-right">
                                                    @if ($latestClaim)
                                                        <div class="mb-2 inline-flex items-center rounded-lg bg-orange-50 border border-orange-100 px-3 py-1.5 text-xs font-semibold text-orange-700">
                                                            Klaim: {{ $latestClaim->status }}
                                                        </div>
                                                    @endif

                                                    @auth
                                                        @if ($isOrderOwner && $warrantyActive && !$hasOpenClaim)
                                                            <form method="POST" action="{{ route('home.warranty-claims.store', [$order, $orderItem]) }}" class="mt-2 text-left">
                                                                @csrf
                                                                <div class="flex gap-2">
                                                                    <input type="text" name="reason" class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Alasan klaim rusak/cacat" required>
                                                                    <button type="submit" class="shrink-0 rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-gray-800">
                                                                        Ajukan Klaim
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        @elseif ($isOrderOwner && $hasOpenClaim)
                                                            <p class="mt-1 text-xs text-orange-600 font-medium">Sedang diproses tim Arip.</p>
                                                        @elseif ($isOrderOwner && !$warrantyActive)
                                                            <p class="mt-1 text-xs text-gray-400 font-medium line-through">Garansi Habis</p>
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
        </main>

        <footer class="mt-auto bg-gray-900 py-8 text-center text-gray-400">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
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
