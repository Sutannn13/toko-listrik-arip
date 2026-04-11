@extends('layouts.admin')

@section('header', 'Edit Kategori')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Kategori
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-dark-border dark:bg-dark-card max-w-lg">
        <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-5 dark:border-dark-border">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-brand-100 dark:bg-brand-500/10">
                <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Edit Kategori</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $category->name }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-error-200 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10">
                <ul class="space-y-1 text-sm text-error-700 dark:text-error-400">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Nama Kategori <span class="text-error-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}"
                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                    placeholder="Contoh: Elektronik" required>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Slug saat ini: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] dark:bg-dark-hover">{{ $category->slug }}</code>
                    <span class="ml-1">(akan terupdate otomatis jika nama berubah)</span>
                </p>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 pt-5 dark:border-dark-border">
                <a href="{{ route('admin.categories.index') }}"
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
