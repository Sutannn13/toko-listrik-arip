@php
    $latestPayment = $order->payments->first();
@endphp

<article class="ui-card ui-card-pad">
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

    <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
        <p class="text-gray-600">
            Item: <span
                class="font-semibold text-gray-900">{{ number_format((int) $order->items->sum('quantity')) }}</span>
        </p>
        <p class="text-gray-600">
            Total: <span class="font-semibold text-gray-900">Rp
                {{ number_format((int) $order->total_amount, 0, ',', '.') }}</span>
        </p>
        <p class="text-gray-600">
            Payment Ref: <span class="font-semibold text-gray-900">{{ $latestPayment?->payment_code ?? '-' }}</span>
        </p>
    </div>

    <div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">
        Produk: {{ $order->items->pluck('product_name')->filter()->join(', ') ?: '-' }}
    </div>
</article>
