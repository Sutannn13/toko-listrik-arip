@extends('layouts.admin')

@section('header', 'Manajemen Kategori Produk')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Daftar Kategori</h3>
            <p class="text-sm text-gray-600">Atur kategori barang listrik lo di sini.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            + Tambah Kategori
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white text-sm uppercase tracking-wider">
                    <th class="p-4 font-medium">ID</th>
                    <th class="p-4 font-medium">Nama Kategori</th>
                    <th class="p-4 font-medium">Slug (URL)</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse($categories as $category)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="p-4">{{ $category->id }}</td>
                        <td class="p-4 font-semibold">{{ $category->name }}</td>
                        <td class="p-4 text-gray-500">{{ $category->slug }}</td>
                        <td class="p-4 text-right">
                            <button class="text-blue-600 hover:underline mr-2">Edit</button>
                            <button class="text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-gray-500 italic">
                            Belum ada kategori yang ditambahkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
