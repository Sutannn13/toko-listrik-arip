@extends('layouts.admin')

@section('header', 'Notifikasi Admin')

@section('content')
    <x-ui.page-header title="Notifikasi Admin"
        subtitle="Pusat update operasional: klaim garansi, perubahan status pesanan, dan aksi penting lain.">
        <x-slot:actions>
            <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="ui-btn ui-btn-secondary">
                    Tandai Semua Dibaca
                </button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.flash-alerts')

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-gray-800 text-sm uppercase tracking-wider text-white">
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium">Judul</th>
                    <th class="p-4 font-medium">Pesan</th>
                    <th class="p-4 font-medium">Waktu</th>
                    <th class="p-4 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse ($notifications as $notification)
                    @php
                        $payload = $notification->data;
                    @endphp

                    <tr class="border-b border-gray-200 align-top hover:bg-gray-50">
                        <td class="p-4">
                            @if ($notification->read_at)
                                <span class="inline-flex rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">
                                    Dibaca
                                </span>
                            @else
                                <span class="inline-flex rounded bg-cyan-100 px-2 py-1 text-xs font-semibold text-cyan-700">
                                    Baru
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-sm font-semibold text-gray-900">{{ $payload['title'] ?? '-' }}</td>
                        <td class="p-4 text-sm text-gray-700">{{ $payload['message'] ?? '-' }}</td>
                        <td class="p-4 text-sm text-gray-600">{{ $notification->created_at->format('d M Y H:i') }}</td>
                        <td class="p-4">
                            @if (!empty($payload['route']))
                                <a href="{{ $payload['route'] }}"
                                    class="inline-flex text-xs font-semibold text-blue-600 hover:underline">
                                    Buka Detail
                                </a>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500 italic">Belum ada notifikasi admin.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($notifications->hasPages())
        <div class="mt-4 rounded-lg bg-white p-4 shadow">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
