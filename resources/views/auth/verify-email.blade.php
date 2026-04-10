<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Verifikasi Email Anda</h1>
        <p class="mt-1 text-sm text-gray-500">
            Cek inbox Anda lalu klik tautan verifikasi. Jika belum menerima email, kirim ulang dari tombol di bawah.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700">
            Link verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}" data-ui-form>
            @csrf

            <div>
                <x-primary-button class="w-full justify-center sm:w-auto" data-loading-text="Mengirim ulang...">
                    Kirim Ulang Email Verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}" data-ui-form>
            @csrf

            <button type="submit" data-loading-text="Keluar..."
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-200 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-primary-500/20 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
