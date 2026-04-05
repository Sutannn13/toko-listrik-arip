@extends('layouts.admin')

@section('header', 'Dashboard Utama')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Total Barang</h3>
            <p class="mt-2 text-3xl font-bold text-gray-800">0</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Pesanan Aktif</h3>
            <p class="mt-2 text-3xl font-bold text-gray-800">0</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Total Pelanggan</h3>
            <p class="mt-2 text-3xl font-bold text-gray-800">0</p>
        </div>

    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Sistem Toko Listrik Arip Aktif</h3>
        <p class="text-gray-600">
            Ini adalah panel khusus Super Admin. Pastikan data produk seperti Kabel, Lampu, dan MCB diinput dengan satuan
            (unit) dan harga yang benar sebelum dipublikasikan ke Landing Page.
        </p>
    </div>
@endsection
