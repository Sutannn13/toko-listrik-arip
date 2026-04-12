@extends('layouts.admin')

@section('header', 'Edit Produk')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Produk
        </a>
    </div>

    <div
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card max-w-4xl">
        <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-5 dark:border-dark-border">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-brand-100 dark:bg-brand-500/10">
                <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Edit Produk</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product->name }}</p>
            </div>
        </div>

        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div
                    class="mb-5 rounded-xl border border-error-200 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10">
                    <ul class="space-y-1 text-sm text-error-700 dark:text-error-400">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- Nama --}}
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Nama Produk <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                        required>
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Kategori <span class="text-error-500">*</span>
                    </label>
                    <select name="category_id"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                        required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Harga --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Harga (Rp) <span class="text-error-500">*</span>
                    </label>
                    <input type="number" name="price" min="0" value="{{ old('price', $product->price) }}"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                        required>
                </div>

                {{-- Stok --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Stok <span class="text-error-500">*</span>
                    </label>
                    <input type="number" name="stock" min="0" value="{{ old('stock', $product->stock) }}"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                        required>
                </div>

                {{-- Satuan --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Satuan <span class="text-error-500">*</span>
                    </label>
                    <select name="unit"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                        required>
                        <option value="pcs" @selected(old('unit', $product->unit) === 'pcs')>PCS</option>
                        <option value="meter" @selected(old('unit', $product->unit) === 'meter')>METER</option>
                        <option value="roll" @selected(old('unit', $product->unit) === 'roll')>ROLL</option>
                        <option value="box" @selected(old('unit', $product->unit) === 'box')>BOX / DUS</option>
                    </select>
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">Deskripsi /
                        Spesifikasi</label>
                    <textarea name="description" rows="4"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                        placeholder="Contoh: Kabel ukuran 2x1.5mm, warna putih, SNI...">{{ old('description', $product->description) }}</textarea>
                </div>

                {{-- Foto Produk --}}
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">Foto Produk</label>

                    @if ($product->image_path)
                        <div
                            class="mb-3 flex items-start gap-4 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-dark-border dark:bg-dark-hover">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                class="h-20 w-20 rounded-lg border border-gray-200 object-cover dark:border-dark-border">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <p class="font-semibold text-gray-700 dark:text-gray-300">Gambar saat ini</p>
                                <p class="mt-1">Upload gambar baru untuk mengganti gambar ini.</p>
                                <label
                                    class="mt-2 inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-error-600">
                                    <input type="checkbox" name="remove_image" value="1"
                                        class="h-4 w-4 rounded border-gray-300 text-error-600 focus:ring-error-500">
                                    Hapus gambar saat ini
                                </label>
                            </div>
                        </div>
                    @endif

                    <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Format: JPG/PNG/WEBP, maksimal 4MB.</p>
                </div>

                {{-- Toggles --}}
                <div class="md:col-span-2 grid sm:grid-cols-2 gap-4">
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                        <input type="checkbox" name="is_electronic" value="1" @checked(old('is_electronic', $product->is_electronic))
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800 dark:text-white">Produk Elektronik</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Aktifkan untuk mengizinkan klaim garansi
                                (maks. 7 hari).</span>
                        </span>
                    </label>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-green-100 bg-green-50 p-4 dark:border-green-500/20 dark:bg-green-500/10">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800 dark:text-white">Produk Aktif</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Nonaktifkan untuk menyembunyikan dari
                                katalog.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5 dark:border-dark-border">
                <a href="{{ route('admin.products.index') }}"
                    class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300">
                    Batal
                </a>
                <button type="submit"
                    class="rounded-xl bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-600">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
