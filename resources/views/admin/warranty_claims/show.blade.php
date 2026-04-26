@extends('layouts.admin')

@section('header', 'Detail Klaim Garansi')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-800">{{ $warrantyClaim->claim_code }}</h3>
            <p class="text-sm text-gray-600">Detail klaim, status saat ini, dan timeline aktivitas.</p>
        </div>

        <a href="{{ route('admin.warranty-claims.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">
            &larr; Kembali ke Daftar Klaim
        </a>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 border-b pb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Informasi Klaim</h4>
            <p class="text-sm text-gray-600">Status:</p>
            <p class="mt-1 inline-flex rounded-full bg-cyan-100 px-2 py-1 text-xs font-bold uppercase text-cyan-700">
                {{ $warrantyClaim->status }}
            </p>

            @if ($warrantyClaim->is_sla_overdue)
                <p class="mt-2 inline-flex rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                    Melewati SLA 2x24 jam
                </p>
            @else
                <p class="mt-2 inline-flex rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                    Dalam SLA
                </p>
            @endif

            <p class="mt-3 text-sm text-gray-600">Diajukan:</p>
            <p class="text-sm font-semibold text-gray-800">
                {{ optional($warrantyClaim->requested_at)->format('d M Y H:i') ?? $warrantyClaim->created_at->format('d M Y H:i') }}
            </p>

            <p class="mt-3 text-sm text-gray-600">Batas SLA:</p>
            <p class="text-sm font-semibold text-gray-800">
                {{ optional($warrantyClaim->sla_deadline)->format('d M Y H:i') ?? '-' }}
            </p>

            <p class="mt-3 text-sm text-gray-600">Resolved:</p>
            <p class="text-sm font-semibold text-gray-800">
                {{ optional($warrantyClaim->resolved_at)->format('d M Y H:i') ?? '-' }}
            </p>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 border-b pb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Order & Produk</h4>
            <p class="text-sm text-gray-600">Order:</p>
            @if ($warrantyClaim->order)
                <a href="{{ route('admin.orders.show', $warrantyClaim->order) }}"
                    class="text-sm font-semibold text-blue-600 hover:underline">
                    {{ $warrantyClaim->order->order_code }}
                </a>
            @else
                <p class="text-sm text-gray-500">Order tidak ditemukan</p>
            @endif

            <p class="mt-3 text-sm text-gray-600">Produk:</p>
            <p class="text-sm font-semibold text-gray-800">{{ $warrantyClaim->orderItem->product_name ?? '-' }}</p>
            <p class="text-xs text-gray-500">Qty: {{ $warrantyClaim->orderItem->quantity ?? '-' }}</p>
            <p class="text-xs text-gray-500 mt-1">
                Jenis: {{ $warrantyClaim->orderItem?->product?->is_electronic ? 'Elektronik' : 'Non-elektronik' }}
            </p>

            <p class="mt-3 text-sm text-gray-600">Masa garansi sampai:</p>
            <p class="text-sm font-semibold text-gray-800">
                {{ optional($warrantyClaim->orderItem?->warranty_expires_at)->format('d M Y H:i') ?? '-' }}
            </p>

            <p class="mt-3 text-sm text-gray-600">Bukti kerusakan:</p>
            @if ($warrantyClaim->damage_proof_url)
                @php
                    $damageProofUrl = route('home.warranty-claims.proof.view', $warrantyClaim);
                @endphp
                @if (str_starts_with((string) $warrantyClaim->damage_proof_mime, 'image/'))
                    <a href="{{ $damageProofUrl }}" target="_blank"
                        class="block mt-1 w-fit">
                        <img src="{{ $damageProofUrl }}" alt="Bukti Kerusakan"
                            class="h-24 rounded border shadow-sm hover:opacity-80 transition">
                    </a>
                @endif

                <a href="{{ $damageProofUrl }}" target="_blank"
                    class="mt-1 inline-flex text-xs font-semibold text-blue-600 hover:underline">
                    Buka file bukti
                </a>
            @else
                <p class="text-sm text-gray-500">Tidak ada bukti terlampir.</p>
            @endif
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
            <h4 class="mb-3 border-b pb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Update Status Klaim</h4>
            <form action="{{ route('admin.warranty-claims.update-status', $warrantyClaim) }}" method="POST"
                class="space-y-3">
                @csrf
                @method('PATCH')

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Status</label>
                    <select name="status"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (['submitted', 'reviewing', 'approved', 'rejected', 'resolved'] as $status)
                            <option value="{{ $status }}" @selected($warrantyClaim->status === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Catatan Admin</label>
                    <textarea name="admin_notes" rows="4"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: Bukti tidak valid karena ... (wajib isi saat status rejected).">{{ $warrantyClaim->admin_notes }}</textarea>
                    <p class="mt-1 text-[11px] text-gray-500">Alasan wajib saat status klaim diubah menjadi rejected.</p>
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Simpan Update
                </button>
            </form>
        </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-5 shadow">
        <h4 class="mb-3 text-base font-bold text-gray-800">Alasan Klaim Pelanggan</h4>
        <p class="text-sm leading-relaxed text-gray-700">{{ $warrantyClaim->reason }}</p>
    </div>

    <div class="rounded-lg bg-white p-5 shadow">
        <h4 class="mb-4 text-base font-bold text-gray-800">Timeline Aktivitas Klaim</h4>

        <div class="space-y-3">
            @forelse ($warrantyClaim->activities as $activity)
                <article class="rounded-lg border border-gray-200 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-800">
                            {{ str_replace('_', ' ', strtoupper($activity->action)) }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $activity->created_at->format('d M Y H:i') }}</p>
                    </div>

                    <p class="mt-1 text-xs text-gray-500">
                        Oleh: {{ $activity->actor?->name ?? ($activity->actor_name ?? 'System') }}
                    </p>

                    @if ($activity->from_status || $activity->to_status)
                        <p class="mt-2 text-xs text-gray-600">
                            Status: {{ $activity->from_status ?? '-' }} &rarr; {{ $activity->to_status ?? '-' }}
                        </p>
                    @endif

                    @if ($activity->note)
                        <p class="mt-2 rounded bg-slate-50 px-2 py-1 text-sm text-gray-700">{{ $activity->note }}</p>
                    @endif
                </article>
            @empty
                <p class="text-sm text-gray-500 italic">Belum ada aktivitas tercatat untuk klaim ini.</p>
            @endforelse
        </div>
    </div>
@endsection
