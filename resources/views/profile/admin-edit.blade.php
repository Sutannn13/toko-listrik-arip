@extends('layouts.storefront')

@section('title', 'Profil Admin - Toko HS ELECTRIC')
@section('header_subtitle', 'Akun Admin')

@section('header_actions')
    <a href="{{ route('admin.dashboard') }}" class="ui-btn ui-btn-secondary">
        Dashboard Admin
    </a>
    @if ($user->hasRole('super-admin'))
        <a href="{{ route('admin.settings.index') }}" class="ui-btn ui-btn-soft">
            Pengaturan
        </a>
    @endif
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
        $profileInitial = strtoupper(substr($user->name ?? 'A', 0, 1));

        if ($status === 'profile-updated') {
            $successMessage = 'Profil admin berhasil diperbarui.';
        } elseif ($status === 'password-updated') {
            $successMessage = 'Password berhasil diperbarui.';
        } elseif ($status === 'verification-link-sent') {
            $successMessage = 'Link verifikasi email baru sudah dikirim.';
        }
    @endphp

    <x-ui.page-header title="Profil Admin"
        subtitle="Halaman profil khusus admin untuk mengelola identitas akun, keamanan, dan akses panel admin." />

    @include('partials.flash-alerts', [
        'successMessage' => $successMessage,
        'showValidationErrors' => true,
    ])

    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
        @csrf
    </form>

    <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
        <section class="ui-card overflow-hidden">
            <div class="bg-gradient-to-r from-slate-900 via-cyan-900 to-teal-700 px-6 py-5 text-white">
                <h3 class="text-lg font-extrabold">Identitas Admin</h3>
                <p class="mt-1 text-sm text-cyan-100">Data ini digunakan untuk akses panel admin dan notifikasi internal.
                </p>
            </div>

            <div class="ui-card-pad">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="h-24 w-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                @if ($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $user->name }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <div
                                        class="grid h-full w-full place-items-center bg-gradient-to-br from-cyan-600 to-teal-500 text-3xl font-black text-white">
                                        {{ $profileInitial }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 space-y-2">
                                <label for="profile_photo" class="ui-label">Foto Profil Admin</label>
                                <input id="profile_photo" name="profile_photo" type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/webp" class="ui-input">
                                <p class="text-xs text-slate-500">Format: JPG, PNG, WEBP. Maksimal 4MB.</p>

                                @if ($user->profile_photo_path)
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                        <input type="checkbox" name="remove_profile_photo" value="1"
                                            @checked(old('remove_profile_photo'))
                                            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                        Hapus foto profil saat simpan perubahan
                                    </label>
                                @endif

                                @error('profile_photo')
                                    <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="ui-label">Nama Admin</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                                required autofocus autocomplete="name" class="ui-input">
                            @error('name')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="ui-label">Email Admin</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                                required autocomplete="username" class="ui-input">
                            @error('email')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-sm text-amber-800">
                                Email Anda belum terverifikasi.
                                <button form="send-verification" type="submit"
                                    class="font-semibold underline decoration-2 underline-offset-2 hover:text-amber-900">
                                    Kirim ulang link verifikasi
                                </button>
                            </p>
                        </div>
                    @endif

                    <button type="submit" class="ui-btn ui-btn-primary">
                        Simpan Profil Admin
                    </button>
                </form>
            </div>
        </section>

        <div class="space-y-6">
            <section class="ui-card ui-card-pad">
                <h3 class="text-base font-extrabold text-slate-900">Akses Cepat Admin</h3>
                <p class="mt-1 text-sm text-slate-600">Shortcut untuk tugas utama admin tanpa menu belanja user.</p>

                <div class="mt-4 grid gap-2">
                    <a href="{{ route('admin.products.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Kelola Produk
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Kelola Kategori
                    </a>
                    <a href="{{ route('admin.orders.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Kelola Pesanan
                    </a>
                    <a href="{{ route('admin.warranty-claims.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Monitoring Klaim Garansi
                    </a>
                    <a href="{{ route('admin.notifications.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Notifikasi Admin
                    </a>
                    @if ($user->hasRole('super-admin'))
                        <a href="{{ route('admin.settings.index') }}"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Pengaturan Sistem
                        </a>
                    @endif
                </div>
            </section>

            <section class="ui-card ui-card-pad">
                <h3 class="text-base font-extrabold text-slate-900">Keamanan Password</h3>
                <p class="mt-1 text-sm text-slate-600">Gunakan password kuat untuk menjaga keamanan panel admin.</p>

                <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="ui-label">Password Saat Ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                            class="ui-input">
                        @if ($errors->updatePassword->has('current_password'))
                            <p class="mt-1 text-xs font-semibold text-red-600">
                                {{ $errors->updatePassword->first('current_password') }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <label for="password" class="ui-label">Password Baru</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                            class="ui-input">
                        @if ($errors->updatePassword->has('password'))
                            <p class="mt-1 text-xs font-semibold text-red-600">
                                {{ $errors->updatePassword->first('password') }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <label for="password_confirmation" class="ui-label">Konfirmasi Password Baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            autocomplete="new-password" class="ui-input">
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <p class="mt-1 text-xs font-semibold text-red-600">
                                {{ $errors->updatePassword->first('password_confirmation') }}
                            </p>
                        @endif
                    </div>

                    <button type="submit" class="ui-btn ui-btn-primary">
                        Update Password
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
