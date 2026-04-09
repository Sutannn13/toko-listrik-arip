@extends('layouts.admin')

@section('header', 'Detail Pesanan')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-800">{{ $order->order_code }}</h3>
            <p class="text-sm text-gray-600">Detail item, payment, dan status pipeline pesanan.</p>
        </div>

        <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">
            &larr; Kembali ke Daftar Pesanan
        </a>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 border-b pb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Informasi Customer</h4>
            <p class="text-base font-semibold text-gray-900">{{ $order->customer_name }}</p>
            <p class="text-sm text-gray-600">{{ $order->customer_email }}</p>
            <p class="mt-2 text-sm text-gray-500">{{ $order->customer_phone ?: '-' }}</p>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 border-b pb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Ringkasan Nominal</h4>
            <p class="text-sm text-gray-600">Subtotal: <span class="font-semibold">Rp
                    {{ number_format($order->subtotal, 0, ',', '.') }}</span></p>
            <p class="text-sm text-gray-600">Ongkir: <span class="font-semibold">Rp
                    {{ number_format($order->shipping_cost, 0, ',', '.') }}</span></p>
            <p class="text-sm text-gray-600">Diskon: <span class="font-semibold">Rp
                    {{ number_format($order->discount_amount, 0, ',', '.') }}</span></p>
            <p class="mt-2 text-base font-bold text-cyan-700">Total: Rp
                {{ number_format($order->total_amount, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 border-b pb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Status Pesanan</h4>
            <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="space-y-3">
                @csrf
                @method('PATCH')

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Order Status</label>
                    <select name="status"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Payment Status</label>
                    <select name="payment_status"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected($order->payment_status === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Nomor Resi Pengiriman</label>
                    <input type="text" name="tracking_number"
                        value="{{ old('tracking_number', $order->tracking_number) }}"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Masukkan nomor resi jika sudah dikirim">
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Simpan Status
                </button>
            </form>
        </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-5 shadow">
        <h4 class="mb-4 text-base font-bold text-gray-800">Item Pesanan</h4>

        <div class="overflow-hidden rounded-lg border">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-100 text-xs uppercase tracking-wide text-gray-600">
                        <th class="p-3 font-medium">Produk</th>
                        <th class="p-3 font-medium">Qty</th>
                        <th class="p-3 font-medium">Harga</th>
                        <th class="p-3 font-medium">Subtotal</th>
                        <th class="p-3 font-medium">Garansi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $warrantyStartAt = (
                            $order->completed_at ??
                            ($order->placed_at ?? ($order->created_at ?? now()))
                        )
                            ->copy()
                            ->startOfDay();
                        $warrantyMinDate = $warrantyStartAt->copy()->addDays(7)->toDateString();
                        $warrantyMaxDate = $warrantyStartAt->copy()->addDays(30)->toDateString();
                    @endphp

                    @foreach ($order->items as $item)
                        @php
                            $defaultWarrantyDate =
                                optional($item->warranty_expires_at)->toDateString() ??
                                $warrantyStartAt
                                    ->copy()
                                    ->addDays(max(7, min(30, (int) $item->warranty_days)))
                                    ->toDateString();
                        @endphp
                        <tr class="border-t">
                            <td class="p-3">
                                <p class="font-semibold text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs uppercase text-gray-500">{{ $item->unit }}</p>
                            </td>
                            <td class="p-3">{{ number_format($item->quantity) }}</td>
                            <td class="p-3">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="p-3 font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            <td class="p-3 text-xs text-gray-600">
                                {{ $item->warranty_days }} hari<br>
                                Exp: {{ optional($item->warranty_expires_at)->format('d M Y') ?? '-' }}

                                <form action="{{ route('admin.orders.items.update-warranty', [$order, $item]) }}"
                                    method="POST" class="mt-2 space-y-2">
                                    @csrf
                                    @method('PATCH')

                                    <label class="block text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Atur Tanggal Garansi (7-30 hari)
                                    </label>
                                    <input type="date" name="warranty_expires_at"
                                        value="{{ old('warranty_expires_at', $defaultWarrantyDate) }}"
                                        min="{{ $warrantyMinDate }}" max="{{ $warrantyMaxDate }}"
                                        class="w-full rounded border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <button type="submit"
                                        class="rounded bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white transition hover:bg-gray-800">
                                        Simpan Garansi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 text-base font-bold text-gray-800">Payment History</h4>

            @forelse ($order->payments as $payment)
                <div class="mb-3 rounded-lg border p-3">
                    <p class="text-sm font-semibold text-gray-800">{{ $payment->payment_code }}
                        ({{ strtoupper($payment->method) }})
                    </p>
                    <p class="text-sm text-gray-600">Amount: Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                    <p class="text-xs uppercase text-gray-500 mb-2">Status: {{ $payment->status }}</p>
                    @if ($payment->proof_url)
                        <div class="mt-2 border-t pt-2">
                            <p class="text-xs font-bold text-gray-700 mb-1">Bukti Transfer:</p>
                            <a href="{{ Storage::url($payment->proof_url) }}" target="_blank" class="block w-fit">
                                <img src="{{ Storage::url($payment->proof_url) }}" alt="Bukti Transfer"
                                    class="w-24 h-auto rounded border shadow-sm hover:opacity-80 transition">
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 italic">Belum ada payment record.</p>
            @endforelse
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 text-base font-bold text-gray-800">Klaim Garansi Terkait</h4>

            @forelse ($order->warrantyClaims as $claim)
                <div class="mb-3 rounded-lg border p-3">
                    <a href="{{ route('admin.warranty-claims.show', $claim) }}"
                        class="text-sm font-semibold text-cyan-700 hover:underline">
                        {{ $claim->claim_code }}
                    </a>
                    <p class="text-sm text-gray-700">{{ $claim->reason }}</p>
                    <p class="text-xs uppercase text-gray-500">Status: {{ $claim->status }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 italic">Belum ada klaim garansi untuk pesanan ini.</p>
            @endforelse
        </div>
    </div>
@endsection
