@extends('layouts.admin')

@section('header', 'Dashboard Utama')

@section('content')
    <x-ui.page-header title="Dashboard Operasional"
        subtitle="Ringkasan metrik harian untuk mempercepat triage order, payment, dan klaim garansi." />

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="ui-card ui-card-pad border-l-4 border-cyan-500">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Produk Aktif</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format((int) ($overview['total_products'] ?? 0)) }}
            </p>
            <a href="{{ route('admin.products.index') }}"
                class="mt-2 inline-flex text-xs font-semibold text-cyan-700 hover:underline">
                Kelola Produk
            </a>
        </article>

        <article class="ui-card ui-card-pad border-l-4 border-blue-500">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Pesanan Aktif</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format((int) ($overview['active_orders'] ?? 0)) }}
            </p>
            <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}"
                class="mt-2 inline-flex text-xs font-semibold text-blue-700 hover:underline">
                Cek Pipeline
            </a>
        </article>

        <article class="ui-card ui-card-pad border-l-4 border-emerald-500">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Pelanggan</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format((int) ($overview['total_customers'] ?? 0)) }}
            </p>
            <p class="mt-2 text-xs text-gray-500">Akun role user terdaftar di sistem.</p>
        </article>

        <article class="ui-card ui-card-pad border-l-4 border-amber-500">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Notifikasi Belum Dibaca</p>
            <p class="mt-2 text-3xl font-black text-gray-900">
                {{ number_format((int) ($overview['unread_notifications'] ?? 0)) }}</p>
            <a href="{{ route('admin.notifications.index') }}"
                class="mt-2 inline-flex text-xs font-semibold text-amber-700 hover:underline">
                Buka Notifikasi
            </a>
        </article>
    </div>

    <div class="mb-8 ui-card ui-card-pad">
        <h3 class="text-base font-extrabold text-gray-900">Antrian Prioritas Admin</h3>
        <p class="mt-1 text-sm text-gray-600">Urutan kerja paling berdampak untuk menurunkan komplain dan percepat fulfill.
        </p>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Verifikasi Pembayaran</p>
                <p class="mt-1 text-2xl font-black text-cyan-800">
                    {{ number_format((int) ($triage['payments_to_verify'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-cyan-700">Pending + bukti transfer sudah diunggah.</p>
                <a href="{{ route('admin.orders.index', ['payment_status' => 'pending', 'proof' => 'uploaded']) }}"
                    class="mt-2 inline-flex text-xs font-semibold text-cyan-800 hover:underline">
                    Review Sekarang
                </a>
            </article>

            <article class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Siap Dikirim</p>
                <p class="mt-1 text-2xl font-black text-indigo-800">
                    {{ number_format((int) ($triage['orders_ready_to_ship'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-indigo-700">Order processing dengan pembayaran paid.</p>
                <a href="{{ route('admin.orders.index', ['status' => 'processing', 'payment_status' => 'paid']) }}"
                    class="mt-2 inline-flex text-xs font-semibold text-indigo-800 hover:underline">
                    Proses Pengiriman
                </a>
            </article>

            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Klaim Baru</p>
                <p class="mt-1 text-2xl font-black text-amber-800">
                    {{ number_format((int) ($triage['claims_submitted'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-amber-700">Klaim status submitted yang menunggu review.</p>
                <a href="{{ route('admin.warranty-claims.index', ['status' => 'submitted']) }}"
                    class="mt-2 inline-flex text-xs font-semibold text-amber-800 hover:underline">
                    Triage Klaim
                </a>
            </article>

            <article class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Lewat SLA 2x24</p>
                <p class="mt-1 text-2xl font-black text-red-800">
                    {{ number_format((int) ($triage['claims_sla_overdue'] ?? 0)) }}</p>
                <p class="mt-1 text-xs text-red-700">Klaim open yang berpotensi eskalasi.</p>
                <a href="{{ route('admin.warranty-claims.index', ['age_bucket' => 'sla_overdue']) }}"
                    class="mt-2 inline-flex text-xs font-semibold text-red-800 hover:underline">
                    Tangani Prioritas
                </a>
            </article>
        </div>
    </div>

    <div class="mb-8 ui-card ui-card-pad">
        <h3 class="text-base font-extrabold text-gray-900">Trend 7 Hari (Mini Chart)</h3>
        <p class="mt-1 text-sm text-gray-600">Pantau pergerakan revenue paid, volume klaim, dan backlog operasional harian.
        </p>

        @php
            $revenueTrend = collect($trend7d['revenue'] ?? []);
            $claimsTrend = collect($trend7d['claims'] ?? []);
            $backlogTrend = collect($trend7d['backlog'] ?? []);
        @endphp

        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <article class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Revenue Paid</p>
                        <p class="mt-1 text-lg font-black text-cyan-800">Rp
                            {{ number_format((int) $revenueTrend->sum('value'), 0, ',', '.') }}</p>
                    </div>
                    <p class="text-xs font-semibold text-cyan-700">7 hari</p>
                </div>

                <div class="mt-4 grid h-24 grid-cols-7 items-end gap-1">
                    @foreach ($revenueTrend as $point)
                        <div class="flex h-full items-end"
                            title="{{ $point['label'] }}: Rp {{ number_format((int) $point['value'], 0, ',', '.') }}">
                            <div class="w-full rounded-t-md bg-cyan-500 transition-opacity hover:opacity-80"
                                style="height: {{ $point['height'] }}%;"></div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-cyan-700">
                    @foreach ($revenueTrend as $point)
                        <span>{{ $point['short_label'] }}</span>
                    @endforeach
                </div>
            </article>

            <article class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Klaim Masuk</p>
                        <p class="mt-1 text-lg font-black text-indigo-800">
                            {{ number_format((int) $claimsTrend->sum('value')) }} klaim</p>
                    </div>
                    <p class="text-xs font-semibold text-indigo-700">7 hari</p>
                </div>

                <div class="mt-4 grid h-24 grid-cols-7 items-end gap-1">
                    @foreach ($claimsTrend as $point)
                        <div class="flex h-full items-end"
                            title="{{ $point['label'] }}: {{ number_format((int) $point['value']) }} klaim">
                            <div class="w-full rounded-t-md bg-indigo-500 transition-opacity hover:opacity-80"
                                style="height: {{ $point['height'] }}%;"></div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-indigo-700">
                    @foreach ($claimsTrend as $point)
                        <span>{{ $point['short_label'] }}</span>
                    @endforeach
                </div>
            </article>

            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Backlog Harian</p>
                        <p class="mt-1 text-lg font-black text-amber-800">
                            {{ number_format((int) $backlogTrend->sum('value')) }} item</p>
                    </div>
                    <p class="text-xs font-semibold text-amber-700">7 hari</p>
                </div>

                <div class="mt-4 grid h-24 grid-cols-7 items-end gap-1">
                    @foreach ($backlogTrend as $point)
                        <div class="flex h-full items-end"
                            title="{{ $point['label'] }}: {{ number_format((int) $point['value']) }} item backlog">
                            <div class="w-full rounded-t-md bg-amber-500 transition-opacity hover:opacity-80"
                                style="height: {{ $point['height'] }}%;"></div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-amber-700">
                    @foreach ($backlogTrend as $point)
                        <span>{{ $point['short_label'] }}</span>
                    @endforeach
                </div>
            </article>
        </div>
    </div>

    <div class="ui-card ui-card-pad">
        <h3 class="text-base font-extrabold text-gray-900">Metrik 30 Hari Terakhir</h3>
        <p class="mt-1 text-sm text-gray-600">Dipakai sebagai baseline Sprint 2 untuk evaluasi perbaikan funnel checkout dan
            operasi admin.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Paid Revenue (30d)</p>
                <p class="mt-1 text-2xl font-black text-gray-900">Rp
                    {{ number_format((int) ($metrics['paid_revenue_30d'] ?? 0), 0, ',', '.') }}</p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Avg Nilai Order Paid (30d)</p>
                <p class="mt-1 text-2xl font-black text-gray-900">Rp
                    {{ number_format((int) ($metrics['avg_paid_order_value_30d'] ?? 0), 0, ',', '.') }}</p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fulfillment Rate (30d)</p>
                <p class="mt-1 text-2xl font-black text-gray-900">
                    {{ number_format((float) ($metrics['fulfillment_rate_30d'] ?? 0), 1, ',', '.') }}%</p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Open Claim &lt; 48 Jam</p>
                <p class="mt-1 text-2xl font-black text-gray-900">
                    {{ number_format((int) ($metrics['open_claims_48h'] ?? 0)) }}</p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Order Pending Payment</p>
                <p class="mt-1 text-2xl font-black text-gray-900">
                    {{ number_format((int) ($metrics['pending_payment_orders'] ?? 0)) }}</p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Order Payment Failed</p>
                <p class="mt-1 text-2xl font-black text-gray-900">
                    {{ number_format((int) ($metrics['failed_payment_orders'] ?? 0)) }}</p>
            </article>
        </div>
    </div>
@endsection
