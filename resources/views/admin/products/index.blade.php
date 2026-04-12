@extends('layouts.admin')

@section('header', 'Manajemen Produk')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Daftar Produk</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atur stok dan harga barang jualan di sini.</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Produk Baru
        </a>
    </div>

    {{-- Stock Adjust Modal (Alpine) --}}
    <div x-data="{
        open: false,
        productId: null,
        productName: '',
        currentStock: 0,
        action: 'add',
        amount: 1,
        openModal(id, name, stock) {
            this.productId = id;
            this.productName = name;
            this.currentStock = stock;
            this.action = 'add';
            this.amount = 1;
            this.open = true;
        }
    }">
        {{-- Modal backdrop --}}
        <div x-cloak x-show="open" x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
            @click.self="open = false">

            <div x-show="open" x-transition:enter="transition ease-out duration-200 transform"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150 transform"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-dark-border dark:bg-dark-card">

                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Ubah Stok Produk</h3>
                    <button @click="open = false"
                        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300" x-text="productName"></p>
                <p class="mb-4 text-xs text-gray-400">
                    Stok saat ini: <span class="font-bold text-gray-700 dark:text-gray-200" x-text="currentStock"></span>
                </p>

                <form :action="`{{ url('admin/products') }}/${productId}/adjust-stock`" method="POST">
                    @csrf

                    {{-- Action toggle --}}
                    <div class="mb-4 grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="action" value="add" x-model="action" class="sr-only peer">
                            <div
                                class="flex items-center justify-center gap-1.5 rounded-xl border-2 border-gray-200 py-2.5 text-sm font-semibold text-gray-600 transition peer-checked:border-success-500 peer-checked:bg-success-50 peer-checked:text-success-700 dark:border-dark-border dark:text-gray-400 dark:peer-checked:bg-success-500/10 dark:peer-checked:text-success-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="action" value="subtract" x-model="action" class="sr-only peer">
                            <div
                                class="flex items-center justify-center gap-1.5 rounded-xl border-2 border-gray-200 py-2.5 text-sm font-semibold text-gray-600 transition peer-checked:border-error-500 peer-checked:bg-error-50 peer-checked:text-error-700 dark:border-dark-border dark:text-gray-400 dark:peer-checked:bg-error-500/10 dark:peer-checked:text-error-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                                Kurangi
                            </div>
                        </label>
                    </div>

                    <div class="mb-5">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Jumlah</label>
                        <input type="number" name="amount" x-model="amount" min="1" max="9999"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-center text-lg font-bold text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="open = false"
                            class="rounded-xl border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300 dark:hover:bg-dark-hover">
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl py-2.5 text-sm font-bold text-white shadow-sm transition"
                            :class="action === 'add' ? 'bg-success-500 hover:bg-success-600 shadow-success-500/20' :
                                'bg-error-500 hover:bg-error-600 shadow-error-500/20'">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Product Table --}}
        <div
            class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-dark-border dark:bg-dark-card overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-dark-border dark:bg-dark-hover">
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Produk</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Kategori</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Harga</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Stok</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Rating</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Status</th>
                        <th
                            class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    @forelse($products as $product)
                        <tr class="group transition hover:bg-gray-50/50 dark:hover:bg-dark-hover/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                        class="h-12 w-12 rounded-lg border border-gray-200 object-cover dark:border-dark-border">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $product->name }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                            {{ $product->is_electronic ? '⚡ Elektronik · Garansi 7 hari' : '📦 Non-elektronik' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-dark-hover dark:text-gray-300">
                                    {{ $product->category->name ?? 'Tanpa Kategori' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-brand-600 dark:text-brand-400">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex min-w-[32px] items-center justify-center rounded-lg px-2.5 py-1 text-sm font-bold
                                        {{ $product->stock > 10 ? 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400' : ($product->stock > 0 ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' : 'bg-error-100 text-error-700 dark:bg-error-500/10 dark:text-error-400') }}">
                                        {{ $product->stock }}
                                    </span>
                                    <span class="text-xs text-gray-400 uppercase">{{ $product->unit }}</span>
                                    <button type="button"
                                        @click="openModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock }})"
                                        class="rounded-lg border border-gray-200 bg-white p-1 text-gray-400 opacity-0 group-hover:opacity-100 transition hover:border-brand-300 hover:text-brand-500 dark:border-dark-border dark:bg-dark-card"
                                        title="Ubah Stok">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if ($product->reviews_count > 0)
                                    <p class="text-sm font-semibold text-amber-600">⭐
                                        {{ number_format((float) $product->reviews_avg_rating, 1) }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->reviews_count }} ulasan</p>
                                @else
                                    <p class="text-xs text-gray-500">Belum ada ulasan</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $product->is_active ? 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-dark-hover dark:text-gray-400' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                        class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400"
                                        title="Edit Produk">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" x-data
                                        @submit.prevent="if(confirm('Hapus produk \"{{ addslashes($product->name) }}\"? Tindakan ini tidak bisa dibatalkan.')) $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-error-300 hover:bg-error-50 hover:text-error-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400"
                                            title="Hapus Produk">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
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
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-dark-hover mb-3">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada produk
                                    ditambahkan.</p>
                                <a href="{{ route('admin.products.create') }}"
                                    class="mt-3 inline-flex text-sm font-semibold text-brand-500 hover:underline">
                                    + Tambah produk pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div
                class="mt-4 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-dark-border dark:bg-dark-card">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
