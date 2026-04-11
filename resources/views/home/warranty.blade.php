@extends('layouts.storefront')

@section('title', 'Pusat Garansi - Toko HS ELECTRIC')
@section('header_subtitle', 'Pusat Garansi')
@section('show_default_store_actions', 'off')

@section('header_actions')
    <a href="{{ route('home.transactions') }}"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
        Riwayat Transaksi
    </a>
    <a href="{{ route('home.warranty-claims.index') }}"
        class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
        Riwayat Klaim
    </a>
@endsection

@section('content')
    <x-ui.page-header title="Pusat Garansi Produk Elektronik"
        subtitle="Ajukan klaim untuk item elektronik selama masa garansi masih aktif." />

    @include('partials.flash-alerts', ['showValidationErrors' => true])

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

            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-extrabold text-primary-700">{{ $order?->order_code ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $item->product_name }}</p>
                        <p class="text-xs text-gray-500">Qty: {{ number_format((int) $item->quantity) }}</p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs text-gray-500">Garansi s/d</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ optional($item->warranty_expires_at)->format('d M Y H:i') ?? '-' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-600">
                            Sisa waktu:
                            <span class="font-semibold text-blue-700" data-warranty-countdown
                                data-expires-at="{{ optional($item->warranty_expires_at)->toIso8601String() }}">
                                Menghitung...
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    @if ($latestClaim)
                        <p class="mb-2 text-xs text-gray-600">
                            Klaim terakhir: <span
                                class="font-semibold uppercase text-gray-800">{{ $latestClaim->status }}</span>
                        </p>
                    @endif

                    @if ($warrantyActive && !$hasOpenClaim)
                        <form method="POST" action="{{ route('home.warranty-claims.store', [$order, $item]) }}"
                            class="space-y-2" enctype="multipart/form-data">
                            @csrf
                            <input type="text" name="reason"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                placeholder="Alasan klaim (contoh: kipas tidak berputar)" required>
                            <input type="file" name="damage_proof" accept="image/*,video/*"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                required>
                            <button type="submit"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-800">
                                Ajukan Klaim Garansi
                            </button>
                        </form>
                    @elseif ($hasOpenClaim)
                        <p class="text-sm font-medium text-amber-700">Klaim masih diproses admin. Tunggu update selanjutnya.
                        </p>
                    @else
                        <p class="text-sm font-medium text-gray-500">Masa garansi sudah berakhir.</p>
                    @endif
                </div>
            </article>
        @empty
            <div
                class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center text-sm text-gray-500">
                Belum ada item elektronik bergaransi yang cocok dengan filter.
            </div>
        @endforelse
    </div>

    @if ($warrantyItems->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
            {{ $warrantyItems->links() }}
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const countdownElements = document.querySelectorAll('[data-warranty-countdown]');
            if (!countdownElements.length) {
                return;
            }

            const renderCountdown = (element) => {
                const expiresAt = element.getAttribute('data-expires-at');
                if (!expiresAt) {
                    element.textContent = '-';
                    return;
                }

                const deadline = new Date(expiresAt).getTime();
                const now = Date.now();
                const diff = deadline - now;

                if (diff <= 0) {
                    element.textContent = 'Garansi berakhir';
                    element.classList.remove('text-blue-700');
                    element.classList.add('text-red-600');
                    return;
                }

                const totalSeconds = Math.floor(diff / 1000);
                const days = Math.floor(totalSeconds / 86400);
                const hours = Math.floor((totalSeconds % 86400) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                element.textContent = `${days}h ${hours}j ${minutes}m ${seconds}d`;
            };

            const tick = () => {
                countdownElements.forEach(renderCountdown);
            };

            tick();
            window.setInterval(tick, 1000);
        })();
    </script>
@endpush
