@extends('layouts.admin')

@section('header', 'Manajemen Klaim Garansi')

@section('content')
    <x-ui.page-header title="Daftar Klaim Garansi"
        subtitle="Pantau SLA 2x24 jam, status klaim, dan jejak audit aktivitas admin.">
        <x-slot:actions>
            <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-soft">
                Lihat Pipeline Pesanan
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.flash-alerts', ['showValidationErrors' => true])

    <form method="GET" action="{{ route('admin.warranty-claims.index') }}" class="mb-6 rounded-lg bg-white p-4 shadow">
        <div class="grid gap-3 md:grid-cols-7">
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Cari Kode / Order / Produk / User</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="WRN..., ORD..., produk, user">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Status Klaim</label>
                <select name="status"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach (['submitted', 'reviewing', 'approved', 'rejected', 'resolved'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Status Payment Order</label>
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
                <label class="mb-1 block text-xs font-semibold text-gray-500">Kategori Produk</label>
                <select name="electronic"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all" @selected(($filters['electronic'] ?? 'all') === 'all')>Semua</option>
                    <option value="electronic" @selected(($filters['electronic'] ?? '') === 'electronic')>Elektronik</option>
                    <option value="non_electronic" @selected(($filters['electronic'] ?? '') === 'non_electronic')>Non-elektronik</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">Umur Klaim</label>
                <select name="age_bucket"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all" @selected(($filters['age_bucket'] ?? 'all') === 'all')>Semua</option>
                    <option value="0_2d" @selected(($filters['age_bucket'] ?? '') === '0_2d')>0-2 hari</option>
                    <option value="3_7d" @selected(($filters['age_bucket'] ?? '') === '3_7d')>3-7 hari</option>
                    <option value="gt_7d" @selected(($filters['age_bucket'] ?? '') === 'gt_7d')>> 7 hari</option>
                    <option value="sla_overdue" @selected(($filters['age_bucket'] ?? '') === 'sla_overdue')>Lewat SLA 2x24</option>
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
            <a href="{{ route('admin.warranty-claims.index') }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-gray-800 text-sm uppercase tracking-wider text-white">
                    <th class="p-4 font-medium">Kode Klaim</th>
                    <th class="p-4 font-medium">Order</th>
                    <th class="p-4 font-medium">Kategori</th>
                    <th class="p-4 font-medium">Status & SLA</th>
                    <th class="p-4 font-medium">Update Terakhir</th>
                    <th class="p-4 font-medium">Aksi Admin</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse ($claims as $claim)
                    <tr class="border-b border-gray-200 align-top hover:bg-gray-50">
                        <td class="p-4">
                            <p class="font-semibold text-cyan-700">{{ $claim->claim_code }}</p>
                            <p class="text-xs text-gray-500">
                                {{ optional($claim->requested_at)->format('d M Y H:i') ?: $claim->created_at->format('d M Y H:i') }}
                            </p>
                        </td>
                        <td class="p-4">
                            @if ($claim->order)
                                <a href="{{ route('admin.orders.show', $claim->order) }}"
                                    class="font-semibold text-blue-600 hover:underline">
                                    {{ $claim->order->order_code }}
                                </a>
                                <p class="mt-1 text-[11px] uppercase text-gray-500">Payment:
                                    {{ $claim->order->payment_status }}</p>
                            @else
                                <p class="text-sm text-gray-500">Order tidak ditemukan</p>
                            @endif

                            <p class="mt-2 text-xs text-gray-600">
                                {{ $claim->orderItem->product_name ?? 'Item tidak ditemukan' }}
                            </p>
                            <p class="text-[11px] text-gray-500">Qty: {{ $claim->orderItem->quantity ?? '-' }}</p>
                        </td>

                        <td class="p-4">
                            @if ($claim->orderItem?->product?->is_electronic)
                                <span class="inline-flex rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                                    Elektronik
                                </span>
                            @else
                                <span class="inline-flex rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                    Non-elektronik
                                </span>
                            @endif

                            @php
                                $ageHours = (int) ($claim->claim_age_hours ?? 0);
                                $ageDays = intdiv($ageHours, 24);
                                $ageRemainder = $ageHours % 24;
                            @endphp
                            <p class="mt-2 text-[11px] text-gray-500">
                                Umur klaim: {{ $ageDays }}h {{ $ageRemainder }}j
                            </p>
                        </td>

                        <td class="p-4">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-bold uppercase {{ in_array($claim->status, ['approved', 'resolved'], true) ? 'bg-emerald-100 text-emerald-700' : ($claim->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $claim->status }}
                            </span>

                            <p class="mt-2 text-xs text-gray-600">
                                SLA deadline:
                                {{ optional($claim->sla_deadline)->format('d M Y H:i') ?? '-' }}
                            </p>

                            @if ($claim->is_sla_overdue)
                                <p
                                    class="mt-1 inline-flex rounded bg-red-100 px-2 py-1 text-[11px] font-semibold text-red-700">
                                    Melewati SLA 2x24 jam
                                </p>
                            @else
                                <p
                                    class="mt-1 inline-flex rounded bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700">
                                    Dalam SLA
                                </p>
                            @endif

                            <p class="mt-2 text-xs text-gray-600 line-clamp-3">{{ $claim->reason }}</p>
                        </td>

                        <td class="p-4">
                            @php $latestActivity = $claim->activities->first(); @endphp
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $latestActivity?->created_at?->format('d M Y H:i') ?? $claim->updated_at->format('d M Y H:i') }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ strtoupper($latestActivity?->action ?? 'status') }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Oleh: {{ $latestActivity?->actor_name ?? 'System' }}
                            </p>
                        </td>

                        <td class="p-4">
                            <a href="{{ route('admin.warranty-claims.show', $claim) }}"
                                class="mb-2 inline-flex text-xs font-semibold text-blue-600 hover:underline">
                                Lihat Detail Timeline
                            </a>

                            <form action="{{ route('admin.warranty-claims.update-status', $claim) }}" method="POST"
                                class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <select name="status"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach (['submitted', 'reviewing', 'approved', 'rejected', 'resolved'] as $status)
                                        <option value="{{ $status }}" @selected($claim->status === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                <textarea name="admin_notes" rows="2"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Catatan admin (wajib isi jika status rejected)...">{{ $claim->admin_notes }}</textarea>
                                <button type="submit"
                                    class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                                    Update Klaim
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-500 italic">Belum ada klaim garansi masuk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($claims->hasPages())
        <div class="mt-4 rounded-lg bg-white p-4 shadow">
            {{ $claims->links() }}
        </div>
    @endif
@endsection
