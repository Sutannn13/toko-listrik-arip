<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manajemen Alamat
            </h2>
            <a href="{{ route('profile.edit') }}" class="text-sm font-semibold text-blue-600 hover:underline">
                &larr; Kembali ke Profile
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <section class="rounded-lg bg-white p-5 shadow sm:p-6">
                <h3 class="text-base font-bold text-gray-800">Tambah Alamat Baru</h3>
                <p class="mt-1 text-sm text-gray-600">Alamat ini akan tersimpan ke profil dan bisa dipilih saat
                    checkout.</p>

                <form method="POST" action="{{ route('profile.addresses.store') }}" class="mt-4 grid gap-4">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Label Alamat</label>
                            <input type="text" name="label" value="{{ old('label') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Contoh: Rumah / Toko">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Nama
                                Penerima</label>
                            <input type="text" name="recipient_name" value="{{ old('recipient_name') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Nomor HP</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Kode Pos</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Kota</label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Provinsi</label>
                            <input type="text" name="province" value="{{ old('province') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Alamat Lengkap</label>
                        <textarea name="address_line" rows="2"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('address_line') }}</textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Catatan Alamat</label>
                        <textarea name="notes" rows="2"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="set_as_default" value="1" @checked(old('set_as_default'))
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Jadikan alamat default
                    </label>

                    <div>
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Simpan Alamat
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg bg-white p-5 shadow sm:p-6">
                <h3 class="text-base font-bold text-gray-800">Daftar Alamat Tersimpan</h3>

                <div class="mt-4 space-y-4">
                    @forelse ($addresses as $address)
                        <article class="rounded-lg border border-gray-200 p-4">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $address->label ?: 'Alamat Tanpa Label' }}
                                        @if ($address->is_default)
                                            <span
                                                class="ml-2 rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-bold uppercase text-emerald-700">
                                                Default
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-600">{{ $address->recipient_name }} -
                                        {{ $address->phone }}</p>
                                    <p class="text-sm text-gray-600">
                                        {{ $address->address_line }}, {{ $address->city }}, {{ $address->province }},
                                        {{ $address->postal_code }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if (!$address->is_default)
                                        <form method="POST"
                                            action="{{ route('profile.addresses.default', $address) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                Jadikan Default
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('profile.addresses.destroy', $address) }}"
                                        onsubmit="return confirm('Hapus alamat ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('profile.addresses.update', $address) }}"
                                class="grid gap-3">
                                @csrf
                                @method('PATCH')

                                <div class="grid gap-3 md:grid-cols-2">
                                    <input type="text" name="label"
                                        value="{{ old('label.' . $address->id, $address->label) }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Label alamat">
                                    <input type="text" name="recipient_name"
                                        value="{{ old('recipient_name.' . $address->id, $address->recipient_name) }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <input type="text" name="phone"
                                        value="{{ old('phone.' . $address->id, $address->phone) }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <input type="text" name="postal_code"
                                        value="{{ old('postal_code.' . $address->id, $address->postal_code) }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <input type="text" name="city"
                                        value="{{ old('city.' . $address->id, $address->city) }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <input type="text" name="province"
                                        value="{{ old('province.' . $address->id, $address->province) }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                </div>

                                <textarea name="address_line" rows="2"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('address_line.' . $address->id, $address->address_line) }}</textarea>

                                <textarea name="notes" rows="2"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes.' . $address->id, $address->notes) }}</textarea>

                                <div>
                                    <button type="submit"
                                        class="rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                        Update Alamat
                                    </button>
                                </div>
                            </form>
                        </article>
                    @empty
                        <p class="text-sm text-gray-500 italic">Belum ada alamat tersimpan di akun ini.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
