@extends('layouts.storefront')

@section('title', 'Pusat Garansi - Toko HS ELECTRIC')
@section('header_subtitle', 'Pusat Garansi')
@section('show_default_store_actions', 'off')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8')

@section('content')
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Pusat Garansi</h1>
                <p class="mt-1 text-sm text-gray-500">Ajukan klaim untuk item elektronik selama masa garansi masih aktif.</p>
            </div>
            <a href="{{ route('home.cart') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Keranjang
            </a>
        </div>

        {{-- Tab Navigation --}}
        <nav class="mt-5 flex flex-wrap gap-2 border-b border-gray-200 pb-0">
            <a href="{{ route('home.transactions') }}"
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-gray-600 hover:text-primary-700 hover:border-primary-300 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Riwayat Transaksi
            </a>
            <a href="{{ route('home.warranty') }}"
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-primary-600 bg-white px-4 py-2.5 text-sm font-bold text-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Pusat Garansi
            </a>
            <a href="{{ route('home.warranty-claims.index') }}"
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-gray-600 hover:text-primary-700 hover:border-primary-300 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Riwayat Klaim
            </a>
        </nav>
    </div>

    @include('partials.flash-alerts', ['showValidationErrors' => true])

    {{-- Info Banner --}}
    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 flex gap-3">
        <svg class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-blue-800">
            Garansi klaim hanya berlaku untuk <strong>produk elektronik</strong> dengan masa garansi maksimal
            <strong>365 hari</strong> (sesuai pengaturan tiap produk) sejak tanggal pesanan.
        </p>
    </div>

    @include('home.partials.filters.warranty', ['filters' => $filters])

    <div class="space-y-4">
        @forelse ($warrantyItems as $item)
            @php
                $order = $item->order;
                $latestClaim = $item->warrantyClaims->first();
                $hasOpenClaim =
                    $latestClaim && in_array($latestClaim->status, ['submitted', 'reviewing', 'approved'], true);
                $warrantyActive = $item->warranty_expires_at && $item->warranty_expires_at->isFuture();
            @endphp

            <article class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                {{-- Header item --}}
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $warrantyActive ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-500' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $item->product_name }}</p>
                            <p class="text-xs text-gray-500">
                                Order: <span class="font-semibold text-primary-700">{{ $order?->order_code ?? '-' }}</span>
                                &bull; Qty: {{ number_format((int) $item->quantity) }}
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        @if ($warrantyActive)
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                Garansi Aktif
                            </span>
                        @else
                            <span
                                class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500">
                                Garansi Berakhir
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        {{-- Info garansi --}}
                        <div class="space-y-1.5">
                            <p class="text-xs text-gray-500">Garansi berlaku s/d</p>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ optional($item->warranty_expires_at)->format('d M Y H:i') ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-600">
                                Sisa waktu:
                                <span class="font-semibold {{ $warrantyActive ? 'text-blue-700' : 'text-red-500' }}"
                                    data-warranty-countdown
                                    data-expires-at="{{ optional($item->warranty_expires_at)->toIso8601String() }}">
                                    Menghitung...
                                </span>
                            </p>

                            @if ($latestClaim)
                                <p class="mt-2 text-xs text-gray-500">
                                    Klaim terakhir:
                                    <span
                                        class="font-semibold uppercase
                                        {{ in_array($latestClaim->status, ['approved', 'resolved'], true) ? 'text-green-700' : ($latestClaim->status === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">
                                        {{ $latestClaim->status }}
                                    </span>
                                </p>
                            @endif
                        </div>

                        {{-- Form / Status klaim --}}
                        <div class="min-w-0 flex-1 max-w-sm">
                            @if ($warrantyActive && !$hasOpenClaim)
                                <form method="POST" action="{{ route('home.warranty-claims.store', [$order, $item]) }}"
                                    class="space-y-2.5" enctype="multipart/form-data">
                                    @csrf
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Alasan Klaim</label>
                                        <input type="text" name="reason"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                            placeholder="Contoh: kipas tidak berputar" required>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Upload Bukti
                                            Kerusakan</label>
                                        <input type="file" name="damage_proof" accept="image/*,video/*"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                            required>
                                    </div>
                                    <button type="submit"
                                        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-primary-500/20 transition hover:bg-primary-700">
                                        Ajukan Klaim Garansi
                                    </button>
                                </form>
                            @elseif ($hasOpenClaim)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-semibold text-amber-800">Klaim Sedang Diproses</p>
                                            <p class="mt-0.5 text-xs text-amber-700">Tim admin sedang meninjau klaim Anda.
                                                Pantau di tab Riwayat Klaim.</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('home.warranty-claims.index') }}"
                                        class="mt-3 inline-flex w-full items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 transition">
                                        Lihat Status Klaim →
                                    </a>
                                </div>
                            @else
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center">
                                    <p class="text-sm font-medium text-gray-500">Masa garansi sudah berakhir.</p>
                                    <p class="mt-1 text-xs text-gray-400">Garansi tidak dapat diperpanjang.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                    <svg class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">Belum ada item bergaransi</p>
                <p class="mt-1 text-xs text-gray-400">Item elektronik bergaransi Anda akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    @if ($warrantyItems->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            {{ $warrantyItems->links() }}
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const countdownElements = document.querySelectorAll('[data-warranty-countdown]');
            if (!countdownElements.length) return;

            const renderCountdown = (element) => {
                const expiresAt = element.getAttribute('data-expires-at');
                if (!expiresAt) {
                    element.textContent = '-';
                    return;
                }

                const diff = new Date(expiresAt).getTime() - Date.now();
                if (diff <= 0) {
                    element.textContent = 'Garansi berakhir';
                    element.classList.remove('text-blue-700');
                    element.classList.add('text-red-500');
                    return;
                }

                const s = Math.floor(diff / 1000);
                const d = Math.floor(s / 86400);
                const h = Math.floor((s % 86400) / 3600);
                const m = Math.floor((s % 3600) / 60);
                const sec = s % 60;
                element.textContent = `${d}h ${h}j ${m}m ${sec}d`;
            };

            const tick = () => countdownElements.forEach(renderCountdown);
            tick();
            window.setInterval(tick, 1000);
        })();
    </script>
@endpush
