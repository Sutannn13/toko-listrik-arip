@extends('layouts.storefront')

@section('title', $product->name . ' - Toko HS ELECTRIC')
@section('header_subtitle', 'Detail Produk')
@section('main_container_class', 'mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12')

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('home') }}" class="font-medium text-primary-600 transition hover:text-primary-700">Katalog</a>
        <span class="text-gray-400">/</span>
        <span class="font-medium text-gray-500">{{ $product->category->name ?? 'Tanpa Kategori' }}</span>
        <span class="text-gray-400">/</span>
        <span class="font-bold text-gray-900">{{ $product->name }}</span>
    </div>

    <section class="grid gap-8 lg:grid-cols-[1.5fr,1fr] xl:gap-12">
        <!-- Deskripsi Produk -->
        <article class="flex flex-col">
            <div class="mb-6 overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 shadow-sm">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                    class="h-64 w-full object-cover sm:h-80 lg:h-[26rem]">
            </div>

            <div class="mb-6 inline-block">
                <span
                    class="inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-bold uppercase tracking-widest text-primary-700">
                    {{ $product->category->name ?? 'Produk Resmi' }}
                </span>
            </div>

            <h1 class="text-3xl font-extrabold leading-tight text-gray-900 sm:text-4xl lg:text-5xl">
                {{ $product->name }}
            </h1>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <div class="flex items-center rounded-full bg-amber-50 px-3 py-1.5 text-amber-500">
                    @for ($star = 1; $star <= 5; $star++)
                        <svg class="h-4 w-4 {{ $star <= round($product->average_rating) ? 'fill-current' : 'fill-none text-amber-300' }}"
                            viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.539 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.719c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z" />
                        </svg>
                    @endfor
                </div>
                <p class="text-sm font-semibold text-gray-700">
                    @if ($product->reviews_total > 0)
                        {{ number_format($product->average_rating, 1) }} dari {{ $product->reviews_total }} ulasan
                        pelanggan
                    @else
                        Belum ada ulasan pelanggan
                    @endif
                </p>
            </div>

            <p class="mt-6 text-base leading-relaxed text-gray-600 sm:text-lg">
                {{ $product->description ?: 'Barang berkualitas & berstandar SNI dari Toko HS ELECTRIC.' }}
            </p>

            <div class="mt-8 grid gap-4 grid-cols-2 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Harga Spesial</p>
                    <p class="mt-2 text-2xl font-black text-primary-600">Rp
                        {{ number_format($product->price, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Stok Toko</p>
                    <p class="mt-2 text-2xl font-black {{ $product->stock > 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ number_format($product->stock) }}
                    </p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm col-span-2 sm:col-span-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Satuan Beli</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ strtoupper($product->unit) }}</p>
                </div>
            </div>

            @if (is_array($product->specifications) && count($product->specifications) > 0)
                <div class="mt-8 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-700">Spesifikasi
                            Lengkap</h2>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="grid gap-y-4 gap-x-6 sm:grid-cols-2">
                            @foreach ($product->specifications as $key => $value)
                                <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0 sm:border-0 sm:pb-0">
                                    <dt class="text-xs font-bold text-gray-500 mb-1">
                                        {{ \Illuminate\Support\Str::headline((string) $key) }}</dt>
                                    <dd class="text-sm font-semibold text-gray-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @endif
        </article>

        <!-- Box Checkout / Cart -->
        <aside class="flex flex-col">
            <div class="sticky top-24 rounded-3xl border border-gray-200 bg-white p-6 shadow-xl shadow-gray-200/50 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900">Pembelian</h2>
                <p class="mt-2 text-sm text-gray-500">Masukkan barang ini ke keranjang belanja Anda untuk
                    diproses lebih lanjut.</p>

                @if ($product->is_electronic)
                    <div
                        class="mt-4 flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Produk elektronik: garansi klaim maksimal 7 hari.
                    </div>
                @else
                    <div
                        class="mt-4 flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-3 text-sm font-medium text-gray-700">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01"></path>
                        </svg>
                        Produk non-elektronik: tidak termasuk klaim garansi.
                    </div>
                @endif

                @auth
                    <form method="POST" action="{{ route('home.products.buy', $product->slug) }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="qty" class="mb-2 block text-sm font-bold text-gray-700">Kuantitas
                                ({{ strtoupper($product->unit) }})
                            </label>
                            <div class="flex items-center">
                                <input id="qty" name="qty" type="number" min="1"
                                    max="{{ max(1, (int) $product->stock) }}" value="1"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition text-center font-bold">
                            </div>
                            @error('qty')
                                <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-base font-bold shadow-md transition {{ $product->stock < 1 ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-500 shadow-none' : 'bg-primary-600 text-white shadow-primary-500/20 hover:bg-primary-700 hover:shadow-primary-500/40' }}">
                            @if ($product->stock < 1)
                                Stok Habis
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                    </path>
                                </svg>
                                Tambah ke Keranjang
                            @endif
                        </button>
                    </form>
                @endauth

                @guest
                    <div class="mt-6 space-y-5">
                        <div>
                            <label for="qty-guest" class="mb-2 block text-sm font-bold text-gray-700">Kuantitas
                                ({{ strtoupper($product->unit) }})
                            </label>
                            <input id="qty-guest" type="number" value="1" disabled
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-base text-gray-500 shadow-sm transition text-center font-bold cursor-not-allowed">
                        </div>
                        <button type="button"
                            onclick="alert('Peringatan: Anda harus masuk/login ke sistem terlebih dahulu sebelum dapat menambahkan barang ke keranjang belanja.')"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-base font-bold shadow-md transition bg-gray-600 text-white shadow-gray-500/20 hover:bg-gray-700">
                            Tambah ke Keranjang
                        </button>
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-500">Atau <a href="{{ route('login') }}"
                                    class="text-primary-600 hover:underline">Masuk</a> sekarang</p>
                        </div>
                    </div>
                @endguest

                <a href="{{ route('home') }}"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Kembali Belanja
                </a>
            </div>
        </aside>
    </section>

    <!-- Terkait -->
    <section class="mt-16 border-t border-gray-200 pt-12">
        <div class="mb-8 flex items-center justify-between">
            <h2 class="text-2xl font-extrabold text-gray-900">Produk Terkait</h2>
            <a href="{{ route('home') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lihat
                Semua
                &rarr;</a>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($relatedProducts as $related)
                    <article
                        class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100">
                        <a href="{{ route('home.products.show', $related->slug) }}"
                            class="mb-4 block overflow-hidden rounded-xl border border-gray-100 bg-gray-50 aspect-[4/3]">
                            <img src="{{ $related->image_url }}" alt="{{ $related->name }}" loading="lazy"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </a>

                        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                            <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">
                                {{ $related->name }}</h3>
                            <span
                                class="rounded bg-gray-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-600">{{ $related->unit }}</span>
                        </div>

                        <p class="mb-2 text-xs font-medium text-gray-500">
                            @if ($related->reviews_total > 0)
                                ⭐ {{ number_format($related->average_rating, 1) }} ({{ $related->reviews_total }} ulasan)
                            @else
                                ⭐ Belum ada ulasan
                            @endif
                        </p>

                        <div class="mt-auto pt-4 flex flex-col gap-3">
                            <p class="text-lg font-black text-primary-600 border-t border-gray-100 pt-3">
                                Rp {{ number_format($related->price, 0, ',', '.') }}
                            </p>
                            <a href="{{ route('home.products.show', $related->slug) }}"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-gray-800">
                                Lihat Detail
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div
                class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center text-gray-500 font-medium">
                Belum ada produk dari kategori yang sama.
            </div>
        @endif
    </section>

    <section class="mt-16 border-t border-gray-200 pt-12">
        <div class="mb-8 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900">Rating & Ulasan</h2>
                <p class="mt-1 text-sm text-gray-500">Feedback pelanggan yang sudah membeli produk ini.</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Rata-rata</p>
                <p class="text-xl font-black text-amber-600">{{ number_format($product->average_rating, 1) }}/5</p>
                <p class="text-xs text-amber-700">{{ $product->reviews_total }} ulasan</p>
            </div>
        </div>

        @auth
            @if ($canReview)
                @php
                    $showReviewEditor =
                        !$userReview ||
                        $errors->has('rating') ||
                        $errors->has('comment') ||
                        old('review_editor') === '1';
                @endphp
                <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                {{ $userReview ? 'Ulasan Anda' : 'Tulis Ulasan Anda' }}</h3>
                            <p class="mt-1 text-xs text-gray-500">Rating wajib diisi. Ulasan saat ini hanya mendukung teks
                                tanpa gambar.</p>
                        </div>

                        @if ($userReview)
                            <button id="toggle_review_editor" type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-bold text-primary-700 transition hover:bg-primary-100">
                                {{ $showReviewEditor ? 'Sembunyikan Form Edit' : 'Edit Ulasan' }}
                            </button>
                        @endif
                    </div>

                    @if ($userReview)
                        <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900">Rating Anda: {{ $userReview->rating }}/5</p>
                                <p class="text-xs text-gray-500">Terakhir update:
                                    {{ optional($userReview->updated_at)->format('d M Y H:i') }}</p>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">{{ $userReview->comment ?: 'Belum ada komentar.' }}</p>
                        </div>
                    @endif

                    <div id="review_editor_form" class="mt-4 {{ $showReviewEditor ? '' : 'hidden' }}">
                        <form method="POST" action="{{ route('home.products.review', $product->slug) }}"
                            class="grid gap-4">
                            @csrf
                            <input type="hidden" name="review_editor" value="1">

                            <div>
                                <label for="rating" class="mb-1.5 block text-sm font-semibold text-gray-700">Rating
                                    (1-5)</label>
                                <select id="rating" name="rating"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                                    @for ($rate = 5; $rate >= 1; $rate--)
                                        <option value="{{ $rate }}" @selected((int) old('rating', $userReview?->rating ?? 5) === $rate)>
                                            {{ $rate }} - {{ str_repeat('★', $rate) }}
                                        </option>
                                    @endfor
                                </select>
                                @error('rating')
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="comment"
                                    class="mb-1.5 block text-sm font-semibold text-gray-700">Komentar</label>
                                <textarea id="comment" name="comment" rows="3"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                                    placeholder="Bagikan pengalaman Anda dengan produk ini...">{{ old('comment', $userReview?->comment) }}</textarea>
                                @error('comment')
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @error('image')
                                <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
                                    {{ $userReview ? 'Simpan Perubahan Ulasan' : 'Kirim Ulasan' }}
                                </button>

                                @if ($userReview)
                                    <button id="cancel_review_editor" type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                        Batal
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="mb-8 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    Anda bisa memberi ulasan setelah melakukan pembelian produk ini.
                </div>
            @endif
        @else
            <div class="mb-8 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                Silakan login terlebih dahulu untuk memberi ulasan.
            </div>
        @endauth

        @if ($reviews->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($reviews as $review)
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $review->user->name ?? 'Pelanggan' }}</p>
                                <p class="text-xs text-gray-500">{{ $review->created_at->format('d M Y') }}</p>
                            </div>
                            <div class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-600">
                                {{ $review->rating }}/5
                            </div>
                        </div>

                        <p class="mt-3 text-sm leading-relaxed text-gray-700">
                            {{ $review->comment ?: 'Pelanggan memberikan rating tanpa komentar.' }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center text-gray-500">
                Belum ada ulasan untuk produk ini.
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggle_review_editor');
            const cancelButton = document.getElementById('cancel_review_editor');
            const editorWrapper = document.getElementById('review_editor_form');

            if (!toggleButton || !editorWrapper) {
                return;
            }

            const setEditorState = (isOpen) => {
                editorWrapper.classList.toggle('hidden', !isOpen);
                toggleButton.textContent = isOpen ? 'Sembunyikan Form Edit' : 'Edit Ulasan';
            };

            setEditorState(!editorWrapper.classList.contains('hidden'));

            toggleButton.addEventListener('click', function() {
                setEditorState(editorWrapper.classList.contains('hidden'));
            });

            if (cancelButton) {
                cancelButton.addEventListener('click', function() {
                    setEditorState(false);
                });
            }
        });
    </script>
@endpush
