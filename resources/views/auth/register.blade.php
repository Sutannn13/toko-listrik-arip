<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Daftar Akun - Toko HS ELECTRIC</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen antialiased">
    {{-- Background Layer --}}
    <div class="fixed inset-0 z-0">
        <img src="{{ asset('img/image_loginpage.png') }}" alt="" class="h-full w-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900/80 via-slate-900/70 to-slate-800/75"></div>
    </div>

    {{-- Main Content --}}
    <main class="relative z-10 flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="mb-8 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                        class="h-12 w-12 rounded-xl border-2 border-white/30 bg-white/20 p-1.5 object-contain shadow-lg backdrop-blur-sm">
                    <div class="text-left">
                        <p class="text-sm font-bold tracking-wide text-white">TOKO HS ELECTRIC</p>
                        <p class="text-xs font-medium text-white/70">Registrasi akun pelanggan</p>
                    </div>
                </a>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl border border-gray-200/80 bg-white p-7 shadow-2xl sm:p-9">
                <div class="mb-6">
                    <h1 class="text-2xl font-extrabold text-gray-900">Buat Akun Baru</h1>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500">
                        Lengkapi data berikut untuk mulai belanja dengan proses checkout yang lebih nyaman.
                    </p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-5" data-ui-form data-auth-form>
                    @csrf

                    {{-- Nama --}}
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-gray-700">Nama
                            Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                            autocomplete="name"
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 transition placeholder:text-gray-400 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-3 focus:ring-green-500/15"
                            placeholder="Nama lengkap Anda">
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autocomplete="username"
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 transition placeholder:text-gray-400 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-3 focus:ring-green-500/15"
                            placeholder="contoh@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password"
                            class="mb-1.5 block text-sm font-semibold text-gray-700">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-sm font-medium text-gray-900 transition placeholder:text-gray-400 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-3 focus:ring-green-500/15"
                                placeholder="Minimal 8 karakter, kombinasi huruf & angka">
                            <button type="button" data-password-toggle data-target="password"
                                aria-label="Tampilkan password"
                                class="absolute inset-y-0 right-3 inline-flex items-center text-gray-400 transition hover:text-gray-600 focus:outline-none">
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

                        {{-- Password Strength --}}
                        <div class="mt-2.5" data-password-strength data-target="password">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                <div data-strength-fill
                                    class="h-full rounded-full bg-gray-300 transition-all duration-300"
                                    style="width: 0%;"></div>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between text-[11px] leading-4">
                                <p class="font-semibold text-gray-600">Kekuatan password: <span
                                        data-strength-label>Belum diisi</span></p>
                                <p data-strength-hint class="text-gray-400">Gunakan minimal 8 karakter.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label for="password_confirmation"
                            class="mb-1.5 block text-sm font-semibold text-gray-700">Konfirmasi Password</label>
                        <div class="relative">
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-sm font-medium text-gray-900 transition placeholder:text-gray-400 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-3 focus:ring-green-500/15"
                                placeholder="Ulangi password yang sama">
                            <button type="button" data-password-toggle data-target="password_confirmation"
                                aria-label="Tampilkan password"
                                class="absolute inset-y-0 right-3 inline-flex items-center text-gray-400 transition hover:text-gray-600 focus:outline-none">
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
                        <x-input-error :messages="$errors->get('password_confirmation')"
                            class="mt-1.5 text-xs text-red-600" />
                    </div>

                    {{-- Submit --}}
                    <button type="submit" data-loading-text="Membuat akun..."
                        class="inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-green-600/20 transition hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500/30 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                        Buat Akun Sekarang
                    </button>
                </form>

                {{-- Login Link --}}
                <div class="mt-6 border-t border-gray-100 pt-5 text-center text-sm text-gray-500">
                    Sudah punya akun?
                    <a href="{{ route('login') }}"
                        class="font-semibold text-green-600 transition hover:text-green-700">
                        Masuk di sini
                    </a>
                </div>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-white/50">
                &copy; {{ date('Y') }} Toko HS ELECTRIC. Hak cipta dilindungi.
            </p>
        </div>
    </main>

    @include('auth.partials.form-micro-interactions')
</body>

</html>
