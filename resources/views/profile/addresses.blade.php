@extends('layouts.storefront')

@section('title', 'Manajemen Alamat - Toko Listrik Arip')
@section('header_subtitle', 'Alamat Pengiriman')

@section('header_actions')
    <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary">
        Kembali ke Profil
    </a>
    <a href="{{ route('home') }}" class="ui-btn ui-btn-soft">
        Katalog
    </a>
@endsection

@section('content')
    <x-ui.page-header title="Manajemen Alamat"
        subtitle="Simpan beberapa alamat pengiriman agar checkout lebih cepat dan rapi." />

    @include('partials.flash-alerts', [
        'successMessage' => session('success'),
        'showValidationErrors' => true,
    ])

    <div class="grid gap-6 xl:grid-cols-[1.1fr,1.4fr]">
        <section class="ui-card ui-card-pad">
            <h3 class="text-base font-extrabold text-slate-900">Tambah Alamat Baru</h3>
            <p class="mt-1 text-sm text-slate-600">Alamat baru bisa langsung dijadikan default untuk checkout berikutnya.</p>

            <form method="POST" action="{{ route('profile.addresses.store') }}" class="mt-5 space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="ui-label">Label Alamat</label>
                        <input type="text" name="label" value="{{ old('label') }}" class="ui-input"
                            placeholder="Rumah / Kantor / Gudang">
                    </div>
                    <div>
                        <label class="ui-label">Nama Penerima</label>
                        <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="ui-input"
                            required>
                    </div>
                    <div>
                        <label class="ui-label">Nomor HP</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="ui-input" required>
                    </div>
                    <div>
                        <label class="ui-label">Kode Pos</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="ui-input" required>
                    </div>
                    <div>
                        <label class="ui-label">Kota</label>
                        <input type="text" name="city" value="{{ old('city') }}" class="ui-input" required>
                    </div>
                    <div>
                        <label class="ui-label">Provinsi</label>
                        <input type="text" name="province" value="{{ old('province') }}" class="ui-input" required>
                    </div>
                </div>

                <div>
                    <label class="ui-label">Alamat Lengkap</label>
                    <textarea name="address_line" rows="3" class="ui-input" required>{{ old('address_line') }}</textarea>
                </div>

                <div>
                    <label class="ui-label">Catatan Alamat</label>
                    <textarea name="notes" rows="2" class="ui-input">{{ old('notes') }}</textarea>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="set_as_default" value="1" @checked(old('set_as_default'))
                        class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    Jadikan alamat default
                </label>

                <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">
                    Simpan Alamat
                </button>
            </form>
        </section>

        <section class="ui-card ui-card-pad">
            <h3 class="text-base font-extrabold text-slate-900">Daftar Alamat Tersimpan</h3>
            <p class="mt-1 text-sm text-slate-600">Edit cepat, ganti default, atau hapus alamat yang tidak dipakai.</p>

            <div class="mt-5 space-y-4">
                @forelse ($addresses as $address)
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-extrabold text-slate-900">
                                    {{ $address->label ?: 'Alamat Tanpa Label' }}
                                    @if ($address->is_default)
                                        <span
                                            class="ml-2 rounded-full bg-primary-100 px-2 py-1 text-[11px] font-bold uppercase text-primary-700">
                                            Default
                                        </span>
                                    @endif
                                </p>
                                <p class="text-sm text-slate-600">{{ $address->recipient_name }} - {{ $address->phone }}
                                </p>
                                <p class="text-sm text-slate-600">
                                    {{ $address->address_line }}, {{ $address->city }}, {{ $address->province }},
                                    {{ $address->postal_code }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if (!$address->is_default)
                                    <form method="POST" action="{{ route('profile.addresses.default', $address) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ui-btn ui-btn-soft px-3 py-1.5 text-xs">
                                            Jadikan Default
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('profile.addresses.destroy', $address) }}"
                                    onsubmit="return confirm('Hapus alamat ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('profile.addresses.update', $address) }}" class="grid gap-3">
                            @csrf
                            @method('PATCH')

                            <div class="grid gap-3 sm:grid-cols-2">
                                <input type="text" name="label" value="{{ $address->label }}" class="ui-input"
                                    placeholder="Label alamat">
                                <input type="text" name="recipient_name" value="{{ $address->recipient_name }}"
                                    class="ui-input" required>
                                <input type="text" name="phone" value="{{ $address->phone }}" class="ui-input"
                                    required>
                                <input type="text" name="postal_code" value="{{ $address->postal_code }}"
                                    class="ui-input" required>
                                <input type="text" name="city" value="{{ $address->city }}" class="ui-input"
                                    required>
                                <input type="text" name="province" value="{{ $address->province }}" class="ui-input"
                                    required>
                            </div>

                            <textarea name="address_line" rows="2" class="ui-input" required>{{ $address->address_line }}</textarea>
                            <textarea name="notes" rows="2" class="ui-input">{{ $address->notes }}</textarea>

                            <div>
                                <button type="submit" class="ui-btn ui-btn-secondary px-3 py-1.5 text-xs">
                                    Update Alamat
                                </button>
                            </div>
                        </form>
                    </article>
                @empty
                    <div class="ui-empty">
                        Belum ada alamat tersimpan di akun ini.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
