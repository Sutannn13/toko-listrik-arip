@extends('layouts.admin')

@section('header', 'Manajemen Kategori')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Daftar Kategori</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola kategori produk yang tersedia di katalog.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Kategori
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-dark-border dark:bg-dark-card overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-dark-border dark:bg-dark-hover">
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Kategori</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Slug</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Produk</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                @forelse($categories as $i => $category)
                    <tr class="transition hover:bg-gray-50/50 dark:hover:bg-dark-hover/50">
                        <td class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $category->name }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <code class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-dark-hover dark:text-gray-400">
                                {{ $category->slug }}
                            </code>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold
                                {{ $category->products_count > 0 ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400' : 'bg-gray-100 text-gray-400 dark:bg-dark-hover dark:text-gray-500' }}">
                                {{ $category->products_count }} produk
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                    class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400"
                                    title="Edit Kategori">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                    x-data
                                    @submit.prevent="if(confirm('Hapus kategori \"{{ addslashes($category->name) }}\"?')) $el.submit()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-error-300 hover:bg-error-50 hover:text-error-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400
                                            {{ $category->products_count > 0 ? 'cursor-not-allowed opacity-40' : '' }}"
                                        title="{{ $category->products_count > 0 ? 'Tidak bisa dihapus — masih ada produk' : 'Hapus Kategori' }}"
                                        {{ $category->products_count > 0 ? 'disabled' : '' }}>
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-dark-hover mb-3">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada kategori.</p>
                            <a href="{{ route('admin.categories.create') }}"
                                class="mt-3 inline-flex text-sm font-semibold text-brand-500 hover:underline">
                                + Tambah kategori pertama
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
