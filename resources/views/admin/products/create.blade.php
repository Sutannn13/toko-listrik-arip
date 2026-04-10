@extends('layouts.admin')

@section('header', 'Tambah Barang Listrik')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="text-blue-600 hover:underline">
            &larr; Kembali ke Daftar Barang
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <form action="{{ route('admin.products.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Barang Listrik</label>
                    <input type="text" name="name"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kategori</label>
                    <select name="category_id"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                    <input type="number" name="price" min="0"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stok Tersedia</label>
                        <input type="number" name="stock" min="0" value="0"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Satuan (Unit)</label>
                        <select name="unit"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="pcs">PCS</option>
                            <option value="meter">METER</option>
                            <option value="roll">ROLL</option>
                            <option value="box">BOX / DUS</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Spesifikasi</label>
                <textarea name="description" rows="4"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Contoh: Kabel ukuran 2x1.5mm, warna putih, SNI..."></textarea>
            </div>

            <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
                <label class="inline-flex items-start gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_electronic" value="1" @checked(old('is_electronic'))
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>
                        <span class="font-semibold text-gray-900">Produk elektronik (bergaransi)</span><br>
                        <span class="text-xs text-gray-600">Aktifkan jika produk termasuk barang elektronik seperti kipas
                            angin, blender, atau perangkat listrik sejenis. Garansi klaim hanya berlaku untuk produk
                            elektronik.</span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end border-t pt-4">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                    Simpan Barang
                </button>
            </div>
        </form>
    </div>
@endsection
