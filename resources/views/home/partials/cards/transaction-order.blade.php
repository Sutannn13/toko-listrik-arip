@php
    $latestPayment = $order->payments->first();
    $firstItem = $order->items->first();
    $firstProduct = $firstItem?->product;
    $productImage = $firstProduct?->image_url ?? asset('img/hero-bg.jpg');
    $itemCount = $order->items->count();
    $totalQty = (int) $order->items->sum('quantity');

    $statusMap = [
        'pending'    => ['label' => 'Menunggu', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'processing' => ['label' => 'Diproses', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
        'shipped'    => ['label' => 'Dikirim', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
        'completed'  => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'cancelled'  => ['label' => 'Dibatalkan', 'class' => 'bg-red-50 text-red-700 border-red-200'],
    ];
    $paymentMap = [
        'pending' => ['label' => 'Belum Bayar', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'paid'    => ['label' => 'Lunas', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'failed'  => ['label' => 'Gagal', 'class' => 'bg-red-50 text-red-700 border-red-200'],
    ];

    $statusInfo = $statusMap[$order->status] ?? $statusMap['pending'];
    $paymentInfo = $paymentMap[$order->payment_status] ?? $paymentMap['pending'];

    $methodLabels = [
        'bank_transfer' => 'Transfer Bank',
        'ewallet'       => 'E-Wallet',
        'cod'           => 'COD',
        'bayargg'       => 'Bayar.gg',
        'dummy'         => 'Transfer',
    ];
    $methodLabel = $methodLabels[$latestPayment?->method ?? ''] ?? 'Lainnya';

    // Progress step
    $currentStep = 0;
    if ($order->status === 'completed') $currentStep = 4;
    elseif ($order->status === 'shipped') $currentStep = 3;
    elseif ($order->status === 'processing' || $order->payment_status === 'paid') $currentStep = 2;
    elseif ($order->payment_status === 'pending' && $order->status === 'pending') $currentStep = 1;

    $isCancelled = $order->status === 'cancelled';
    $needsPayment = !$isCancelled && $order->payment_status !== 'paid' && in_array($latestPayment?->method, ['bank_transfer', 'ewallet', 'dummy']) && !$latestPayment?->proof_url;
@endphp

<article class="group rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md hover:border-gray-300">
    {{-- Header Row --}}
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate">{{ $order->order_code }}</p>
                <p class="text-[11px] text-gray-500">{{ optional($order->placed_at)->format('d M Y, H:i') ?? $order->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $statusInfo['class'] }}">
                {{ $statusInfo['label'] }}
            </span>
            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $paymentInfo['class'] }}">
                {{ $paymentInfo['label'] }}
            </span>
        </div>
    </div>

    {{-- Body: Product Info --}}
    <div class="flex gap-4 px-5 py-4">
        {{-- Thumbnail --}}
        <div class="shrink-0">
            <div class="relative h-20 w-20 overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                <img src="{{ $productImage }}" alt="{{ $firstItem?->product_name ?? 'Produk' }}"
                     class="h-full w-full object-cover" loading="lazy">
                @if ($itemCount > 1)
                    <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/50 to-transparent">
                        <span class="w-full py-1 text-center text-[10px] font-bold text-white">+{{ $itemCount - 1 }} lainnya</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Details --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ $firstItem?->product_name ?? '-' }}</p>
            @if ($itemCount > 1)
                <p class="mt-0.5 text-xs text-gray-500 truncate">dan {{ $itemCount - 1 }} produk lainnya</p>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                <span>{{ $totalQty }} barang</span>
                <span>•</span>
                <span>{{ $methodLabel }}</span>
            </div>
            <p class="mt-2 text-base font-bold text-gray-900">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Progress bar (only for active orders) --}}
    @unless ($isCancelled)
        <div class="border-t border-gray-100 px-5 py-3">
            <div class="flex items-center gap-1">
                @php
                    $steps = ['Pesan', 'Bayar', 'Proses', 'Kirim'];
                @endphp
                @foreach ($steps as $i => $stepLabel)
                    @php $stepNum = $i + 1; $isActive = $stepNum <= $currentStep; @endphp
                    <div class="flex-1">
                        <div class="h-1.5 rounded-full {{ $isActive ? 'bg-primary-500' : 'bg-gray-200' }} transition-colors"></div>
                    </div>
                    @unless ($loop->last)
                        <div class="w-0.5"></div>
                    @endunless
                @endforeach
            </div>
            <div class="mt-1.5 flex justify-between text-[10px] font-medium {{ $isCancelled ? 'text-red-500' : 'text-gray-400' }}">
                @foreach ($steps as $i => $stepLabel)
                    <span class="{{ ($i + 1) <= $currentStep ? 'text-primary-600 font-semibold' : '' }}">{{ $stepLabel }}</span>
                @endforeach
            </div>
        </div>
    @else
        <div class="border-t border-gray-100 px-5 py-3">
            <div class="flex items-center gap-2 text-xs text-red-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <span class="font-semibold">Pesanan ini telah dibatalkan</span>
            </div>
        </div>
    @endunless

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-3">
        @if ($needsPayment)
            <a href="{{ route('home.tracking.show', $order->order_code) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Upload Bukti Bayar
            </a>
        @endif
        <a href="{{ route('home.tracking.show', $order->order_code) }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
            Lihat Detail
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</article>
