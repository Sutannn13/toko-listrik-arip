<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Daftar Akun - Toko Listrik Arip</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 font-sans text-gray-900 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-primary-100/60 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-primary-50/80 blur-3xl"></div>
    </div>

    <main class="relative z-10 mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center gap-8 px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[1.2fr,1fr] lg:items-center lg:gap-16 lg:px-8 lg:py-16">
        {{-- Left — Brand Panel --}}
        <section class="hidden lg:block">
            <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-700">
                Buat Akun Baru
            </span>
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-gray-900">
                Daftar Sekarang, Belanja Lebih Mudah
            </h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-gray-600">
                Dengan akun Toko Arip, Anda bisa menyimpan alamat pengiriman, melacak pesanan, dan mengajukan klaim garansi secara online.
            </p>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Checkout Cepat</p>
                    <p class="mt-1 text-sm text-gray-700">Alamat tersimpan langsung bisa dipilih saat order.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Garansi Online</p>
                    <p class="mt-1 text-sm text-gray-700">Riwayat klaim tercatat transparan lewat sistem.</p>
                </div>
            </div>

            <a href="{{ route('landing') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 transition hover:text-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Landing Page
            </a>
        </section>

        {{-- Right — Register Form Card --}}
        <section class="w-full max-w-md mx-auto lg:mx-0">
            <div class="mb-6 text-center lg:hidden">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-sm font-extrabold text-white shadow-md shadow-primary-500/30">TA</span>
                    <span class="text-lg font-bold text-gray-900">Toko Listrik Arip</span>
                </a>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg shadow-gray-200/50 sm:p-8">
                <h2 class="text-2xl font-bold text-gray-900">Daftar Akun</h2>
                <p class="mt-1 text-sm text-gray-500">Isi data di bawah ini untuk membuat akun baru.</p>

                <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-gray-700">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
                            placeholder="Nama lengkap Anda">
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
                            placeholder="contoh@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-gray-700">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
                            placeholder="Buat password kuat">
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-gray-700">Konfirmasi Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
                            placeholder="Ulangi password">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs text-red-600" />
                    </div>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30">
                        Daftar Sekarang
                    </button>
                </form>

                <div class="mt-5 border-t border-gray-100 pt-5 text-center text-sm">
                    <a href="{{ route('login') }}" class="font-medium text-gray-600 hover:text-gray-900 transition">
                        Sudah punya akun? <span class="font-semibold text-primary-600">Masuk</span>
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
