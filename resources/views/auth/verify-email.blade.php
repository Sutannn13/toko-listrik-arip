<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verifikasi Email - Toko HS ELECTRIC</title>

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

    <main data-auth-shell
        class="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-10">
        <div data-auth-grid class="grid w-full items-stretch gap-6 lg:grid-cols-[1.12fr,0.88fr] lg:gap-10">
            <section
                class="hidden rounded-3xl border border-white/35 bg-gradient-to-b from-white/24 to-white/10 p-10 text-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] backdrop-blur-sm lg:flex lg:flex-col lg:justify-between">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                        <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                            class="h-11 w-11 rounded-xl border border-white/30 bg-white/20 p-1.5 object-contain">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Toko HS
                                ELECTRIC</p>
                            <p class="text-sm font-medium text-white/85">Verifikasi akun pelanggan</p>
                        </div>
                    </a>

                    <div class="mt-10 max-w-xl">
                        <span
                            class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-50">
                            Verifikasi Email
                        </span>
                        <h1 class="mt-4 text-[2.15rem] font-extrabold leading-tight tracking-tight text-white">
                            Satu langkah lagi untuk mengaktifkan akun Anda.
                        </h1>
                        <p class="mt-4 text-base leading-relaxed text-emerald-50/95">
                            Silakan cek inbox email Anda lalu klik tautan verifikasi. Proses ini membantu menjaga
                            keamanan akun serta memastikan notifikasi transaksi dapat terkirim dengan benar.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Belum dapat email?
                        </p>
                        <p class="mt-2 text-sm text-white/90">Klik tombol kirim ulang verifikasi. Cek folder spam atau
                            promosi bila email belum muncul.</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Cepat</p>
                        <p class="mt-2 text-sm text-white/90">Link verifikasi biasanya masuk dalam hitungan detik.</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Aman</p>
                        <p class="mt-2 text-sm text-white/90">Akun hanya aktif setelah email terverifikasi.</p>
                    </div>
                </div>
            </section>

            <section class="w-full max-w-md place-self-center">
                <div class="mb-5 text-center lg:hidden">
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-white backdrop-blur-sm">
                        <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                            class="h-7 w-7 object-contain">
                        <span class="text-sm font-semibold">Toko HS ELECTRIC</span>
                    </a>
                </div>

                <div data-auth-card
                    class="rounded-3xl border border-white/75 bg-white/88 p-6 shadow-[0_24px_60px_rgba(15,23,42,0.24)] backdrop-blur-sm sm:p-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Aktivasi Akun</p>
                        <h2 data-auth-title class="mt-2 text-[1.9rem] font-extrabold leading-tight text-slate-900">
                            Verifikasi email Anda</h2>
                        <p data-auth-subtitle class="mt-2 text-sm leading-relaxed text-slate-500">Setelah email
                            terverifikasi, Anda bisa menggunakan seluruh fitur akun secara penuh.</p>
                    </div>

                    @if (session('status') == 'verification-link-sent')
                        <div
                            class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            Link verifikasi baru telah dikirim ke email yang Anda daftarkan.
                        </div>
                    @endif

                    <div class="mt-6 grid gap-3">
                        <form method="POST" action="{{ route('verification.send') }}" data-ui-form>
                            @csrf
                            <button type="submit" data-loading-text="Mengirim ulang..."
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/35 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                                Kirim Ulang Email Verifikasi
                            </button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}" data-ui-form>
                            @csrf
                            <button type="submit" data-loading-text="Keluar..."
                                class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-300/35 active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-80">
                                Keluar dari akun
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>

    @include('auth.partials.form-micro-interactions')
</body>

</html>
