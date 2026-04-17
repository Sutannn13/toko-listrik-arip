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
            @php
                $latestPayment = $order->payments->sortByDesc('id')->first();
                $requiresProofVerification =
                    $latestPayment &&
                    filled($latestPayment->proof_url) &&
                    in_array($latestPayment->status, ['pending', 'failed'], true) &&
                    $order->status !== 'cancelled';
            @endphp
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

                @if ($requiresProofVerification)
                    <input type="hidden" name="payment_status"
                        value="{{ old('payment_status', $order->payment_status) }}">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                        <p class="text-xs font-semibold text-amber-700">Payment Status (Dikelola via ACC/Tolak)</p>
                        <p class="mt-1 text-sm font-bold uppercase text-amber-900">{{ $order->payment_status }}</p>
                        <p class="mt-1 text-[11px] text-amber-700">
                            Untuk menghindari status bentrok, verifikasi pembayaran dilakukan dari panel
                            <strong>Verifikasi Bukti Pembayaran</strong> pada bagian Payment History.
                        </p>
                    </div>
                @else
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
                @endif

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Nomor Resi Pengiriman</label>
                    <input type="text" name="tracking_number"
                        value="{{ old('tracking_number', $order->tracking_number) }}"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Masukkan nomor resi jika sudah dikirim">
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Simpan Status Order
                </button>
            </form>
        </div>
    </div>

    @php
        $shippingAddress = $order->address;
        $locationLine = $shippingAddress
            ? implode(
                ', ',
                array_filter([$shippingAddress->city, $shippingAddress->province, $shippingAddress->postal_code]),
            )
            : null;
    @endphp

    <div class="mb-6 rounded-lg bg-white p-5 shadow">
        <h4 class="mb-4 text-base font-bold text-gray-800">Alamat Pengiriman</h4>

        <div class="overflow-hidden rounded-lg border">
            <table class="w-full border-collapse text-left text-sm">
                <tbody>
                    <tr class="border-b bg-gray-50">
                        <th class="w-44 p-3 font-semibold text-gray-600">Label Alamat</th>
                        <td class="p-3 text-gray-800">{{ $shippingAddress?->label ?: '-' }}</td>
                    </tr>
                    <tr class="border-b">
                        <th class="w-44 p-3 font-semibold text-gray-600">Penerima</th>
                        <td class="p-3 text-gray-800">{{ $shippingAddress?->recipient_name ?: $order->customer_name }}</td>
                    </tr>
                    <tr class="border-b bg-gray-50">
                        <th class="w-44 p-3 font-semibold text-gray-600">Telepon Tujuan</th>
                        <td class="p-3 text-gray-800">{{ $shippingAddress?->phone ?: ($order->customer_phone ?: '-') }}
                        </td>
                    </tr>
                    <tr class="border-b">
                        <th class="w-44 p-3 font-semibold text-gray-600">Alamat Jalan</th>
                        <td class="p-3 text-gray-800">{{ $shippingAddress?->address_line ?: '-' }}</td>
                    </tr>
                    <tr class="border-b bg-gray-50">
                        <th class="w-44 p-3 font-semibold text-gray-600">Kota / Provinsi</th>
                        <td class="p-3 text-gray-800">{{ $locationLine ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th class="w-44 p-3 font-semibold text-gray-600">Catatan Alamat</th>
                        <td class="p-3 text-gray-800">{{ $shippingAddress?->notes ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if (!$shippingAddress)
            <p class="mt-3 text-xs text-amber-700">
                Data alamat relasional belum tersedia untuk pesanan ini. Periksa catatan order jika ada snapshot alamat.
            </p>
        @endif

        @if ($order->notes)
            <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Catatan Order</p>
                <p class="mt-1 text-xs text-gray-700">{{ $order->notes }}</p>
            </div>
        @endif
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
                        $warrantyMinDate = $warrantyStartAt->copy()->addDay()->toDateString();
                    @endphp

                    @foreach ($order->items as $item)
                        @php
                            $isWarrantyEligible =
                                (int) $item->warranty_days > 0 || !is_null($item->warranty_expires_at);
                            $productWarrantyDays = (int) ($item->product?->warranty_days_for_claim ?? 0);
                            $itemMaxWarrantyDays = max(
                                1,
                                min(365, $productWarrantyDays > 0 ? $productWarrantyDays : (int) $item->warranty_days),
                            );
                            $itemWarrantyMaxDate = $warrantyStartAt
                                ->copy()
                                ->addDays($itemMaxWarrantyDays)
                                ->toDateString();
                            $defaultWarrantyDate =
                                optional($item->warranty_expires_at)->toDateString() ??
                                $warrantyStartAt->copy()->addDays($itemMaxWarrantyDays)->toDateString();
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

                                @if ($isWarrantyEligible)
                                    <form action="{{ route('admin.orders.items.update-warranty', [$order, $item]) }}"
                                        method="POST" class="mt-2 space-y-2">
                                        @csrf
                                        @method('PATCH')

                                        <label
                                            class="block text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                            Atur Tanggal Garansi (1-{{ $itemMaxWarrantyDays }} hari)
                                        </label>
                                        <input type="date" name="warranty_expires_at"
                                            value="{{ old('warranty_expires_at', $defaultWarrantyDate) }}"
                                            min="{{ $warrantyMinDate }}" max="{{ $itemWarrantyMaxDate }}"
                                            class="w-full rounded border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500"
                                            required>
                                        <button type="submit"
                                            class="rounded bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white transition hover:bg-gray-800">
                                            Simpan Garansi
                                        </button>
                                    </form>
                                @else
                                    <p class="mt-2 rounded bg-gray-100 px-2 py-1 text-[11px] font-medium text-gray-600">
                                        Non-elektronik: tidak ada garansi klaim.
                                    </p>
                                @endif
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

            @php
                $latestPaymentId = optional($latestPayment)->id;
            @endphp

            @forelse ($order->payments as $payment)
                <div class="mb-3 rounded-lg border p-3">
                    <p class="text-sm font-semibold text-gray-800">{{ $payment->payment_code }}
                        ({{ strtoupper($payment->method) }})
                    </p>
                    <p class="text-sm text-gray-600">Amount: Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                    <p class="text-xs uppercase text-gray-500 mb-2">Status: {{ $payment->status }}</p>
                    @if ($payment->notes)
                        <p class="mb-2 rounded bg-gray-50 px-2 py-1 text-xs text-gray-600">{{ $payment->notes }}</p>
                    @endif
                    @if ($payment->proof_url)
                        <div class="mt-2 border-t pt-2">
                            <p class="text-xs font-bold text-gray-700 mb-1">Bukti Transfer:</p>
                            <a href="{{ Storage::url($payment->proof_url) }}" target="_blank" class="block w-fit">
                                <img src="{{ Storage::url($payment->proof_url) }}" alt="Bukti Transfer"
                                    class="w-24 h-auto rounded border shadow-sm hover:opacity-80 transition">
                            </a>

                            @if (
                                (int) $payment->id === (int) $latestPaymentId &&
                                    in_array($payment->status, ['pending', 'failed'], true) &&
                                    $order->status !== 'cancelled')
                                <div class="mt-3 grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                        Verifikasi Bukti Pembayaran (Terbaru)
                                    </p>

                                    <form action="{{ route('admin.orders.payments.approve', [$order, $payment]) }}"
                                        method="POST" class="grid gap-2 sm:grid-cols-[1fr,auto]">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="admin_notes" value="{{ old('admin_notes') }}"
                                            placeholder="Catatan opsional untuk pelanggan"
                                            class="w-full rounded border-gray-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
                                        <button type="submit"
                                            class="rounded bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                            ACC Bukti
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.orders.payments.reject', [$order, $payment]) }}"
                                        method="POST" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="admin_notes" rows="2" required placeholder="Alasan penolakan (wajib diisi)"
                                            class="w-full rounded border-gray-300 text-xs focus:border-red-500 focus:ring-red-500">{{ old('admin_notes') }}</textarea>
                                        <button type="submit"
                                            class="rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                            Tolak Bukti
                                        </button>
                                    </form>

                                    @error('admin_notes')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
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
