@extends('layouts.storefront')

@section('title', 'Privacy Policy - ' . \App\Models\Setting::get('store_name', 'Toko Listrik'))
@section('header_subtitle', 'Privacy Policy')
@section('main_container_class', 'mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 lg:px-8 flex-1')

@section('content')
    @php
        $storePhoneRaw = (string) \App\Models\Setting::get('store_phone', '');
        $storePhoneDigits = preg_replace('/\D+/', '', $storePhoneRaw);
        if ($storePhoneDigits !== '' && str_starts_with($storePhoneDigits, '0')) {
            $storePhoneDigits = '62' . substr($storePhoneDigits, 1);
        }
        $privacyWaUrl = $storePhoneDigits !== '' ? 'https://wa.me/' . $storePhoneDigits : route('home');
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Privacy Policy</h1>
        <p class="mt-2 text-sm text-slate-600">
            Kebijakan privasi ini menjelaskan bagaimana {{ \App\Models\Setting::get('store_name', 'Toko Listrik') }}
            mengumpulkan, menggunakan, dan melindungi data pelanggan.
        </p>

        <div class="mt-6 space-y-6 text-sm leading-7 text-slate-700">
            <div>
                <h2 class="text-base font-bold text-slate-900">1. Data yang Kami Kumpulkan</h2>
                <p class="mt-1">Kami dapat mengumpulkan data seperti nama, email, nomor telepon, alamat pengiriman, dan
                    riwayat transaksi untuk memproses pesanan Anda.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">2. Penggunaan Data</h2>
                <p class="mt-1">Data digunakan untuk verifikasi akun, pemrosesan pesanan, pengiriman notifikasi, dukungan
                    pelanggan, dan peningkatan layanan toko.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">3. Keamanan Data</h2>
                <p class="mt-1">Kami menerapkan langkah keamanan teknis dan operasional untuk mencegah akses tidak sah,
                    kehilangan, atau penyalahgunaan data pribadi.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">4. Pembagian Data ke Pihak Ketiga</h2>
                <p class="mt-1">Data hanya dibagikan jika diperlukan untuk operasional (misalnya kurir pengiriman atau
                    payment gateway) dan tetap dibatasi sesuai kebutuhan layanan.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-slate-900">5. Kontak</h2>
                <p class="mt-1">Jika ada pertanyaan terkait privasi, Anda dapat menghubungi kami melalui email
                    <a href="mailto:{{ \App\Models\Setting::get('store_email', 'admin@example.com') }}"
                        class="font-semibold text-primary-700 hover:text-primary-800">
                        {{ \App\Models\Setting::get('store_email', 'admin@example.com') }}
                    </a>
                    atau WhatsApp
                    <a href="{{ $privacyWaUrl }}" target="_blank" rel="noopener noreferrer"
                        class="font-semibold text-primary-700 hover:text-primary-800">
                        {{ \App\Models\Setting::get('store_phone', '-') }}
                    </a>.
                </p>
            </div>
        </div>
    </section>
@endsection
