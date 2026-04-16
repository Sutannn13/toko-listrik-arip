@extends('layouts.storefront')

@section('title', 'Lacak Pesanan - ' . \App\Models\Setting::get('store_name', 'Toko Listrik'))
@section('header_subtitle', 'Lacak Pesanan')
@section('show_default_store_actions', 'off')
@section('main_container_class', 'mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8 flex-1')
@section('footer')
    @include('layouts.partials.flowbite-footer')
@endsection

@section('background')
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-32 left-0 h-96 w-96 rounded-full bg-primary-100/40 blur-3xl"></div>
        <div class="absolute top-1/4 -right-16 h-80 w-80 rounded-full bg-primary-200/30 blur-3xl"></div>
    </div>
@endsection

@section('header_actions')
    <a href="{{ route('home') }}"
        class="hidden lg:inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:border-primary-500 hover:text-primary-600 hover:bg-gray-50">
        Katalog Produk
    </a>
@endsection

@section('content')

    <div class="mb-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Lacak Pesanan</h1>
                <p class="mt-1 text-sm text-gray-500">Semua pesanan akun Anda tampil otomatis, tanpa input kode order.
                </p>
            </div>
            <a href="{{ route('home.transactions') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Riwayat Transaksi
            </a>
        </div>

        <form method="GET" action="{{ route('home.tracking') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr,180px,auto]">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                placeholder="Cari kode order atau nama produk">

            <select name="status"
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                @foreach (['all' => 'Semua Status', 'pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary-700">
                Filter
            </button>
        </form>
    </div>

    @include('home.partials.flash-alerts')

    <div class="space-y-4">
        @forelse ($orders as $order)
            @php
                $latestPayment = $order->payments->first();
                $isCancelled = $order->status === 'cancelled';

                $currentStep = 1;
                if ($order->status === 'completed') {
                    $currentStep = 4;
                } elseif ($order->status === 'shipped') {
                    $currentStep = 3;
                } elseif ($order->status === 'processing' || $order->payment_status === 'paid') {
                    $currentStep = 2;
                }

                $steps = [
                    ['label' => 'Menunggu Bayar', 'hint' => 'Transfer/COD'],
                    ['label' => 'Diproses', 'hint' => 'Admin menyiapkan'],
                    ['label' => 'Dikirim', 'hint' => $order->tracking_number ?: 'Menunggu resi'],
                    ['label' => 'Selesai', 'hint' => 'Pesanan tuntas'],
                ];
            @endphp

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-extrabold text-primary-700">{{ $order->order_code }}</p>
                        <p class="text-xs text-gray-500">
                            {{ optional($order->placed_at)->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <span
                            class="ui-badge uppercase {{ $order->status === 'completed' ? 'ui-badge-success' : ($order->status === 'cancelled' ? 'ui-badge-danger' : 'ui-badge-warning') }}">
                            {{ $order->status }}
                        </span>
                        <span
                            class="ui-badge uppercase {{ $order->payment_status === 'paid' ? 'ui-badge-success' : ($order->payment_status === 'failed' ? 'ui-badge-danger' : 'ui-badge-warning') }}">
                            {{ $order->payment_status }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <p class="text-gray-600">Item: <span
                            class="font-semibold text-gray-900">{{ number_format((int) $order->items->sum('quantity')) }}</span>
                    </p>
                    <p class="text-gray-600">Total: <span class="font-semibold text-gray-900">Rp
                            {{ number_format((int) $order->total_amount, 0, ',', '.') }}</span></p>
                    <p class="text-gray-600">Payment Ref: <span
                            class="font-semibold text-gray-900">{{ $latestPayment?->payment_code ?? '-' }}</span></p>
                </div>

                @if ($isCancelled)
                    <div
                        class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">
                        Pesanan dibatalkan.
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($steps as $index => $step)
                            @php
                                $stepNumber = $index + 1;
                                $isReached = $stepNumber <= $currentStep;
                            @endphp
                            <div
                                class="rounded-lg border px-3 py-2 {{ $isReached ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50' }}">
                                <p
                                    class="text-[11px] font-bold uppercase tracking-wide {{ $isReached ? 'text-emerald-700' : 'text-gray-500' }}">
                                    {{ $step['label'] }}
                                </p>
                                <p class="mt-1 text-xs {{ $isReached ? 'text-emerald-700' : 'text-gray-500' }}">
                                    {{ $step['hint'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">Produk:
                        {{ $order->items->pluck('product_name')->filter()->join(', ') ?: '-' }}</p>

                    <a href="{{ route('home.tracking.show', $order->order_code) }}"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-700">
                        Lihat Detail Tracking
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <p class="text-sm font-semibold text-gray-700">Belum ada pesanan untuk dilacak.</p>
                <p class="mt-1 text-xs text-gray-500">Checkout pertama Anda akan muncul otomatis di halaman ini.</p>
                <a href="{{ route('home') }}"
                    class="mt-5 inline-flex rounded-xl bg-primary-600 px-5 py-2 text-sm font-bold text-white shadow-md shadow-primary-500/20 hover:bg-primary-700 transition">
                    Mulai Belanja
                </a>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
