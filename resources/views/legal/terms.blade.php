@extends('layouts.storefront')

@section('title', 'Terms & Conditions - ' . \App\Models\Setting::get('store_name', 'Toko Listrik'))
@section('header_subtitle', 'Terms & Conditions')
@section('main_container_class', 'mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 lg:px-8 flex-1')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Terms & Conditions</h1>
        <p class="mt-2 text-sm text-slate-600">
            Dengan menggunakan layanan {{ \App\Models\Setting::get('store_name', 'Toko Listrik') }},
            Anda setuju terhadap syarat dan ketentuan berikut.
        </p>

        <div class="mt-6 space-y-6 text-sm leading-7 text-slate-700">
            <div>
                <h2 class="text-base font-bold text-slate-900">1. Akun Pengguna</h2>
                <p class="mt-1">Pengguna bertanggung jawab menjaga kerahasiaan akun dan password. Semua aktivitas dalam
                    akun menjadi tanggung jawab pemilik akun.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">2. Pemesanan dan Pembayaran</h2>
                <p class="mt-1">Pesanan diproses setelah pembayaran terverifikasi (untuk transfer/e-wallet) atau sesuai
                    ketentuan metode COD yang berlaku.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">3. Pengiriman</h2>
                <p class="mt-1">Estimasi pengiriman dapat berubah karena faktor operasional atau pihak ekspedisi. Nomor
                    resi akan diberikan jika tersedia.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">4. Garansi dan Klaim</h2>
                <p class="mt-1">Garansi hanya berlaku pada produk yang memenuhi ketentuan garansi toko. Klaim wajib
                    disertai bukti dan diajukan dalam periode garansi aktif.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">5. Perubahan Ketentuan</h2>
                <p class="mt-1">Kami berhak memperbarui syarat dan ketentuan sewaktu-waktu. Versi terbaru akan ditampilkan
                    pada halaman ini.</p>
            </div>
        </div>
    </section>
@endsection
