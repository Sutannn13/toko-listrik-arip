<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Lacak Pesanan - Toko Listrik Arip</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased selection:bg-primary-500 selection:text-white flex flex-col">
    <!-- Overlay Design Elements -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-32 left-0 h-96 w-96 rounded-full bg-primary-100/40 blur-3xl"></div>
        <div class="absolute top-1/4 -right-16 h-80 w-80 rounded-full bg-primary-200/30 blur-3xl"></div>
    </div>

    <div class="relative z-10 flex-1 flex flex-col">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 transition-transform hover:scale-105">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-sm font-extrabold text-white shadow-md shadow-primary-500/30">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-widest text-primary-600 uppercase">Toko Listrik Arip</p>
                        <p class="text-[10px] font-medium text-gray-400">Lacak Pesanan</p>
                    </div>
                </a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="hidden sm:inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:border-primary-500 hover:text-primary-600 hover:bg-gray-50">
                        Katalog Produk
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-xl px-4 py-16 sm:px-6 lg:px-8 flex-1 flex flex-col justify-center">
            
            <div class="text-center mb-10">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-50 text-primary-600 shadow-sm border border-primary-100 mb-6">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Lacak Pesanan Anda</h1>
                <p class="mt-3 text-base text-gray-500 max-w-sm mx-auto">Masukkan kode pesanan dan alamat email yang Anda gunakan saat checkout untuk melihat status pesanan terkini.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xl shadow-gray-200/50 sm:p-8">
                @if (session('error'))
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex gap-3 text-sm text-red-700 items-start">
                        <svg class="h-5 w-5 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('home.tracking.check') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label for="order_code" class="mb-1.5 block text-sm font-semibold text-gray-700">Kode Pesanan (Order ID)</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                            </div>
                            <input type="text" name="order_code" id="order_code" value="{{ old('order_code') }}" class="block w-full rounded-xl border-gray-300 pl-10 focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 transition shadow-sm" placeholder="Contoh: ORD-17ABC98XYZ" required>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">Email Utama (Saat Checkout)</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="block w-full rounded-xl border-gray-300 pl-10 focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 transition shadow-sm" placeholder="Contoh: pelanggan@email.com" required>
                        </div>
                    </div>

                    <button type="submit" class="group relative flex w-full justify-center rounded-xl border border-transparent bg-primary-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-primary-300 group-hover:text-primary-200 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        Lacak Pesanan
                    </button>
                    
                    <div class="text-center pt-2">
                       <a href="{{ route('home') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 transition">Atau kembali mulai berbelanja</a>
                    </div>
                </form>
            </div>
            
        </main>
        
        <footer class="mt-auto bg-gray-900 py-6 text-center text-gray-400">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-sm">&copy; {{ date('Y') }} Toko Listrik Arip. Hak Cipta Dilindungi.</p>
            </div>
        </footer>
    </div>
</body>
</html>
