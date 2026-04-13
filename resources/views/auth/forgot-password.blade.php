<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Reset Password - Toko HS ELECTRIC</title>

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
                        <p class="text-xs font-medium text-white/70">Pemulihan akun aman</p>
                    </div>
                </a>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl border border-gray-200/80 bg-white p-7 shadow-2xl sm:p-9">
                <div class="mb-2">
                    {{-- Lock Icon --}}
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
                        <svg class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-extrabold text-gray-900">Lupa Password?</h1>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500">
                        Masukkan alamat email yang terdaftar. Kami akan mengirimkan link untuk
                        mereset password Anda.
                    </p>
                </div>

                <x-auth-session-status
                    class="mt-4 mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                    :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" data-ui-form
                    data-auth-form>
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 transition placeholder:text-gray-400 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-3 focus:ring-green-500/15"
                            placeholder="contoh@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    {{-- Submit --}}
                    <button type="submit" data-loading-text="Mengirim tautan..."
                        class="inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-green-600/20 transition hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500/30 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                        Kirim Link Reset Password
                    </button>
                </form>

                {{-- Back to Login --}}
                <div class="mt-6 border-t border-gray-100 pt-5 text-center text-sm text-gray-500">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1.5 font-semibold text-green-600 transition hover:text-green-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali ke halaman login
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
