@extends('layouts.admin')

@section('title', 'User Management')
@section('header', 'User Management')

@section('content')
    {{-- Stats Cards --}}
    <div class="mb-6 grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-dark-border dark:bg-dark-card">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total User</p>
            <p class="mt-2 text-3xl font-black text-gray-800 dark:text-white">{{ number_format($counts['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm dark:border-green-500/20 dark:bg-green-500/10">
            <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Aktif</p>
            <p class="mt-2 text-3xl font-black text-green-700 dark:text-green-400">{{ number_format($counts['active']) }}</p>
        </div>
        <div class="rounded-2xl border border-error-200 bg-error-50 p-5 shadow-sm dark:border-error-500/20 dark:bg-error-500/10">
            <p class="text-xs font-semibold uppercase tracking-wider text-error-600 dark:text-error-400">Disuspend</p>
            <p class="mt-2 text-3xl font-black text-error-700 dark:text-error-400">{{ number_format($counts['suspended']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.users.index') }}"
        class="mb-6 flex flex-wrap gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-dark-border dark:bg-dark-card">
        <input type="text" name="search" value="{{ request('search') }}"
            class="flex-1 min-w-[200px] rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
            placeholder="Cari nama atau email...">

        <select name="role"
            class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white">
            <option value="">Semua Role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                    {{ Str::headline(str_replace('-', ' ', $role->name)) }}
                </option>
            @endforeach
        </select>

        <select name="status"
            class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white">
            <option value="">Semua Status</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Disuspend</option>
        </select>

        <button type="submit"
            class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-600">
            Terapkan
        </button>
        <a href="{{ route('admin.users.index') }}"
            class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300">
            Reset
        </a>
    </form>

    {{-- User Table --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-dark-border dark:bg-dark-card overflow-hidden"
        x-data="{ activeModal: null, modalUser: null, resetUserId: null, resetUserName: '' }">

        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-dark-border dark:bg-dark-hover">
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">User</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Bergabung</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                @forelse ($users as $user)
                    @php
                        $isSelf = $user->id === auth()->id();
                        $isSuperAdmin = $user->hasRole('super-admin');
                        $primaryRole = $user->primaryRole();
                        $roleColors = [
                            'super-admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                            'admin'       => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                            'user'        => 'bg-gray-100 text-gray-600 dark:bg-dark-hover dark:text-gray-400',
                        ];
                        $roleColor = $roleColors[$primaryRole] ?? $roleColors['user'];
                    @endphp
                    <tr class="group transition hover:bg-gray-50/50 dark:hover:bg-dark-hover/50">
                        {{-- User Info --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-xs font-bold text-white">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                        {{ $user->name }}
                                        @if ($isSelf)
                                            <span class="ml-1 rounded bg-brand-100 px-1.5 py-0.5 text-[10px] font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">KAMU</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Role --}}
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $roleColor }}">
                                {{ Str::headline(str_replace('-', ' ', $primaryRole)) }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4">
                            @if ($user->is_suspended)
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-100 px-2.5 py-1 text-xs font-bold text-error-700 dark:bg-error-500/10 dark:text-error-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-error-500"></span>
                                    Disuspend
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-100 px-2.5 py-1 text-xs font-bold text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-success-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            @endif
                        </td>

                        {{-- Bergabung --}}
                        <td class="px-5 py-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">

                                @if (! $isSelf)
                                    {{-- Ubah Role --}}
                                    <button type="button"
                                        @click="activeModal = 'role-{{ $user->id }}'"
                                        class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400"
                                        title="Ubah Role">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </button>

                                    {{-- Suspend / Unsuspend --}}
                                    @if (! $isSuperAdmin)
                                        @if ($user->is_suspended)
                                            <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-lg border border-gray-200 bg-white p-1.5 text-success-600 transition hover:border-success-300 hover:bg-success-50 dark:border-dark-border dark:bg-dark-card"
                                                    title="Aktifkan Kembali">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                @click="activeModal = 'suspend-{{ $user->id }}'"
                                                class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-error-300 hover:bg-error-50 hover:text-error-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400"
                                                title="Suspend Akun">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        @endif
                                    @endif

                                    {{-- Reset Password --}}
                                    <button type="button"
                                        @click="activeModal = 'reset-{{ $user->id }}'"
                                        class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition hover:border-warning-300 hover:bg-warning-50 hover:text-warning-600 dark:border-dark-border dark:bg-dark-card dark:text-gray-400"
                                        title="Reset Password">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic px-1">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- ── MODAL: Ubah Role ── --}}
                    <template x-if="activeModal === 'role-{{ $user->id }}'">
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
                            @click.self="activeModal = null">
                            <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-dark-border dark:bg-dark-card">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Ubah Role</h3>
                                    <button @click="activeModal = null" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                    Mengubah role untuk <strong class="text-gray-800 dark:text-white">{{ $user->name }}</strong>.
                                </p>
                                <form method="POST" action="{{ route('admin.users.update-role', $user) }}">
                                    @csrf @method('PATCH')
                                    <div class="mb-4 space-y-2">
                                        @foreach ($roles as $role)
                                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-gray-200 px-4 py-3 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-dark-border dark:has-[:checked]:bg-brand-500/10">
                                                <input type="radio" name="role" value="{{ $role->name }}"
                                                    class="text-brand-500 focus:ring-brand-500"
                                                    @if($primaryRole === $role->name) checked @endif>
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ Str::headline(str_replace('-', ' ', $role->name)) }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button type="button" @click="activeModal = null"
                                            class="rounded-xl border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="rounded-xl bg-brand-500 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-600">
                                            Simpan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>

                    {{-- ── MODAL: Suspend ── --}}
                    <template x-if="activeModal === 'suspend-{{ $user->id }}'">
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
                            @click.self="activeModal = null">
                            <div class="w-full max w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-dark-border dark:bg-dark-card">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Suspend Akun</h3>
                                    <button @click="activeModal = null" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                    Suspend akun <strong class="text-gray-800 dark:text-white">{{ $user->name }}</strong>?
                                    User tidak akan bisa login setelah disuspend.
                                </p>
                                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Alasan Suspend (opsional)</label>
                                        <textarea name="suspended_reason" rows="3"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-error-400 focus:outline-none focus:ring-2 focus:ring-error-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                                            placeholder="Contoh: Melanggar ketentuan penggunaan..."></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button type="button" @click="activeModal = null"
                                            class="rounded-xl border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="rounded-xl bg-error-500 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-error-600">
                                            Suspend Sekarang
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>

                    {{-- ── MODAL: Reset Password ── --}}
                    <template x-if="activeModal === 'reset-{{ $user->id }}'">
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
                            @click.self="activeModal = null">
                            <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-dark-border dark:bg-dark-card"
                                x-data="{ showPw: false }">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Reset Password</h3>
                                    <button @click="activeModal = null" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                    Set password baru untuk <strong class="text-gray-800 dark:text-white">{{ $user->name }}</strong>.
                                </p>
                                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Password Baru</label>
                                        <div class="relative">
                                            <input :type="showPw ? 'text' : 'password'" name="new_password"
                                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                                                placeholder="Min. 8 karakter" required>
                                            <button type="button" @click="showPw = !showPw"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        x-show="!showPw"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        x-show="showPw" x-cloak
                                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Konfirmasi Password</label>
                                        <input :type="showPw ? 'text' : 'password'" name="new_password_confirmation"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white"
                                            placeholder="Ulangi password" required>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button type="button" @click="activeModal = null"
                                            class="rounded-xl border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-dark-border dark:bg-dark-card dark:text-gray-300">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="rounded-xl bg-warning-500 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-warning-600">
                                            Reset Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada user ditemukan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-dark-border">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
