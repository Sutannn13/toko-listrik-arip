@extends('layouts.admin')

@section('header', 'Manajemen Barang Listrik')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Daftar Produk</h3>
            <p class="text-sm text-gray-600">Atur stok dan harga barang jualan lo di sini.</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            + Tambah Barang Baru
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white text-sm uppercase tracking-wider">
                    <th class="p-4 font-medium">Nama Barang</th>
                    <th class="p-4 font-medium">Kategori</th>
                    <th class="p-4 font-medium">Harga</th>
                    <th class="p-4 font-medium">Stok & Satuan</th>
                    <th class="p-4 font-medium">Garansi</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse($products as $product)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="p-4 font-semibold">{{ $product->name }}</td>
                        <td class="p-4 text-gray-600">{{ $product->category->name ?? 'Tanpa Kategori' }}</td>
                        <td class="p-4 text-blue-600 font-bold">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                        <td class="p-4">
                            <span
                                class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">{{ $product->stock }}</span>
                            <span class="text-gray-500 uppercase text-xs ml-1">{{ $product->unit }}</span>
                        </td>
                        <td class="p-4">
                            @if ($product->is_electronic)
                                <span class="inline-flex rounded bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">
                                    Elektronik (Maks. 7 hari)
                                </span>
                            @else
                                <span class="inline-flex rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">
                                    Non-elektronik
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <button class="text-blue-600 hover:underline mr-2">Edit</button>
                            <button class="text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-500 italic">
                            Belum ada barang listrik yang ditambahkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
