@php
    $currentStatus = $filters['status'] ?? 'completed';
    $tabs = [
        'all'        => ['label' => 'Semua', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>'],
        'pending'    => ['label' => 'Menunggu', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        'processing' => ['label' => 'Diproses', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'],
        'shipped'    => ['label' => 'Dikirim', 'icon' => '<path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" stroke-linecap="round" stroke-linejoin="round"/>'],
        'completed'  => ['label' => 'Selesai', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        'cancelled'  => ['label' => 'Dibatalkan', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ];
@endphp

{{-- Status Tabs --}}
<div class="mb-5 -mx-1 overflow-x-auto">
    <div class="flex gap-1 px-1 min-w-max">
        @foreach ($tabs as $value => $tab)
            @php $isActive = $currentStatus === $value; @endphp
            <a href="{{ route('home.transactions', array_merge(request()->except('status', 'page'), ['status' => $value])) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium transition
                      {{ $isActive
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-gray-800' }}">
                <svg class="h-4 w-4 {{ $isActive ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    {!! $tab['icon'] !!}
                </svg>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>

{{-- Search Bar --}}
<form method="GET" action="{{ route('home.transactions') }}" class="mb-5">
    <input type="hidden" name="status" value="{{ $currentStatus }}">
    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
               class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-24 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition"
               placeholder="Cari kode order, nama, atau email...">
        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-1.5">
            @if (!empty($filters['q']))
                <a href="{{ route('home.transactions', ['status' => $currentStatus]) }}"
                   class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100 transition">Reset</a>
            @endif
            <button type="submit"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-700">
                Cari
            </button>
        </div>
    </div>
</form>
