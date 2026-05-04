@extends('layouts.admin')

@section('header', 'Pipeline Pesanan')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Daftar Pesanan</h3>
            <p class="text-sm text-gray-600">Kelola status pesanan, payment, dan progres order user.</p>
        </div>

        <a href="{{ route('admin.warranty-claims.index') }}"
            class="rounded-lg border border-cyan-300 bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100">
            Kelola Klaim Garansi
        </a>
    </div>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-6 rounded-lg bg-white p-4 shadow">
        <div class="grid gap-3 md:grid-cols-6">
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Cari Kode / Customer / Payment</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="ORD..., nama, email, PAY...">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Status Order</label>
                <select name="status"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Status Payment</label>
                <select name="payment_status"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach (['pending', 'paid', 'failed', 'refunded'] as $paymentStatus)
                        <option value="{{ $paymentStatus }}" @selected(($filters['payment_status'] ?? '') === $paymentStatus)>
                            {{ ucfirst($paymentStatus) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Bukti Pembayaran</label>
                <select name="proof"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all" @selected(($filters['proof'] ?? 'all') === 'all')>Semua</option>
                    <option value="uploaded" @selected(($filters['proof'] ?? '') === 'uploaded')>Sudah Upload</option>
                    <option value="missing" @selected(($filters['proof'] ?? '') === 'missing')>Belum Upload</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                Terapkan Filter
            </button>
            <a href="{{ route('admin.orders.index') }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Reset
            </a>
            <a href="{{ route('admin.orders.index', array_filter(array_merge($filters, ['refund' => 'pending']))) }}"
                class="rounded-lg border {{ ($filters['refund'] ?? '') === 'pending' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-indigo-200 bg-white text-indigo-600' }} px-4 py-2 text-sm font-semibold transition hover:bg-indigo-50">
                Refund Pending
            </a>
        </div>
    </form>

    <p class="mb-2 text-xs font-medium text-gray-500 sm:hidden">Geser tabel ke samping untuk melihat kolom detail dan aksi.
    </p>
    <div class="rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] border-collapse text-left">
                <thead>
                    <tr class="bg-gray-800 text-sm uppercase tracking-wider text-white">
                        <th class="p-4 font-medium">Kode</th>
                        <th class="p-4 font-medium">Customer</th>
                        <th class="p-4 font-medium">Alamat</th>
                        <th class="p-4 font-medium">Total</th>
                        <th class="p-4 font-medium">Payment</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 font-medium">Tanggal</th>
                        <th class="p-4 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse($orders as $order)
                        @php
                            $latestPayment = $order->latestPayment;
                            $hasLatestProof = filled($latestPayment?->proof_url);
                        @endphp
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="p-4 font-semibold text-gray-900">{{ $order->order_code }}</td>
                            <td class="p-4">
                                <p class="font-semibold">{{ $order->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->customer_email }}</p>
                            </td>
                            <td class="p-4 text-sm text-gray-600">
                                @if ($order->address)
                                    <p class="font-semibold text-gray-800">{{ $order->address->city ?: '-' }}
                                        {{ $order->address->province ? ', ' . $order->address->province : '' }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->address->address_line ?: '-' }}</p>
                                @else
                                    <p class="text-xs text-gray-400 italic">Alamat belum tersedia</p>
                                @endif
                            </td>
                            <td class="p-4 font-bold text-cyan-700">Rp
                                {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="p-4">
                                @php
                                    $latestPayment = $order->latestPayment;
                                    $hasLatestProof = filled($latestPayment?->proof_url);
                                    $hasPendingRefund = $latestPayment && str_contains((string) ($latestPayment->notes ?? ''), '[REFUND_REQUEST_PENDING]');
                                @endphp
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-bold uppercase {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($order->payment_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ $order->payment_status }}
                                </span>
                                @if ($hasPendingRefund)
                                    <span class="mt-1 inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase text-indigo-700">
                                        REFUND PENDING
                                    </span>
                                @endif
                                <p
                                    class="mt-1 text-[11px] font-semibold {{ $hasLatestProof ? 'text-cyan-700' : 'text-gray-500' }}">
                                    Proof terbaru: {{ $hasLatestProof ? 'uploaded' : 'missing' }}
                                </p>
                            </td>
                            <td class="p-4">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-bold uppercase {{ $order->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($order->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="p-4 text-sm text-gray-500">
                                {{ optional($order->placed_at)->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                    class="font-semibold text-blue-600 transition hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-500 italic">Belum ada pesanan tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($orders->hasPages())
        <div class="mt-4 rounded-lg bg-white p-4 shadow">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
