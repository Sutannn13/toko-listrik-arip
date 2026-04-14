@extends('layouts.storefront')

@section('title',
    'Hasil Lacak Pesanan ' .
    $order->order_code .
    ' - ' .
    \App\Models\Setting::get(
    'store_name',
    'Toko
    Listrik',
    ))
@section('header_subtitle', 'Hasil Pelacakan')
@section('show_default_store_actions', 'off')
@section('main_container_class', 'mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8 flex-1')
@section('footer')
    @include('layouts.partials.flowbite-footer')
@endsection

@section('background')
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-32 left-0 h-96 w-96 rounded-full bg-primary-100/40 blur-3xl"></div>
    </div>
@endsection

@section('header_actions')
    <a href="{{ route('home.tracking') }}"
        class="hidden sm:inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:border-primary-500 hover:text-primary-600 hover:bg-gray-50">
        Kembali ke Daftar Pesanan
    </a>
@endsection

@section('content')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                Status Pesanan
                <span
                    class="inline-block rounded-md bg-gray-100 px-2 py-1 text-sm font-mono text-gray-700 border border-gray-200">{{ $order->order_code }}</span>
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Dipesan pada
                {{ optional($order->placed_at)->format('d F Y H:i') ?? $order->created_at->format('d F Y H:i') }}
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 flex gap-3 text-sm text-green-700 items-start">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex gap-3 text-sm text-red-700 items-start">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    @php
        $latestPayment = $order->payments->sortByDesc('id')->first();
        $proofMethods = ['bank_transfer', 'ewallet', 'dummy'];
        $isProofPaymentMethod = $latestPayment && in_array($latestPayment->method, $proofMethods, true);
        $isCodMethod = $latestPayment && $latestPayment->method === 'cod';
        $isBayarGgMethod = $latestPayment && $latestPayment->method === 'bayargg';

        $isWaitingAdminApproval =
            $latestPayment &&
            in_array($latestPayment->method, ['bank_transfer', 'ewallet', 'dummy'], true) &&
            $latestPayment->status === 'pending' &&
            filled($latestPayment->proof_url);

        $isWaitingGatewayPayment =
            $latestPayment && $latestPayment->method === 'bayargg' && $order->payment_status !== 'paid';

        $bankTransferAccounts = array_values(
            array_filter(
                [
                    [
                        'name' => \App\Models\Setting::get('bank_1_name', 'BCA'),
                        'number' => \App\Models\Setting::get('bank_1_account'),
                        'holder' => \App\Models\Setting::get('bank_1_holder'),
                    ],
                    [
                        'name' => \App\Models\Setting::get('bank_2_name', 'BRI'),
                        'number' => \App\Models\Setting::get('bank_2_account'),
                        'holder' => \App\Models\Setting::get('bank_2_holder'),
                    ],
                ],
                fn(array $account) => filled($account['name']) && filled($account['number']),
            ),
        );

        $ewalletAccount = [
            'name' => \App\Models\Setting::get('bank_3_name', 'DANA'),
            'number' => \App\Models\Setting::get('bank_3_account'),
            'holder' => \App\Models\Setting::get('bank_3_holder'),
        ];
    @endphp

    <!-- Resi Display -->
    @if ($order->tracking_number)
        <div
            class="mb-4 rounded-xl border border-primary-200 bg-primary-50 p-4 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-bold text-primary-700 uppercase tracking-wider mb-1">Nomor Resi
                    Pengiriman</p>
                <p class="text-lg font-mono font-bold text-gray-900">{{ $order->tracking_number }}</p>
            </div>
        </div>
    @endif

    <!-- Payment Proof Form -->
    @if ($order->status !== 'cancelled' && $order->payment_status !== 'paid')
        @if ($latestPayment)
            @if ($isBayarGgMethod)
                <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-indigo-900 mb-2">Pembayaran Otomatis via Bayar.gg</h3>
                    <p class="text-xs text-indigo-700 mb-3">
                        Metode ini tidak memerlukan upload bukti pembayaran manual. Silakan lanjutkan pembayaran melalui
                        halaman Bayar.gg agar status order terkonfirmasi otomatis.
                    </p>

                    <div class="mb-4 grid gap-2 rounded-lg border border-indigo-200 bg-white p-3 text-xs text-gray-700">
                        <p><span class="font-semibold">Invoice Gateway:</span>
                            {{ $latestPayment->gateway_invoice_id ?: '-' }}</p>
                        <p><span class="font-semibold">Status Gateway:</span>
                            {{ strtoupper($latestPayment->gateway_status ?: 'pending') }}</p>
                        @if ($latestPayment->gateway_expires_at)
                            <p><span class="font-semibold">Batas Waktu:</span>
                                {{ $latestPayment->gateway_expires_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        @if (filled($latestPayment->gateway_payment_url))
                            <a href="{{ $latestPayment->gateway_payment_url }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 shadow-md">
                                Bayar Sekarang di Bayar.gg
                            </a>
                        @else
                            <p class="text-xs text-indigo-700">Link pembayaran belum tersedia. Klik tombol buat ulang di
                                samping.</p>
                        @endif

                        <form action="{{ route('home.tracking.bayargg.regenerate', $order->order_code) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 py-2.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                Buat Link Bayar.gg Lagi
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($isProofPaymentMethod && !$latestPayment->proof_url)
                <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-blue-900 mb-2">Segera Upload Bukti Pembayaran</h3>
                    <p class="text-xs text-blue-700 mb-3">
                        Bayar sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                        sesuai metode yang dipilih, lalu upload bukti agar status masuk antrean ACC admin.
                    </p>

                    @if (in_array($latestPayment->method, ['bank_transfer', 'dummy'], true))
                        <div class="mb-4 space-y-2 rounded-lg border border-blue-200 bg-white p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-700">Rekening Transfer
                                Bank</p>
                            @forelse ($bankTransferAccounts as $account)
                                <p class="text-xs text-gray-700">
                                    <span class="font-semibold">{{ $account['name'] }}</span>
                                    {{ $account['number'] }}
                                    @if (filled($account['holder']))
                                        a/n {{ $account['holder'] }}
                                    @endif
                                </p>
                            @empty
                                <p class="text-xs text-gray-700">Info rekening bank belum disetel oleh admin.</p>
                            @endforelse
                        </div>
                    @elseif($latestPayment->method === 'ewallet')
                        <div class="mb-4 rounded-lg border border-blue-200 bg-white p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-700">Akun E-Wallet /
                                E-Money</p>
                            <p class="mt-1 text-xs text-gray-700">
                                <span class="font-semibold">{{ $ewalletAccount['name'] }}</span>
                                {{ $ewalletAccount['number'] ?: '-' }}
                                @if (filled($ewalletAccount['holder']))
                                    a/n {{ $ewalletAccount['holder'] }}
                                @endif
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('home.tracking.proof', $order->order_code) }}" method="POST"
                        enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="file" name="payment_proof" accept="image/*" required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 transition bg-white">
                        <button type="submit"
                            class="whitespace-nowrap rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700 shadow-md">
                            Unggah Bukti
                        </button>
                    </form>
                    @error('payment_proof')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @elseif($isProofPaymentMethod && $latestPayment->proof_url)
                @php
                    $isProofRejected = $latestPayment->status === 'failed';
                @endphp
                <div
                    class="mb-6 rounded-xl border p-4 shadow-sm {{ $isProofRejected ? 'border-red-200 bg-red-50' : 'border-blue-200 bg-blue-50' }}">
                    <div class="flex items-center gap-4">
                        @if ($isProofRejected)
                            <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12">
                                </path>
                            </svg>
                        @else
                            <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                        <div>
                            @if ($isProofRejected)
                                <p class="text-sm font-bold text-red-900">Bukti Pembayaran Ditolak Admin</p>
                                <p class="text-xs text-red-700 mt-0.5">Silakan unggah ulang bukti pembayaran yang valid.</p>
                            @else
                                <p class="text-sm font-bold text-blue-900">Bukti Pembayaran Berhasil Diunggah</p>
                                <p class="text-xs text-blue-700 mt-0.5">Status pembayaran Anda saat ini menunggu ACC admin.
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($isProofRejected && $latestPayment->notes)
                        <div class="mt-3 rounded-lg border border-red-200 bg-white px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-red-700">Catatan Admin</p>
                            <p class="mt-1 text-xs text-red-700">{{ $latestPayment->notes }}</p>
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a href="{{ Storage::disk('public')->url($latestPayment->proof_url) }}" target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $isProofRejected ? 'border-red-300 text-red-700 hover:bg-red-100' : 'border-blue-300 text-blue-700 hover:bg-blue-100' }} bg-white">
                            Lihat Bukti Saat Ini
                        </a>
                    </div>

                    @if (in_array($latestPayment->status, ['pending', 'failed'], true))
                        <form action="{{ route('home.tracking.proof', $order->order_code) }}" method="POST"
                            enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-[1fr,auto]">
                            @csrf
                            <input type="hidden" name="replace_proof" value="1">
                            <input type="file" name="payment_proof" accept="image/*" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold transition bg-white {{ $isProofRejected ? 'file:bg-red-100 file:text-red-700 hover:file:bg-red-200' : 'file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200' }}">
                            <button type="submit"
                                class="whitespace-nowrap rounded-xl px-6 py-2.5 text-sm font-bold text-white transition shadow-md {{ $isProofRejected ? 'bg-red-700 hover:bg-red-800' : 'bg-blue-700 hover:bg-blue-800' }}">
                                Ganti Bukti
                            </button>
                        </form>
                        @error('payment_proof')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            @elseif($isCodMethod)
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-sm font-bold text-emerald-900">Metode Pembayaran COD</p>
                    <p class="mt-1 text-xs text-emerald-700">
                        Pesanan ini menggunakan COD, jadi tidak perlu upload bukti pembayaran. Silakan bayar saat barang
                        diterima.
                    </p>
                </div>
            @endif
        @endif
    @endif

    <!-- Status Banner -->
    <div
        class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4 w-full sm:w-auto">
            @if ($order->status === 'completed')
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-green-600">Pesanan Selesai</p>
                    <p class="text-sm font-medium text-gray-900">Barang telah diterima oleh pelanggan.</p>
                </div>
            @elseif($order->status === 'cancelled')
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-red-600">Dibatalkan</p>
                    <p class="text-sm font-medium text-gray-900">Pesanan ini telah dibatalkan.</p>
                </div>
            @else
                <!-- Processing Status -->
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Sedang Diproses</p>
                    <p class="text-sm font-medium text-gray-900">Pesanan Anda dalam tahap
                        ({{ $order->status }}).</p>
                </div>
            @endif
        </div>

        @php
            $paymentBoxClass =
                $order->payment_status === 'paid'
                    ? 'bg-green-50 border border-green-200'
                    : ($isWaitingAdminApproval
                        ? 'bg-blue-50 border border-blue-200'
                        : ($isWaitingGatewayPayment
                            ? 'bg-indigo-50 border border-indigo-200'
                            : 'bg-orange-50 border border-orange-200'));
        @endphp
        <div class="w-full sm:w-auto p-4 rounded-xl {{ $paymentBoxClass }}">
            <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Status Pembayaran</p>
            @if ($order->payment_status === 'paid')
                <p class="text-sm font-bold text-green-700 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg> Lunas (Paid)</p>
            @elseif($isWaitingAdminApproval)
                <p class="text-sm font-bold text-blue-700 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg> Menunggu ACC Admin</p>
            @elseif($isWaitingGatewayPayment)
                <p class="text-sm font-bold text-indigo-700 flex items-center gap-1.5"><svg class="w-4 h-4"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg> Menunggu Pembayaran Gateway</p>
            @else
                <p class="text-sm font-bold text-orange-700 flex items-center gap-1.5"><svg class="w-4 h-4"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg> Belum Lunas ({{ $order->payment_status }})</p>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Customer & Address Info -->
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 uppercase tracking-wider">
                    Informasi Pengiriman</h3>

                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Nama Tujuan</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Kontak</p>
                        <p class="text-sm font-medium text-gray-900">{{ $order->customer_phone }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Alamat Lengkap</p>
                        <p class="text-sm font-medium text-gray-900 leading-relaxed">{{ $shippingAddress }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm filter flex flex-col h-full">
                <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 uppercase tracking-wider">
                    Daftar Produk</h3>

                <div class="space-y-4 flex-1">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between items-start border-b border-gray-50 pb-4 last:border-0 last:pb-0">
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ number_format($item->quantity) }} x
                                    Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-extrabold text-gray-900">Rp
                                    {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-gray-200 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Item</span>
                        <span class="font-bold text-gray-900">{{ number_format($order->items->sum('quantity')) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">Rp
                            {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Ongkir</span>
                        <span class="font-semibold text-gray-900">Rp
                            {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base items-center pt-2">
                        <span class="font-bold text-gray-900">Total Pembayaran</span>
                        <span class="text-xl font-black text-primary-600">Rp
                            {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('home') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-3 text-sm font-bold text-white transition hover:bg-gray-800 shadow-md">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Kembali ke Beranda
        </a>
    </div>
@endsection
