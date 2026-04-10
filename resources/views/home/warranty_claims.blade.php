@extends('layouts.storefront')

@section('title', 'Riwayat Klaim Garansi - Toko Listrik Arip')
@section('header_subtitle', 'Riwayat Klaim Garansi')
@section('show_default_store_actions', 'off')

@section('header_actions')
    <a href="{{ route('home.cart') }}"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
        Kembali ke Keranjang
    </a>
@endsection

@section('content')
    <x-ui.page-header title="Riwayat Klaim Garansi"
        subtitle="Lihat status klaim, catatan admin, dan riwayat bukti kerusakan yang pernah Anda unggah." />

    @include('partials.flash-alerts')

    @include('home.partials.filters.warranty-claims', ['filters' => $filters])

    <div class="space-y-4">
        @forelse ($claims as $claim)
            @php
                $latestActivity = $claim->activities->first();
            @endphp

            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-extrabold text-primary-700">{{ $claim->claim_code }}</p>
                        <p class="text-xs text-gray-500">
                            Diajukan:
                            {{ optional($claim->requested_at)->format('d M Y H:i') ?? $claim->created_at->format('d M Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Update terakhir:
                            {{ $latestActivity?->created_at?->format('d M Y H:i') ?? $claim->updated_at->format('d M Y H:i') }}
                        </p>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-bold uppercase {{ in_array($claim->status, ['approved', 'resolved'], true) ? 'bg-emerald-100 text-emerald-700' : ($claim->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ $claim->status }}
                    </span>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">Order</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $claim->order?->order_code ?? '-' }}</p>

                        <p class="mt-3 text-xs font-semibold uppercase text-gray-500">Produk</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $claim->orderItem?->product_name ?? '-' }}</p>

                        <p class="mt-3 text-xs font-semibold uppercase text-gray-500">Alasan Klaim</p>
                        <p class="text-sm text-gray-700">{{ $claim->reason }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">Catatan Admin</p>
                        <p class="mt-1 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            {{ $claim->admin_notes ?: 'Belum ada catatan admin.' }}
                        </p>

                        <p class="mt-3 text-xs font-semibold uppercase text-gray-500">Bukti Kerusakan</p>
                        @if ($claim->damage_proof_url)
                            @if (str_starts_with((string) $claim->damage_proof_mime, 'image/'))
                                <a href="{{ Storage::url($claim->damage_proof_url) }}" target="_blank"
                                    class="mt-1 block w-fit">
                                    <img src="{{ Storage::url($claim->damage_proof_url) }}" alt="Bukti Kerusakan"
                                        class="h-24 rounded border shadow-sm hover:opacity-80 transition">
                                </a>
                            @endif

                            <a href="{{ Storage::url($claim->damage_proof_url) }}" target="_blank"
                                class="mt-1 inline-flex text-xs font-semibold text-primary-700 hover:underline">
                                Buka file bukti
                            </a>
                        @else
                            <p class="text-sm text-gray-500">Tidak ada bukti terlampir.</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div
                class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center text-sm text-gray-500">
                Belum ada klaim garansi yang tercatat di akun Anda.
            </div>
        @endforelse
    </div>

    @if ($claims->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
            {{ $claims->links() }}
        </div>
    @endif
@endsection
