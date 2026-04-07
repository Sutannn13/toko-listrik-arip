<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Daftar Akun - Toko Listrik Arip</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-36 left-0 h-96 w-96 rounded-full bg-cyan-500/20 blur-3xl"></div>
        <div class="absolute top-1/3 -right-28 h-[24rem] w-[24rem] rounded-full bg-blue-600/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-teal-500/15 blur-3xl"></div>
    </div>

    <main
        class="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl flex-col justify-center gap-8 px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[1.2fr,1fr] lg:px-8 lg:py-16">
        <section
            class="rounded-3xl border border-slate-800/80 bg-gradient-to-br from-slate-900/95 via-slate-900/85 to-cyan-950/40 p-6 shadow-2xl shadow-slate-950/50 sm:p-8">
            <p
                class="mb-3 inline-flex rounded-full border border-cyan-400/40 bg-cyan-500/10 px-3 py-1 text-xs font-bold tracking-[0.16em] text-cyan-200">
                BUAT AKUN BARU
            </p>
            <h1 class="text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                Daftar Sekarang, Kelola Belanja & Garansi Lebih Mudah
            </h1>
            <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                Akun customer dipakai untuk checkout lebih cepat, simpan alamat, pantau pesanan, dan ajukan klaim
                garansi secara online.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Checkout Cepat</p>
                    <p class="mt-2 text-sm text-slate-200">Alamat tersimpan langsung bisa dipilih saat order.</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Garansi Online</p>
                    <p class="mt-2 text-sm text-slate-200">Riwayat klaim tercatat transparan lewat sistem.</p>
                </div>
            </div>

            <a href="{{ route('landing') }}"
                class="mt-6 inline-flex rounded-xl border border-cyan-400/40 px-4 py-2 text-sm font-semibold text-cyan-200 transition hover:border-cyan-300 hover:bg-cyan-400/10">
                &larr; Kembali ke Landing Page
            </a>
        </section>

        <section
            class="rounded-3xl border border-slate-800/80 bg-slate-900/90 p-6 shadow-2xl shadow-slate-950/50 sm:p-8">
            <h2 class="text-2xl font-bold text-white">Daftar Akun</h2>
            <p class="mt-1 text-sm text-slate-400">Isi data di bawah ini untuk membuat akun baru.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label for="name"
                        class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Nama Lengkap
                    </label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        autocomplete="name"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none"
                        placeholder="Nama lengkap">
                    <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs text-rose-300" />
                </div>

                <div>
                    <label for="email"
                        class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Email
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        autocomplete="username"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none"
                        placeholder="contoh@email.com">
                    <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-rose-300" />
                </div>

                <div>
                    <label for="password"
                        class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Password
                    </label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none"
                        placeholder="Buat password">
                    <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-rose-300" />
                </div>

                <div>
                    <label for="password_confirmation"
                        class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Konfirmasi Password
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none"
                        placeholder="Ulangi password">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-rose-300" />
                </div>

                <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-900/30 transition hover:brightness-110">
                    Daftar Sekarang
                </button>
            </form>

            <div class="mt-4 text-sm">
                <a href="{{ route('login') }}" class="font-semibold text-cyan-300 hover:text-cyan-200">
                    Sudah punya akun? Masuk
                </a>
            </div>
        </section>
    </main>
</body>

</html>
