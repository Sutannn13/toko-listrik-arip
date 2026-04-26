@extends('layouts.storefront')

@section('title', 'Profil Akun - Toko HS ELECTRIC')
@section('header_subtitle', 'Akun Saya')
@section('main_container_class', 'mx-auto w-full max-w-3xl px-4 py-5 pb-16 sm:px-6 sm:py-8 lg:px-8 lg:pb-8')

@section('header_actions')
    <a href="{{ route('home') }}" class="ui-btn ui-btn-secondary hidden lg:inline-flex">
        Katalog
    </a>
    <a href="{{ route('profile.addresses.index') }}" class="ui-btn ui-btn-soft hidden lg:inline-flex">
        Kelola Alamat
    </a>
@endsection

@section('content')
    @php
        $status = session('status');
        $successMessage = null;
        $normalizedPhotoPath = str_replace('\\', '/', (string) $user->profile_photo_path);
        $profilePhotoUrl =
            $normalizedPhotoPath !== '' &&
            (\Illuminate\Support\Facades\Storage::disk('local')->exists($normalizedPhotoPath) ||
                \Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPhotoPath))
            ? route('profile.photo', $user) . '?v=' . ($user->updated_at?->timestamp ?? now()->timestamp)
            : null;
        $profileInitial = strtoupper(substr($user->name ?? 'U', 0, 1));
        $addresses = $addresses ?? collect();
        $defaultAddress = $defaultAddress ?? null;

        if ($status === 'profile-updated') {
            $successMessage = 'Profil berhasil diperbarui.';
        } elseif ($status === 'password-updated') {
            $successMessage = 'Password berhasil diperbarui.';
        } elseif ($status === 'verification-link-sent') {
            $successMessage = 'Link verifikasi email baru sudah dikirim.';
        }
    @endphp

    @include('partials.flash-alerts', [
        'successMessage' => $successMessage,
        'showValidationErrors' => true,
    ])

    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
        @csrf
    </form>

    {{-- ════════════════════════════════════════
         HERO PROFILE CARD — Premium compact
         ════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 via-primary-500 to-emerald-500 p-5 sm:p-6 shadow-xl shadow-primary-500/15">
        {{-- Decorative --}}
        <div class="pointer-events-none absolute -right-8 -top-8 h-36 w-36 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-8 -left-8 h-28 w-28 rounded-full bg-white/8 blur-2xl"></div>

        <div class="relative flex items-center gap-4">
            {{-- Avatar --}}
            <div class="relative shrink-0">
                <div class="h-16 w-16 overflow-hidden rounded-2xl border-[3px] border-white/30 shadow-lg sm:h-20 sm:w-20">
                    @if ($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $user->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full w-full place-items-center bg-gradient-to-br from-white/30 to-white/10 text-3xl font-black text-white sm:text-4xl">
                            {{ $profileInitial }}
                        </div>
                    @endif
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-400 shadow"></span>
            </div>

            {{-- Name & email --}}
            <div class="flex-1 overflow-hidden">
                <h1 class="truncate text-lg font-extrabold text-white sm:text-xl">{{ $user->name }}</h1>
                <p class="mt-0.5 truncate text-sm text-green-100/80">{{ $user->email }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if ($user->email_verified_at)
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-white">
                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Terverifikasi
                        </span>
                    @endif
                    <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-white">
                        {{ $addresses->count() }} Alamat
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         QUICK ACCESS MENU — Separated from hero
         ════════════════════════════════════════ --}}
    <div class="mt-3 sm:mt-4 mx-0">
        <div class="grid grid-cols-4 gap-1.5 rounded-2xl border border-gray-100 bg-white p-3 shadow-lg shadow-gray-200/50 sm:gap-2 sm:p-4">
            <a href="{{ route('home.cart') }}"
                class="group flex flex-col items-center gap-1 rounded-xl p-2 text-center transition hover:bg-primary-50 active:scale-95">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-600 transition group-hover:bg-primary-200 sm:h-11 sm:w-11">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <span class="text-[10px] font-semibold text-gray-600 sm:text-[11px]">Keranjang</span>
            </a>

            <a href="{{ route('home.tracking') }}"
                class="group flex flex-col items-center gap-1 rounded-xl p-2 text-center transition hover:bg-orange-50 active:scale-95">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 transition group-hover:bg-orange-200 sm:h-11 sm:w-11">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
                <span class="text-[10px] font-semibold text-gray-600 sm:text-[11px]">Pesanan</span>
            </a>

            <a href="{{ route('profile.addresses.index') }}"
                class="group flex flex-col items-center gap-1 rounded-xl p-2 text-center transition hover:bg-blue-50 active:scale-95">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 transition group-hover:bg-blue-200 sm:h-11 sm:w-11">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                <span class="text-[10px] font-semibold text-gray-600 sm:text-[11px]">Alamat</span>
            </a>

            <a href="{{ route('home.notifications.index') }}"
                class="group flex flex-col items-center gap-1 rounded-xl p-2 text-center transition hover:bg-purple-50 active:scale-95">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 transition group-hover:bg-purple-200 sm:h-11 sm:w-11">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </span>
                <span class="text-[10px] font-semibold text-gray-600 sm:text-[11px]">Notifikasi</span>
            </a>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         MAIN CONTENT — Single column, clean
         ════════════════════════════════════════ --}}
    <div class="mt-5 space-y-4">

        {{-- ── Identitas Akun ── --}}
        <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100">
                    <svg class="h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Identitas Akun</h2>
                    <p class="text-xs text-gray-500">Nama, email & foto profil</p>
                </div>
            </div>

            <div class="p-5">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    {{-- Photo upload --}}
                    <div class="flex items-center gap-4 rounded-xl border border-gray-100 bg-gray-50/50 p-3.5">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2 border-white shadow-md">
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $user->name }}"
                                    class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center bg-gradient-to-br from-primary-600 to-emerald-500 text-xl font-black text-white">
                                    {{ $profileInitial }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="mb-1.5 text-xs font-semibold text-gray-700">Foto Profil</p>
                            <input id="profile_photo" name="profile_photo" type="file"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-primary-700 file:cursor-pointer file:transition">
                            <p class="mt-1 text-[10px] text-gray-400">JPG, PNG, WEBP · Maks. 4MB</p>
                            @if ($user->profile_photo_path)
                                <label class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] text-gray-500 cursor-pointer">
                                    <input type="checkbox" name="remove_profile_photo" value="1"
                                        @checked(old('remove_profile_photo'))
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    Hapus foto profil
                                </label>
                            @endif
                            @error('profile_photo')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Name & Email --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-semibold text-gray-600">Nama Lengkap</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                                required autofocus autocomplete="name"
                                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @error('name')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold text-gray-600">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                                required autocomplete="username"
                                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @error('email')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-xs text-amber-800">
                                Email belum terverifikasi.
                                <button form="send-verification" type="submit"
                                    class="font-semibold underline underline-offset-2 hover:text-amber-900">
                                    Kirim ulang
                                </button>
                            </p>
                        </div>
                    @endif

                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 py-2.5 text-sm font-bold text-white transition hover:bg-primary-700 active:scale-[0.98] shadow-sm shadow-primary-500/20">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Profil
                    </button>
                </form>

                {{-- ── Keamanan Password (sub-section inside identity) ── --}}
                <div x-data="{ passwordOpen: false }" class="mt-5 rounded-xl border border-gray-100 bg-gray-50/50">
                    <button type="button" @click="passwordOpen = !passwordOpen"
                        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-gray-100/60 rounded-xl">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                                <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-xs font-bold text-gray-800">Keamanan Password</h3>
                                <p class="text-[10px] text-gray-500">Ganti password secara berkala</p>
                            </div>
                        </div>
                        <svg class="h-4 w-4 text-gray-400 transition" :class="passwordOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="passwordOpen" x-cloak x-collapse class="px-4 pb-4">
                        <form method="POST" action="{{ route('password.update') }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="current_password" class="mb-1.5 block text-xs font-semibold text-gray-600">Password Saat Ini</label>
                                <input id="current_password" name="current_password" type="password"
                                    autocomplete="current-password" placeholder="••••••••"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                @if ($errors->updatePassword->has('current_password'))
                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        {{ $errors->updatePassword->first('current_password') }}
                                    </p>
                                @endif
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="password" class="mb-1.5 block text-xs font-semibold text-gray-600">Password Baru</label>
                                    <input id="password" name="password" type="password"
                                        autocomplete="new-password" placeholder="Min. 8 karakter"
                                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                    @if ($errors->updatePassword->has('password'))
                                        <p class="mt-1 text-xs font-semibold text-red-600">
                                            {{ $errors->updatePassword->first('password') }}
                                        </p>
                                    @endif
                                </div>

                                <div>
                                    <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold text-gray-600">Konfirmasi Password</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password"
                                        autocomplete="new-password" placeholder="Ulangi password"
                                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                    @if ($errors->updatePassword->has('password_confirmation'))
                                        <p class="mt-1 text-xs font-semibold text-red-600">
                                            {{ $errors->updatePassword->first('password_confirmation') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600 active:scale-[0.98]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Alamat Pengiriman ── --}}
        <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100">
                        <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Alamat Pengiriman</h2>
                        <p class="text-xs text-gray-500">{{ $addresses->count() }} alamat tersimpan</p>
                    </div>
                </div>
                <a href="{{ route('profile.addresses.index') }}"
                    class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100">
                    Kelola
                </a>
            </div>

            <div class="p-5">
                @if ($defaultAddress)
                    <div class="rounded-xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50/50 p-4">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="rounded-full bg-green-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Default</span>
                            <span class="text-[11px] text-green-700">Dipakai saat checkout</span>
                        </div>
                        <p class="text-sm font-bold text-gray-900">{{ $defaultAddress->label ?: 'Alamat Utama' }}</p>
                        <p class="mt-0.5 text-xs text-gray-600">{{ $defaultAddress->recipient_name }} · {{ $defaultAddress->phone }}</p>
                        <p class="mt-0.5 text-xs text-gray-600">{{ $defaultAddress->address_line }}</p>
                        <p class="mt-0.5 text-xs text-gray-600">{{ $defaultAddress->city }}, {{ $defaultAddress->province }} {{ $defaultAddress->postal_code }}</p>
                    </div>
                @else
                    <div class="flex flex-col items-center rounded-xl border-2 border-dashed border-gray-200 px-4 py-6 text-center">
                        <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="mt-2 text-sm font-semibold text-gray-600">Belum ada alamat</p>
                        <p class="mt-0.5 text-xs text-gray-400">Checkout lebih cepat dengan alamat tersimpan</p>
                        <a href="{{ route('profile.addresses.index') }}"
                            class="mt-3 rounded-lg bg-primary-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-primary-700">
                            + Tambah Alamat
                        </a>
                    </div>
                @endif

                {{-- Other addresses --}}
                @if ($addresses->count() > 1)
                    <div class="mt-3 space-y-1.5">
                        @foreach ($addresses->where('is_default', false)->take(3) as $address)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/50 px-3.5 py-2.5 transition hover:bg-white">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-800">{{ $address->label ?: 'Alamat' }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $address->recipient_name }} · {{ $address->city }}</p>
                                </div>
                                <form method="POST" action="{{ route('profile.addresses.default', $address) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="shrink-0 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700">
                                        Set Default
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>


        {{-- ── Aktivitas Akun ── --}}
        <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-sm font-bold text-gray-900">Aktivitas Akun</h2>
            </div>
            <div class="divide-y divide-gray-50">
                <a href="{{ route('home.transactions') }}"
                    class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50 active:bg-gray-100">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-100">
                        <svg class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                    <span class="flex-1 text-sm font-medium text-gray-800">Riwayat Transaksi</span>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('home.tracking') }}"
                    class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50 active:bg-gray-100">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </span>
                    <span class="flex-1 text-sm font-medium text-gray-800">Lacak Pesanan</span>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('home.warranty-claims.index') }}"
                    class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50 active:bg-gray-100">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100">
                        <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <span class="flex-1 text-sm font-medium text-gray-800">Klaim Garansi</span>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('home.notifications.index') }}"
                    class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50 active:bg-gray-100">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-100">
                        <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </span>
                    <span class="flex-1 text-sm font-medium text-gray-800">Semua Notifikasi</span>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-3 px-5 py-3 text-left transition hover:bg-red-50 active:bg-red-100">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100">
                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </span>
                        <span class="flex-1 text-sm font-medium text-red-600">Keluar dari Akun</span>
                        <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            </div>
        </section>

        {{-- ── Zona Bahaya — Collapsible ── --}}
        <section x-data="{ dangerOpen: false }" class="overflow-hidden rounded-2xl border border-red-200/60 bg-white shadow-sm">
            <button type="button" @click="dangerOpen = !dangerOpen"
                class="flex w-full items-center justify-between gap-3 px-5 py-3.5 text-left transition hover:bg-red-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100">
                        <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-red-800">Zona Bahaya</h2>
                        <p class="text-xs text-red-500">Hapus akun permanen</p>
                    </div>
                </div>
                <svg class="h-4 w-4 text-red-400 transition" :class="dangerOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="dangerOpen" x-cloak x-collapse>
                <div class="border-t border-red-100 bg-red-50/50 p-5">
                    <p class="mb-4 text-xs text-red-700 leading-relaxed">Penghapusan akun bersifat permanen. Semua data pesanan, alamat, dan riwayat akan dihapus selamanya dan tidak dapat dipulihkan.</p>
                    <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-3"
                        onsubmit="return confirm('PERINGATAN: Akun Anda akan dihapus secara PERMANEN.\n\nSemua data pesanan, alamat, dan riwayat transaksi akan hilang selamanya.\n\nYakin ingin melanjutkan?');">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="delete_password" class="mb-1.5 block text-xs font-semibold text-red-700">Konfirmasi Password</label>
                            <input id="delete_password" name="password" type="password"
                                class="w-full rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-400/20"
                                placeholder="Masukkan password untuk konfirmasi">
                            @if ($errors->userDeletion->has('password'))
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $errors->userDeletion->first('password') }}
                                </p>
                            @endif
                        </div>

                        <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 py-2.5 text-sm font-bold text-white transition hover:bg-red-700 active:scale-[0.98]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Akun Permanen
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
