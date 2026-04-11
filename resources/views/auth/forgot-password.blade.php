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
</head>

<body class="min-h-screen bg-slate-50 font-sans text-gray-900 antialiased">
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
                Reset Akun
            </span>
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-gray-900">
                Lupa Password? Kami Bantu Reset
            </h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-gray-600">
                Masukkan email akun Anda, dan kami akan mengirimkan tautan reset password dalam hitungan detik.
            </p>

            <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div
                    class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Tips Keamanan</p>
                <p class="mt-1 text-sm text-gray-700">Gunakan password kuat (min 8 karakter, campuran huruf & angka) dan
                    jangan bagikan tautan reset ke siapapun.</p>
            </div>

            <a href="{{ route('login') }}"
                class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 transition hover:text-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Login
            </a>
        </section>

        {{-- Right — Reset Form Card --}}
        <section class="w-full max-w-md mx-auto lg:mx-0">
            <div class="mb-6 text-center lg:hidden">
                <a href="{{ route('home') }}" class="inline-flex items-center">
                    <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                        class="h-10 w-10 object-contain">
                </a>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg shadow-gray-200/50 sm:p-8">
                <h2 class="text-2xl font-bold text-gray-900">Minta Link Reset</h2>
                <p class="mt-1 text-sm text-gray-500">Tautan reset akan dikirim ke email terdaftar.</p>

                <x-auth-session-status
                    class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700"
                    :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" data-ui-form>
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autofocus
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 transition duration-200 hover:border-primary-300 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15"
                            placeholder="contoh@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <button type="submit" data-loading-text="Mengirim tautan..."
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition duration-200 hover:-translate-y-0.5 hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30 active:translate-y-0 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                        Kirim Link Reset Password
                    </button>
                </form>

                <div class="mt-5 border-t border-gray-100 pt-5 text-center text-sm lg:hidden">
                    <a href="{{ route('login') }}" class="font-medium text-gray-600 hover:text-gray-900 transition">
                        Kembali ke <span class="font-semibold text-primary-600">Login</span>
                    </a>
                </div>
            </div>
        </section>
    </main>
    @include('auth.partials.form-micro-interactions')
</body>

</html>
