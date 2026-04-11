<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk - Toko HS ELECTRIC</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen text-slate-900 antialiased"
    style="font-family: 'Manrope', sans-serif; background-image: linear-gradient(120deg, rgba(6, 78, 59, 0.78) 0%, rgba(6, 95, 70, 0.68) 38%, rgba(15, 23, 42, 0.62) 100%), url('{{ asset('img/image_loginpage.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="pointer-events-none fixed inset-0 bg-slate-950/30"></div>
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-16 -top-20 h-72 w-72 rounded-full bg-emerald-200/20 blur-3xl"></div>
        <div class="absolute -right-16 bottom-0 h-80 w-80 rounded-full bg-cyan-200/15 blur-3xl"></div>
    </div>

    <main data-auth-shell
        class="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-10">
        <div data-auth-grid class="grid w-full items-stretch gap-6 lg:grid-cols-[1.12fr,0.88fr] lg:gap-10">
            <section
                class="hidden rounded-3xl border border-white/25 bg-gradient-to-b from-emerald-900/72 to-emerald-950/58 p-10 text-white shadow-[0_30px_80px_rgba(2,6,23,0.45)] backdrop-blur-md lg:flex lg:flex-col lg:justify-between">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                        <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                            class="h-11 w-11 rounded-xl border border-white/30 bg-white/20 p-1.5 object-contain">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Toko HS
                                ELECTRIC</p>
                            <p class="text-sm font-medium text-white/85">Pusat alat listrik terpercaya</p>
                        </div>
                    </a>

                    <div class="mt-10 max-w-xl">
                        <span
                            class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-50">
                            Login Pelanggan & Admin
                        </span>
                        <h1 class="mt-4 text-[2.15rem] font-extrabold leading-tight tracking-tight text-white">
                            Akses kebutuhan listrik dengan pengalaman akun yang terasa lebih rapi dan nyaman.
                        </h1>
                        <p class="mt-4 text-base leading-relaxed text-emerald-50">
                            Masuk untuk memantau transaksi, mengelola pesanan, dan memproses garansi dalam satu
                            dashboard yang profesional dan mudah digunakan.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Checkout Kilat</p>
                        <p class="mt-2 text-sm text-white">Alamat tersimpan otomatis, proses pembayaran lebih cepat.</p>
                    </div>
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Tracking Real-time
                        </p>
                        <p class="mt-2 text-sm text-white">Status order selalu ter-update tanpa harus tanya manual.</p>
                    </div>
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Garansi Digital</p>
                        <p class="mt-2 text-sm text-white">Klaim garansi tercatat dan mudah dipantau kapan saja.</p>
                    </div>
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Akun Aman</p>
                        <p class="mt-2 text-sm text-white">Perlindungan sesi login dengan validasi sistem berlapis.</p>
                    </div>
                </div>
            </section>

            <section class="w-full max-w-md place-self-center">
                <div class="mb-5 text-center lg:hidden">
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-white/25 px-4 py-2 text-white backdrop-blur-sm">
                        <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                            class="h-7 w-7 object-contain">
                        <span class="text-sm font-semibold">Toko HS ELECTRIC</span>
                    </a>
                </div>

                <div data-auth-card
                    class="rounded-3xl border border-white/90 bg-white/96 p-6 shadow-[0_24px_60px_rgba(15,23,42,0.35)] backdrop-blur-md sm:p-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Masuk</p>
                        <h2 data-auth-title class="mt-2 text-[1.9rem] font-extrabold leading-tight text-slate-900">
                            Selamat datang kembali</h2>
                        <p data-auth-subtitle class="mt-2 text-sm leading-relaxed text-slate-500">Silakan login untuk
                            melanjutkan aktivitas belanja dan layanan purna jual Anda.</p>
                    </div>

                    <x-auth-session-status
                        class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                        :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" data-ui-form
                        data-auth-form>
                        @csrf

                        <div>
                            <label for="email"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                autofocus autocomplete="username"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                placeholder="contoh@email.com">
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                        </div>

                        <div>
                            <label for="password"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required
                                    autocomplete="current-password"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm font-medium text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                    placeholder="Masukkan password">
                                <button type="button" data-password-toggle data-target="password"
                                    aria-label="Tampilkan password"
                                    class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-emerald-700 focus:outline-none">
                                    <svg data-icon-show class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg data-icon-hide class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6M6.7 6.7C4.1 8.2 2.5 11 2.5 12c0 0 3.2 6 9.5 6 2.1 0 3.9-.7 5.3-1.7M9.2 5.1A10.2 10.2 0 0 1 12 4.5c6.3 0 9.5 7.5 9.5 7.5a15.6 15.6 0 0 1-2.2 3.1" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-red-600" />
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm">
                            <label for="remember_me"
                                class="inline-flex cursor-pointer items-center gap-2 text-slate-600 select-none">
                                <input id="remember_me" type="checkbox" name="remember"
                                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-2 focus:ring-emerald-500/30">
                                Ingat saya
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                    class="font-semibold text-emerald-700 transition hover:text-emerald-800">
                                    Lupa password?
                                </a>
                            @endif
                        </div>

                        <button type="submit" data-loading-text="Memverifikasi..."
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/35 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                            Masuk Sekarang
                        </button>
                    </form>

                    <div class="mt-6 border-t border-slate-200 pt-5 text-center text-sm text-slate-600">
                        @if (Route::has('register'))
                            Belum punya akun?
                            <a href="{{ route('register') }}"
                                class="font-semibold text-emerald-700 transition hover:text-emerald-800">
                                Daftar di sini
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </main>

    @include('auth.partials.form-micro-interactions')
</body>

</html>
