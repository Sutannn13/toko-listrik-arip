@props([
    'name' => 'Kabel Supreme NYM 3x2.5',
    'brand' => 'Supreme',
    'image' => null,
    'flashPrice' => 189000,
    'originalPrice' => 225000,
    'discountPercent' => 16,
    'stockLeft' => 12,
    'soldCount' => 48,
    'expiresLabel' => 'Berakhir 23:59',
    'href' => '#',
    'ctaLabel' => 'Beli Flash Sale',
    'unit' => 'roll',
])

@php
    $totalUnits = max(1, (int) $soldCount + (int) $stockLeft);
    $soldProgress = (int) round(((int) $soldCount / $totalUnits) * 100);
    $soldProgress = max(5, min(100, $soldProgress));
@endphp

<article {{ $attributes->class(['ui-card overflow-hidden']) }}>
    <a href="{{ $href }}" class="block aspect-square overflow-hidden bg-gray-50"
        aria-label="Lihat produk {{ $name }}">
        @if (filled($image))
            <img src="{{ $image }}" alt="{{ $name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-200 hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-primary-50 p-4">
                <svg class="h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
        @endif
    </a>

    <div class="ui-card-pad">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="ui-badge ui-badge-warning">Flash Sale</span>
            <span class="ui-badge ui-badge-danger">-{{ (int) $discountPercent }}%</span>
        </div>

        <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $brand }}</p>

        <h3 class="mb-4 text-sm font-bold leading-snug text-gray-900 sm:text-base">{{ $name }}</h3>

        <div class="mb-4 flex items-end gap-2">
            <p class="text-lg font-black text-primary-700">Rp {{ number_format((int) $flashPrice, 0, ',', '.') }}</p>
            <p class="text-xs font-medium text-gray-400 line-through">
                Rp {{ number_format((int) $originalPrice, 0, ',', '.') }}
            </p>
            <p class="text-xs font-medium text-gray-500">/{{ $unit }}</p>
        </div>

        <div class="mb-4 flex items-center justify-between gap-2 text-xs">
            <span class="font-medium text-gray-500">Sisa {{ number_format((int) $stockLeft) }}</span>
            <span class="font-semibold text-warning-700">{{ $expiresLabel }}</span>
        </div>

        <div class="mb-4 h-2 w-full overflow-hidden rounded-full bg-gray-100" role="progressbar"
            aria-label="Progress penjualan flash sale {{ $name }}" aria-valuemin="0" aria-valuemax="100"
            aria-valuenow="{{ $soldProgress }}">
            <div class="h-full rounded-full bg-primary-500 transition-all duration-200"
                style="width: {{ $soldProgress }}%"></div>
        </div>

        <p class="mb-4 text-xs font-medium text-gray-500">Terjual {{ number_format((int) $soldCount) }} unit</p>

        <a href="{{ $href }}" class="ui-btn ui-btn-primary w-full">{{ $ctaLabel }}</a>
    </div>
</article>
