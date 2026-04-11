@extends('layouts.storefront')

@section('title', 'Profil Akun - Toko HS ELECTRIC')
@section('header_subtitle', 'Akun Saya')

@section('header_actions')
    <a href="{{ route('home') }}" class="ui-btn ui-btn-secondary">
        Katalog
    </a>
    <a href="{{ route('profile.addresses.index') }}" class="ui-btn ui-btn-soft">
        Kelola Alamat
    </a>
@endsection

@section('content')
    @php
        $status = session('status');
        $successMessage = null;

        if ($status === 'profile-updated') {
            $successMessage = 'Profil berhasil diperbarui.';
        } elseif ($status === 'password-updated') {
            $successMessage = 'Password berhasil diperbarui.';
        } elseif ($status === 'verification-link-sent') {
            $successMessage = 'Link verifikasi email baru sudah dikirim.';
        }
    @endphp

    <x-ui.page-header title="Profil Akun" subtitle="Kelola data akun, keamanan password, dan preferensi profil Anda." />

    @include('partials.flash-alerts', [
        'successMessage' => $successMessage,
        'showValidationErrors' => true,
    ])

    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
        @csrf
    </form>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="ui-card ui-card-pad">
            <h3 class="text-base font-extrabold text-slate-900">Informasi Profil</h3>
            <p class="mt-1 text-sm text-slate-600">Pastikan nama dan email aktif selalu terbaru untuk notifikasi pesanan.
            </p>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="ui-label">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                        autofocus autocomplete="name" class="ui-input">
                    @error('name')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="ui-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                        autocomplete="username" class="ui-input">
                    @error('email')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
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

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" class="ui-btn ui-btn-primary">
                        Simpan Profil
                    </button>
                </div>
            </form>
        </section>

        <div class="space-y-6">
            <section class="ui-card ui-card-pad">
                <h3 class="text-base font-extrabold text-slate-900">Keamanan Password</h3>
                <p class="mt-1 text-sm text-slate-600">Gunakan password panjang dan unik agar akun tetap aman.</p>

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
                        <input id="password" name="password" type="password" autocomplete="new-password" class="ui-input">
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

            <section class="ui-card ui-card-pad border border-red-200 bg-red-50/50">
                <h3 class="text-base font-extrabold text-red-800">Zona Bahaya</h3>
                <p class="mt-1 text-sm text-red-700">Penghapusan akun bersifat permanen dan tidak dapat dipulihkan.</p>

                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-5 space-y-3"
                    onsubmit="return confirm('Hapus akun secara permanen?');">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="delete_password" class="ui-label text-red-700">Konfirmasi Password</label>
                        <input id="delete_password" name="password" type="password"
                            class="ui-input border-red-200 focus:border-red-400 focus:ring-red-400"
                            placeholder="Masukkan password untuk konfirmasi">
                        @if ($errors->userDeletion->has('password'))
                            <p class="mt-1 text-xs font-semibold text-red-600">
                                {{ $errors->userDeletion->first('password') }}
                            </p>
                        @endif
                    </div>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                        Hapus Akun Permanen
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
