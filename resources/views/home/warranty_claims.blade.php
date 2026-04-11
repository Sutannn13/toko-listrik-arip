@extends('layouts.storefront')

@section('title', 'Riwayat Klaim Garansi - Toko HS ELECTRIC')
@section('header_subtitle', 'Riwayat Klaim Garansi')
@section('show_default_store_actions', 'off')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8')

@section('content')
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Riwayat Klaim Garansi</h1>
                <p class="mt-1 text-sm text-gray-500">Lihat status klaim, catatan admin, dan bukti kerusakan yang pernah Anda unggah.</p>
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
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-gray-600 hover:text-primary-700 hover:border-primary-300 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Pusat Garansi
            </a>
            <a href="{{ route('home.warranty-claims.index') }}"
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-primary-600 bg-white px-4 py-2.5 text-sm font-bold text-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Riwayat Klaim
            </a>
        </nav>
    </div>

    @include('partials.flash-alerts')

    @include('home.partials.filters.warranty-claims', ['filters' => $filters])

    <div class="space-y-4">
        @forelse ($claims as $claim)
            @php
                $latestActivity = $claim->activities->first();
                $isApproved = in_array($claim->status, ['approved', 'resolved'], true);
                $isRejected = $claim->status === 'rejected';
                $statusColor = $isApproved
                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                    : ($isRejected
                        ? 'bg-red-100 text-red-700 border-red-200'
                        : 'bg-amber-100 text-amber-700 border-amber-200');
                $statusIcon = $isApproved ? '✓' : ($isRejected ? '✕' : '⏳');
            @endphp

            <article class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                {{-- Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $isApproved ? 'bg-emerald-100' : ($isRejected ? 'bg-red-100' : 'bg-amber-100') }}">
                            <span class="text-base">{{ $statusIcon }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-primary-700">{{ $claim->claim_code }}</p>
                            <p class="text-xs text-gray-500">
                                Diajukan: {{ optional($claim->requested_at)->format('d M Y H:i') ?? $claim->created_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full border px-3 py-1 text-xs font-bold uppercase {{ $statusColor }}">
                        {{ $claim->status }}
                    </span>
                </div>

                {{-- Body --}}
                <div class="p-5 grid gap-4 md:grid-cols-2">
                    {{-- Kolom kiri: Detail klaim --}}
                    <div class="space-y-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Order</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $claim->order?->order_code ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Produk</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $claim->orderItem?->product_name ?? '-' }}</p>
                            @if ($claim->orderItem)
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Garansi: {{ $claim->orderItem->warranty_days }} hari
                                    @if ($claim->orderItem->warranty_expires_at)
                                        &bull; s/d {{ optional($claim->orderItem->warranty_expires_at)->format('d M Y') }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Alasan Klaim</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $claim->reason }}</p>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Update Terakhir</p>
                            <p class="text-xs text-gray-500">
                                {{ $latestActivity?->created_at?->format('d M Y H:i') ?? $claim->updated_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>

                    {{-- Kolom kanan: Catatan admin & bukti --}}
                    <div class="space-y-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Catatan Admin</p>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $claim->admin_notes ?: 'Belum ada catatan dari admin.' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Bukti Kerusakan</p>
                            @if ($claim->damage_proof_url)
                                @if (str_starts_with((string) $claim->damage_proof_mime, 'image/'))
                                    <a href="{{ Storage::url($claim->damage_proof_url) }}" target="_blank"
                                        class="block w-fit mt-1 group">
                                        <img src="{{ Storage::url($claim->damage_proof_url) }}" alt="Bukti Kerusakan"
                                            class="h-28 w-auto rounded-lg border border-gray-200 shadow-sm object-cover group-hover:opacity-80 transition">
                                    </a>
                                @endif

                                <a href="{{ Storage::url($claim->damage_proof_url) }}" target="_blank"
                                    class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:underline">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Buka file bukti
                                </a>
                            @else
                                <p class="text-sm text-gray-400 italic">Tidak ada bukti terlampir.</p>
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
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">Belum ada klaim garansi</p>
                <p class="mt-1 text-xs text-gray-400">Klaim garansi Anda akan tercatat di sini setelah diajukan.</p>
                <a href="{{ route('home.warranty') }}"
                    class="mt-5 inline-flex rounded-xl bg-primary-600 px-5 py-2 text-sm font-bold text-white shadow-md shadow-primary-500/20 hover:bg-primary-700 transition">
                    Ajukan Klaim Garansi
                </a>
            </div>
        @endforelse
    </div>

    @if ($claims->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            {{ $claims->links() }}
        </div>
    @endif
@endsection
