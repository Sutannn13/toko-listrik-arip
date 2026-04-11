@extends('layouts.storefront')

@section('title', 'Notifikasi Saya - Toko HS ELECTRIC')
@section('header_subtitle', 'Notifikasi Saya')
@section('show_default_store_actions', 'off')

@section('header_actions')
    <a href="{{ route('home.transactions') }}"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
        Riwayat Transaksi
    </a>
    <a href="{{ route('home.warranty-claims.index') }}"
        class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
        Riwayat Klaim
    </a>
@endsection

@section('content')
    <x-ui.page-header title="Notifikasi"
        subtitle="Semua update penting pesanan dan klaim garansi akun Anda akan muncul di sini.">
        <x-slot:actions>
            <form action="{{ route('home.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="ui-btn ui-btn-secondary">
                    Tandai Semua Dibaca
                </button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.flash-alerts')

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            @php
                $payload = $notification->data;
            @endphp

            <article
                class="rounded-xl border {{ $notification->read_at ? 'border-gray-200 bg-white' : 'border-primary-200 bg-primary-50/40' }} p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900">{{ $payload['title'] ?? '-' }}</p>
                        <p class="mt-1 text-sm text-gray-700">{{ $payload['message'] ?? '-' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $notification->created_at->format('d M Y H:i') }}</p>
                    </div>

                    @if (!$notification->read_at)
                        <span
                            class="inline-flex rounded bg-primary-100 px-2 py-1 text-[11px] font-semibold text-primary-700">Baru</span>
                    @endif
                </div>

                @if (!empty($payload['route']))
                    <a href="{{ $payload['route'] }}"
                        class="mt-3 inline-flex text-xs font-semibold text-primary-700 hover:underline">
                        Buka Detail
                    </a>
                @endif
            </article>
        @empty
            <div
                class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center text-sm text-gray-500">
                Belum ada notifikasi untuk akun Anda.
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
