<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Atur Ulang Password - Toko HS ELECTRIC</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen text-slate-900 antialiased"
    style="font-family: 'Manrope', sans-serif; background-image: linear-gradient(120deg, rgba(6, 95, 70, 0.58) 0%, rgba(16, 185, 129, 0.44) 36%, rgba(15, 23, 42, 0.36) 100%), url('{{ asset('img/image_loginpage.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-16 -top-20 h-72 w-72 rounded-full bg-emerald-100/35 blur-3xl"></div>
        <div class="absolute -right-16 bottom-0 h-80 w-80 rounded-full bg-cyan-100/25 blur-3xl"></div>
    </div>

    <main data-auth-shell class="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-10">
        <div data-auth-grid class="grid w-full items-stretch gap-6 lg:grid-cols-[1.12fr,0.88fr] lg:gap-10">
            <section
                class="hidden rounded-3xl border border-white/35 bg-gradient-to-b from-white/24 to-white/10 p-10 text-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] backdrop-blur-sm lg:flex lg:flex-col lg:justify-between">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                        <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                            class="h-11 w-11 rounded-xl border border-white/30 bg-white/20 p-1.5 object-contain">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Toko HS ELECTRIC</p>
                            <p class="text-sm font-medium text-white/85">Pembaruan kredensial akun</p>
                        </div>
                    </a>

                    <div class="mt-10 max-w-xl">
                        <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-50">
                            Atur Ulang Password
                        </span>
                        <h1 class="mt-4 text-[2.15rem] font-extrabold leading-tight tracking-tight text-white">
                            Buat password baru untuk menjaga keamanan akun Anda.
                        </h1>
                        <p class="mt-4 text-base leading-relaxed text-emerald-50/95">
                            Silakan gunakan kombinasi karakter yang kuat agar akun tetap aman. Setelah tersimpan, Anda bisa langsung login kembali.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Tips Password</p>
                        <p class="mt-2 text-sm text-white/90">Gunakan gabungan huruf besar, huruf kecil, angka, dan simbol agar kekuatan password lebih optimal.</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Mudah Diingat</p>
                        <p class="mt-2 text-sm text-white/90">Pilih pola unik yang Anda kenali, bukan yang mudah ditebak.</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Tetap Aman</p>
                        <p class="mt-2 text-sm text-white/90">Hindari memakai password yang sama dengan akun lain.</p>
                    </div>
                </div>
            </section>

            <section class="w-full max-w-md place-self-center">
                <div class="mb-5 text-center lg:hidden">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-white backdrop-blur-sm">
                        <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC" class="h-7 w-7 object-contain">
                        <span class="text-sm font-semibold">Toko HS ELECTRIC</span>
                    </a>
                </div>

                <div data-auth-card class="rounded-3xl border border-white/75 bg-white/88 p-6 shadow-[0_24px_60px_rgba(15,23,42,0.24)] backdrop-blur-sm sm:p-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Password Baru</p>
                        <h2 data-auth-title class="mt-2 text-[1.9rem] font-extrabold leading-tight text-slate-900">Atur ulang password</h2>
                        <p data-auth-subtitle class="mt-2 text-sm leading-relaxed text-slate-500">Masukkan password baru Anda, lalu konfirmasi untuk menyelesaikan proses pemulihan akun.</p>
                    </div>

                    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5" data-ui-form data-auth-form>
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus
                                autocomplete="username"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                placeholder="contoh@email.com">
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password Baru</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required autocomplete="new-password"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm font-medium text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                    placeholder="Masukkan password baru">
                                <button type="button" data-password-toggle data-target="password" aria-label="Tampilkan password"
                                    class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-emerald-700 focus:outline-none">
                                    <svg data-icon-show class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg data-icon-hide class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6M6.7 6.7C4.1 8.2 2.5 11 2.5 12c0 0 3.2 6 9.5 6 2.1 0 3.9-.7 5.3-1.7M9.2 5.1A10.2 10.2 0 0 1 12 4.5c6.3 0 9.5 7.5 9.5 7.5a15.6 15.6 0 0 1-2.2 3.1" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-red-600" />

                            <div class="mt-2.5" data-password-strength data-target="password">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div data-strength-fill class="h-full rounded-full bg-slate-300 transition-all duration-300" style="width: 0%;"></div>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between text-[11px] leading-4">
                                    <p class="font-semibold text-slate-600">Kekuatan password: <span data-strength-label>Belum diisi</span></p>
                                    <p data-strength-hint class="text-slate-500">Gunakan minimal 8 karakter.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Konfirmasi Password Baru</label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                    autocomplete="new-password"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm font-medium text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                    placeholder="Ulangi password baru">
                                <button type="button" data-password-toggle data-target="password_confirmation" aria-label="Tampilkan password"
                                    class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-emerald-700 focus:outline-none">
                                    <svg data-icon-show class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg data-icon-hide class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6M6.7 6.7C4.1 8.2 2.5 11 2.5 12c0 0 3.2 6 9.5 6 2.1 0 3.9-.7 5.3-1.7M9.2 5.1A10.2 10.2 0 0 1 12 4.5c6.3 0 9.5 7.5 9.5 7.5a15.6 15.6 0 0 1-2.2 3.1" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs text-red-600" />
                        </div>

                        <button type="submit" data-loading-text="Menyimpan password..."
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/35 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                            Simpan Password Baru
                        </button>
                    </form>

                    <div class="mt-6 border-t border-slate-200 pt-5 text-center text-sm text-slate-600">
                        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 transition hover:text-emerald-800">
                            Kembali ke halaman login
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    @include('auth.partials.form-micro-interactions')
</body>

</html>
