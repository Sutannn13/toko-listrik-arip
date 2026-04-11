<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Reset Password - Toko HS ELECTRIC</title>

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
                            <p class="text-sm font-medium text-white/85">Pemulihan akun aman</p>
                        </div>
                    </a>

                    <div class="mt-10 max-w-xl">
                        <span
                            class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-50">
                            Lupa Password
                        </span>
                        <h1 class="mt-4 text-[2.15rem] font-extrabold leading-tight tracking-tight text-white">
                            Pemulihan akun yang cepat, aman, dan tetap nyaman di mata.
                        </h1>
                        <p class="mt-4 text-base leading-relaxed text-emerald-50">
                            Masukkan email terdaftar untuk menerima tautan reset password. Prosesnya singkat, dan Anda
                            bisa kembali menggunakan akun tanpa ribet.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Tips Keamanan</p>
                        <p class="mt-2 text-sm text-white">Gunakan password baru yang unik, minimal 8 karakter, dan
                            jangan membagikan tautan reset kepada orang lain.</p>
                    </div>
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Email Valid</p>
                        <p class="mt-2 text-sm text-white">Pastikan email sesuai dengan akun yang terdaftar.</p>
                    </div>
                    <div class="rounded-2xl border border-white/30 bg-white/18 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Cek Inbox</p>
                        <p class="mt-2 text-sm text-white">Lihat folder spam jika email reset belum terlihat.</p>
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
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Reset Password</p>
                        <h2 data-auth-title class="mt-2 text-[1.9rem] font-extrabold leading-tight text-slate-900">Minta
                            tautan reset</h2>
                        <p data-auth-subtitle class="mt-2 text-sm leading-relaxed text-slate-500">Kami akan mengirimkan
                            link reset ke email Anda dalam beberapa detik.</p>
                    </div>

                    <x-auth-session-status
                        class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                        :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" data-ui-form
                        data-auth-form>
                        @csrf

                        <div>
                            <label for="email"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                autofocus
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                placeholder="contoh@email.com">
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                        </div>

                        <button type="submit" data-loading-text="Mengirim tautan..."
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/35 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                            Kirim Link Reset Password
                        </button>
                    </form>

                    <div class="mt-6 border-t border-slate-200 pt-5 text-center text-sm text-slate-600">
                        <a href="{{ route('login') }}"
                            class="font-semibold text-emerald-700 transition hover:text-emerald-800">
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
