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
@section('main_container_class', 'mx-auto w-full max-w-4xl px-4 pt-4 pb-28 sm:px-6 lg:px-8 sm:pb-8 sm:pt-8 flex-1')
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

    @php
        $latestPayment = $order->payments->sortByDesc('id')->first();
        $proofMethods = ['bank_transfer', 'ewallet', 'dummy'];
        $isProofPaymentMethod = $latestPayment && in_array($latestPayment->method, $proofMethods, true);
        $isCodMethod = $latestPayment && $latestPayment->method === 'cod';
        $isBayarGgMethod = $latestPayment && $latestPayment->method === 'bayargg';
        $isCancelled = $order->status === 'cancelled';

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

        // Payment stepper
        $paymentStep = 1;
        if ($order->payment_status === 'paid') {
            $paymentStep = 4;
        } elseif ($isWaitingAdminApproval) {
            $paymentStep = 3;
        } elseif ($isProofPaymentMethod && !$latestPayment?->proof_url && !$isCodMethod) {
            $paymentStep = 2;
        } elseif ($isCodMethod || $isBayarGgMethod) {
            $paymentStep = $order->payment_status === 'paid' ? 4 : 2;
        }
        if ($isCancelled) {
            $paymentStep = 0;
        }

        $stepperSteps = [
            ['label' => 'Pesanan Dibuat', 'icon' => 'clipboard'],
            ['label' => 'Pembayaran', 'icon' => 'credit-card'],
            ['label' => 'Verifikasi', 'icon' => 'shield-check'],
            ['label' => 'Selesai', 'icon' => 'check-circle'],
        ];
    @endphp

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-medium">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Order Header Card --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Pesanan</p>
                    <h1 class="mt-1 text-xl font-bold text-gray-900 font-mono">{{ $order->order_code }}</h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Dipesan pada
                        {{ optional($order->placed_at)->format('d F Y, H:i') ?? $order->created_at->format('d F Y, H:i') }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    @php
                        $statusColors = [
                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                            'shipped' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                        ];
                        $statusLabels = [
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            'shipped' => 'Dikirim',
                            'processing' => 'Diproses',
                            'pending' => 'Menunggu',
                        ];
                    @endphp
                    <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusColors[$order->status] ?? $statusColors['pending'] }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                    @php
                        $payColors = [
                            'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'failed' => 'bg-red-50 text-red-700 border-red-200',
                            'refunded' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        ];
                        $payLabels = [
                            'paid' => 'Lunas',
                            'pending' => 'Belum Bayar',
                            'failed' => 'Gagal',
                            'refunded' => 'Refunded',
                        ];
                    @endphp
                    <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $payColors[$order->payment_status] ?? $payColors['pending'] }}">
                        {{ $payLabels[$order->payment_status] ?? $order->payment_status }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Payment Progress Stepper --}}
        @unless ($isCancelled)
            <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                <div class="flex items-center">
                    @foreach ($stepperSteps as $i => $step)
                        @php
                            $stepNum = $i + 1;
                            $isActive = $stepNum <= $paymentStep;
                            $isCurrent = $stepNum === $paymentStep;
                        @endphp
                        <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full transition-colors
                                    {{ $isActive ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}
                                    {{ $isCurrent ? 'ring-4 ring-primary-100' : '' }}">
                                    @if ($isActive && !$isCurrent)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <span class="text-xs font-bold">{{ $stepNum }}</span>
                                    @endif
                                </div>
                                <p
                                    class="mt-1.5 text-[10px] font-semibold text-center {{ $isActive ? 'text-primary-700' : 'text-gray-400' }}">
                                    {{ $step['label'] }}</p>
                            </div>
                            @unless ($loop->last)
                                <div
                                    class="mx-2 flex-1 h-0.5 rounded {{ $stepNum < $paymentStep ? 'bg-primary-500' : 'bg-gray-200' }}">
                                </div>
                            @endunless
                        </div>
                    @endforeach
                </div>
            </div>
        @endunless
    </div>

    {{-- Resi Display --}}
    @if ($order->tracking_number)
        <div
            class="mb-6 flex items-center justify-between rounded-xl border border-primary-200 bg-primary-50 p-4 shadow-sm">
            <div>
                <p class="text-xs font-bold text-primary-700 uppercase tracking-wider">Nomor Resi Pengiriman</p>
                <p class="mt-1 text-lg font-mono font-bold text-gray-900">{{ $order->tracking_number }}</p>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $order->tracking_number }}')" type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-primary-300 bg-white px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-100">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Salin
            </button>
        </div>
    @endif

    {{-- Payment Action Area --}}
    @if ($order->status !== 'cancelled' && $order->payment_status !== 'paid')
        @if ($latestPayment)
            @if ($isBayarGgMethod)
                <div class="mb-6 rounded-2xl border border-indigo-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-indigo-50 border-b border-indigo-100 px-5 py-3">
                        <h3 class="text-sm font-bold text-indigo-900 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Pembayaran Otomatis via Bayar.gg
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-sm text-gray-600">Scan QRIS dari halaman Bayar.gg — verifikasi otomatis tanpa upload
                            bukti.</p>
                        <div class="grid gap-2 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                            <p><span class="font-semibold text-gray-900">Invoice:</span>
                                {{ $latestPayment->gateway_invoice_id ?: '-' }}</p>
                            <p><span class="font-semibold text-gray-900">Status:</span>
                                {{ strtoupper($latestPayment->gateway_status ?: 'pending') }}</p>
                            @if ($latestPayment->gateway_expires_at)
                                <p><span class="font-semibold text-gray-900">Batas Waktu:</span>
                                    {{ $latestPayment->gateway_expires_at->format('d M Y H:i') }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            @if (filled($latestPayment->gateway_payment_url))
                                <a href="{{ $latestPayment->gateway_payment_url }}" target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Bayar Sekarang
                                </a>
                            @endif
                            <form action="{{ route('home.tracking.bayargg.regenerate', $order->order_code) }}"
                                method="POST">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 py-3 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                    Buat Link Ulang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @elseif($isProofPaymentMethod && !$latestPayment->proof_url)
                {{-- Upload Payment Proof --}}
                <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-blue-50 border-b border-blue-100 px-5 py-3">
                        <h3 class="text-sm font-bold text-blue-900 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Upload Bukti Pembayaran
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-sm text-gray-600">
                            Transfer <strong class="text-gray-900">Rp
                                {{ number_format($order->total_amount, 0, ',', '.') }}</strong> lalu upload bukti agar
                            status masuk antrean verifikasi admin.
                        </p>

                        {{-- Bank Account Cards --}}
                        @if (in_array($latestPayment->method, ['bank_transfer', 'dummy'], true))
                            <div class="grid gap-3 sm:grid-cols-2">
                                @forelse ($bankTransferAccounts as $account)
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            {{ $account['name'] }}</p>
                                        <div class="mt-2 flex items-center justify-between gap-2">
                                            <p class="text-lg font-bold text-gray-900 font-mono tracking-wide">
                                                {{ $account['number'] }}</p>
                                            <button type="button"
                                                onclick="navigator.clipboard.writeText('{{ $account['number'] }}')"
                                                class="shrink-0 rounded-lg border border-gray-300 bg-white p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                                                title="Salin nomor rekening">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        @if (filled($account['holder']))
                                            <p class="mt-1 text-xs text-gray-500">a.n. {{ $account['holder'] }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500 col-span-full">Info rekening bank belum disetel oleh
                                        admin.</p>
                                @endforelse
                            </div>
                        @elseif($latestPayment->method === 'ewallet')
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    {{ $ewalletAccount['name'] }}</p>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <p class="text-lg font-bold text-gray-900 font-mono tracking-wide">
                                        {{ $ewalletAccount['number'] ?: '-' }}</p>
                                    @if ($ewalletAccount['number'])
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $ewalletAccount['number'] }}')"
                                            class="shrink-0 rounded-lg border border-gray-300 bg-white p-2 text-gray-500 transition hover:bg-gray-100"
                                            title="Salin">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                @if (filled($ewalletAccount['holder']))
                                    <p class="mt-1 text-xs text-gray-500">a.n. {{ $ewalletAccount['holder'] }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Dropzone Upload --}}
                        <form action="{{ route('home.tracking.proof', $order->order_code) }}" method="POST"
                            enctype="multipart/form-data" x-data="{ fileName: '', preview: null }" class="space-y-3">
                            @csrf
                            <label
                                class="group flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-6 transition hover:border-primary-400 hover:bg-primary-50/30">
                                <div x-show="!preview" class="text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400 group-hover:text-primary-500 transition"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-semibold text-gray-700">Drag & drop atau klik untuk upload
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">JPG, PNG — Maks 2MB</p>
                                </div>
                                <img x-show="preview" :src="preview" class="max-h-32 rounded-lg object-contain"
                                    alt="Preview">
                                <p x-show="fileName" x-text="fileName" class="mt-2 text-xs font-medium text-primary-700">
                                </p>
                                <input type="file" name="payment_proof" accept="image/*" required class="sr-only"
                                    @change="fileName = $event.target.files[0]?.name || ''; if($event.target.files[0]) { const reader = new FileReader(); reader.onload = (e) => preview = e.target.result; reader.readAsDataURL($event.target.files[0]); }">
                            </label>
                            @error('payment_proof')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                class="w-full rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50">
                                Unggah Bukti Pembayaran
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($isProofPaymentMethod && $latestPayment->proof_url)
                @php $isProofRejected = $latestPayment->status === 'failed'; @endphp
                <div
                    class="mb-6 rounded-2xl border {{ $isProofRejected ? 'border-red-200' : 'border-emerald-200' }} bg-white shadow-sm overflow-hidden">
                    <div
                        class="px-5 py-4 {{ $isProofRejected ? 'bg-red-50 border-b border-red-100' : 'bg-emerald-50 border-b border-emerald-100' }}">
                        <div class="flex items-center gap-3">
                            @if ($isProofRejected)
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-red-900">Bukti Pembayaran Ditolak</p>
                                    <p class="text-xs text-red-700 mt-0.5">Silakan unggah ulang bukti pembayaran yang
                                        valid.</p>
                                </div>
                            @else
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-emerald-900">Bukti Berhasil Diunggah</p>
                                    <p class="text-xs text-emerald-700 mt-0.5">Menunggu verifikasi admin.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        @if ($isProofRejected && $latestPayment->notes)
                            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-red-700 mb-1">Catatan Admin
                                </p>
                                <p class="text-sm text-red-700">{{ $latestPayment->notes }}</p>
                            </div>
                        @endif

                        <a href="{{ route('home.tracking.proof.view', ['orderCode' => $order->order_code, 'payment' => $latestPayment->id]) }}"
                            target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Lihat Bukti
                        </a>

                        @if (in_array($latestPayment->status, ['pending', 'failed'], true))
                            <form action="{{ route('home.tracking.proof', $order->order_code) }}" method="POST"
                                enctype="multipart/form-data" x-data="{ fileName: '', preview: null }"
                                class="space-y-3 pt-3 border-t border-gray-100">
                                @csrf
                                <input type="hidden" name="replace_proof" value="1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ganti Bukti
                                    Pembayaran</p>
                                <label
                                    class="group flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-5 transition hover:border-primary-400 hover:bg-primary-50/30">
                                    <div x-show="!preview" class="text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400 group-hover:text-primary-500 transition"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-xs font-medium text-gray-600">Klik atau drag file baru</p>
                                    </div>
                                    <img x-show="preview" :src="preview"
                                        class="max-h-24 rounded-lg object-contain" alt="Preview">
                                    <p x-show="fileName" x-text="fileName"
                                        class="mt-1 text-xs font-medium text-primary-700"></p>
                                    <input type="file" name="payment_proof" accept="image/*" required class="sr-only"
                                        @change="fileName = $event.target.files[0]?.name || ''; if($event.target.files[0]) { const reader = new FileReader(); reader.onload = (e) => preview = e.target.result; reader.readAsDataURL($event.target.files[0]); }">
                                </label>
                                @error('payment_proof')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <button type="submit"
                                    class="w-full rounded-xl {{ $isProofRejected ? 'bg-red-600 hover:bg-red-700' : 'bg-primary-600 hover:bg-primary-700' }} px-6 py-3 text-sm font-bold text-white shadow-sm transition">
                                    Ganti Bukti
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @elseif($isCodMethod)
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-emerald-50 border-b border-emerald-100 px-5 py-3">
                        <h3 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Bayar di Tempat (COD)
                        </h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-600">Tidak perlu upload bukti pembayaran. Siapkan uang pas saat kurir
                            tiba di alamat Anda.</p>
                    </div>
                </div>
            @endif
        @endif
    @endif

    @php
        $hasPendingRefundRequest =
            $latestPayment && str_contains((string) ($latestPayment->notes ?? ''), '[REFUND_REQUEST_PENDING]');
        $canRequestRefund =
            $latestPayment &&
            $order->payment_status === 'paid' &&
            $latestPayment->status === 'paid' &&
            !$hasPendingRefundRequest;
    @endphp

    @if ($hasPendingRefundRequest || $canRequestRefund)
        <div class="mb-6 rounded-2xl border border-indigo-200 bg-white shadow-sm overflow-hidden">
            <div class="bg-indigo-50 border-b border-indigo-100 px-5 py-3">
                <h3 class="text-sm font-bold text-indigo-900 flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8c-1.657 0-3 1.343-3 3v1H8a2 2 0 00-2 2v1a2 2 0 002 2h8a2 2 0 002-2v-1a2 2 0 00-2-2h-1v-1c0-1.657-1.343-3-3-3z" />
                    </svg>
                    Pengajuan Refund
                </h3>
            </div>
            <div class="p-5">
                @if ($hasPendingRefundRequest)
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">
                        <p class="text-sm font-bold text-indigo-900">Pengajuan refund sudah dikirim</p>
                        <p class="mt-1 text-xs text-indigo-700">Admin sedang meninjau permintaan refund Anda. Pantau status
                            terbaru di halaman ini.</p>
                    </div>
                @elseif($canRequestRefund)
                    <p class="mb-3 text-sm text-gray-600">Silakan pilih alasan refund melalui dropdown berikut agar tim
                        admin bisa meninjau lebih cepat.</p>
                    <form action="{{ route('home.tracking.refund', $order->order_code) }}" method="POST"
                        class="grid gap-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500">Alasan Refund</label>
                            <select name="reason" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih alasan refund</option>
                                <option value="wrong_item">Barang tidak sesuai pesanan</option>
                                <option value="damaged_item">Barang rusak/cacat</option>
                                <option value="late_delivery">Pengiriman terlalu lama</option>
                                <option value="duplicate_payment">Pembayaran ganda</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500">Detail Tambahan
                                (opsional)</label>
                            <textarea name="details" rows="3" maxlength="1000"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Jelaskan ringkas kendala refund Anda."></textarea>
                        </div>
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700">Kirim
                            Pengajuan Refund</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Order Details Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Customer & Address --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3 mb-4">
                    Informasi Pengiriman</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-[11px] text-gray-500">Penerima</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-500">Kontak</p>
                        <p class="text-sm font-medium text-gray-900">{{ $order->customer_phone }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_email }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-500">Alamat</p>
                        <p class="text-sm font-medium text-gray-900 leading-relaxed">{{ $shippingAddress }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm flex flex-col h-full overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Daftar Produk</h3>
                </div>
                <div class="flex-1 divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-4 px-5 py-4">
                            @php $itemProduct = $item->product; @endphp
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                                <img src="{{ $itemProduct?->image_url ?? asset('img/hero-bg.jpg') }}"
                                    alt="{{ $item->product_name }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ number_format($item->quantity) }} × Rp
                                    {{ number_format($item->price, 0, ',', '.') }}</p>
                            </div>
                            <p class="text-sm font-bold text-gray-900 shrink-0">Rp
                                {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 px-5 py-4 bg-gray-50/50 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal ({{ number_format($order->items->sum('quantity')) }}
                            item)</span>
                        <span class="font-semibold text-gray-900">Rp
                            {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Ongkir</span>
                        <span class="font-semibold text-gray-900">Rp
                            {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                        <span class="text-base font-bold text-gray-900">Total</span>
                        <span class="text-xl font-black text-primary-600">Rp
                            {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('home') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 shadow-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Beranda
        </a>
    </div>
@endsection
