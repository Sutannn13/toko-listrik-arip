@extends('layouts.storefront')

@section('title', 'Riwayat Transaksi - Toko HS ELECTRIC')
@section('header_subtitle', 'Riwayat Transaksi')
@section('show_default_store_actions', 'off')

@section('header_actions')
    <a href="{{ route('home.cart') }}"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
        Keranjang
    </a>
    <a href="{{ route('home.warranty') }}"
        class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
        Pusat Garansi
    </a>
@endsection

@section('content')
    <x-ui.page-header title="Riwayat Transaksi"
        subtitle="Pantau status pesanan dan histori pembayaran Anda dalam satu halaman." />

    @include('partials.flash-alerts')

    @include('home.partials.filters.transactions', ['filters' => $filters])

    <div class="space-y-4">
        @forelse ($orders as $order)
            @include('home.partials.cards.transaction-order', ['order' => $order])
        @empty
            <div
                class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center text-sm text-gray-500">
                Belum ada data transaksi sesuai filter.
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
