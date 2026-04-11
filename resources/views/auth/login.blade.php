<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk - Toko HS ELECTRIC</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
  UX Rationale (Light & Crisp Theme):
  - White/off-white backgrounds build TRUST for financial transactions (bank-grade feel)
  - High-contrast borders + focus rings ensure WCAG AA compliance
  - Subtle shadows lift the form card, creating visual hierarchy without darkness
  - Green primary color reinforces the Toko HS ELECTRIC brand identity throughout auth flow
--}}

<body class="min-h-screen bg-slate-50 font-sans text-gray-900 antialiased">
    {{-- Subtle decorative blobs — light, non-distracting --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-primary-100/60 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-primary-50/80 blur-3xl"></div>
    </div>

    <main
        class="relative z-10 mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center gap-8 px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[1.2fr,1fr] lg:items-center lg:gap-16 lg:px-8 lg:py-16">
        {{-- Left — Brand Panel --}}
        <section class="hidden lg:block">
            <span
                class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-700">
                Toko HS ELECTRIC
            </span>
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-gray-900">
                Masuk ke Akun Anda
            </h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-gray-600">
                Kelola pesanan, pantau pengiriman, dan ajukan klaim garansi — semua dari satu dashboard yang aman dan
                terpercaya.
            </p>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div
                        class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Customer</p>
                    <p class="mt-1 text-sm text-gray-700">Checkout cepat dengan alamat tersimpan.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div
                        class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Admin</p>
                    <p class="mt-1 text-sm text-gray-700">Pipeline pesanan & klaim garansi real-time.</p>
                </div>
            </div>

            <a href="{{ route('home') }}"
                class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 transition hover:text-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Lihat Katalog
            </a>
        </section>

        {{-- Right — Login Form Card --}}
        <section class="w-full max-w-md mx-auto lg:mx-0">
            {{-- Mobile brand --}}
            <div class="mb-6 text-center lg:hidden">
                <a href="{{ route('home') }}" class="inline-flex items-center">
                    <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                        class="h-10 w-10 object-contain">
                </a>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg shadow-gray-200/50 sm:p-8">
                <h2 class="text-2xl font-bold text-gray-900">Masuk ke Akun</h2>
                <p class="mt-1 text-sm text-gray-500">Gunakan email dan password terdaftar.</p>

                <x-auth-session-status
                    class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700"
                    :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" data-ui-form>
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Email
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autofocus autocomplete="username"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 transition duration-200 hover:border-primary-300 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15"
                            placeholder="contoh@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-gray-700">
                            Password
                        </label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 transition duration-200 hover:border-primary-300 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15"
                            placeholder="Masukkan password">
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <label for="remember_me"
                        class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                        <input id="remember_me" type="checkbox" name="remember"
                            class="h-4 w-4 rounded border-gray-300 text-primary-600 transition duration-200 focus:ring-2 focus:ring-primary-500/30">
                        Ingat sesi login saya
                    </label>

                    <button type="submit" data-loading-text="Memverifikasi..."
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition duration-200 hover:-translate-y-0.5 hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30 active:translate-y-0 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                        Masuk Sekarang
                    </button>
                </form>

                <div
                    class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-5 text-sm">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                            class="font-medium text-primary-600 hover:text-primary-700 transition">
                            Lupa password?
                        </a>
                    @endif

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="font-medium text-gray-600 hover:text-gray-900 transition">
                            Belum punya akun? <span class="font-semibold text-primary-600">Daftar</span>
                        </a>
                    @endif
                </div>
            </div>
        </section>
    </main>
    @include('auth.partials.form-micro-interactions')
</body>

</html>
