@extends('layouts.storefront')

@section('title', 'Riwayat Transaksi - Toko HS ELECTRIC')
@section('header_subtitle', 'Riwayat Transaksi')
@section('show_default_store_actions', 'off')
@section('main_container_class', 'flex-1 w-full mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8')

@section('content')
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">Riwayat Transaksi</h1>
                <p class="mt-1 text-sm text-gray-500">Pantau status pesanan dan histori pembayaran Anda.</p>
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
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-primary-600 bg-white px-4 py-2.5 text-sm font-bold text-primary-700">
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
                class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-gray-600 hover:text-primary-700 hover:border-primary-300 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Riwayat Klaim
            </a>
        </nav>
    </div>

    @include('partials.flash-alerts')

    {{-- Filter --}}
    @include('home.partials.filters.transactions', ['filters' => $filters])

    {{-- List --}}
    <div class="space-y-4">
        @forelse ($orders as $order)
            @include('home.partials.cards.transaction-order', ['order' => $order])
        @empty
            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                    <svg class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">Belum ada transaksi</p>
                <p class="mt-1 text-xs text-gray-400">Transaksi Anda akan muncul di sini setelah checkout.</p>
                <a href="{{ route('home') }}"
                    class="mt-5 inline-flex rounded-xl bg-primary-600 px-5 py-2 text-sm font-bold text-white shadow-md shadow-primary-500/20 hover:bg-primary-700 transition">
                    Mulai Berbelanja
                </a>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
