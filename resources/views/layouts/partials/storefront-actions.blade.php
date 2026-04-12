@guest
    <a href="{{ route('login') }}"
        class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
        Masuk
    </a>

    @if (Route::has('register'))
        <a href="{{ route('register') }}"
            class="hidden sm:inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-primary-500/20 transition hover:bg-primary-700">
            Daftar
        </a>
    @endif
@endguest

@auth
    @php
        $authUser = Auth::user();
        $isAdminUser = $authUser->hasAnyRole(['super-admin', 'admin']);
        $notificationsTableExists = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $normalizedPhotoPath = str_replace('\\', '/', (string) $authUser->profile_photo_path);
        $profilePhotoUrl =
            $normalizedPhotoPath !== '' &&
            \Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedPhotoPath)
                ? route('profile.photo', $authUser) . '?v=' . ($authUser->updated_at?->timestamp ?? now()->timestamp)
                : null;
        $emailHandle = '@' . \Illuminate\Support\Str::before((string) $authUser->email, '@');

        $userUnreadNotificationCount = 0;
        $userNotificationPreviews = collect();

        if (!$isAdminUser && $notificationsTableExists) {
            $userUnreadNotificationCount = $authUser->unreadNotifications()->count();

            $userNotificationPreviews = $authUser
                ->notifications()
                ->latest()
                ->limit(6)
                ->get()
                ->map(function ($notification) {
                    $payload = is_array($notification->data) ? $notification->data : [];
                    $title = trim((string) ($payload['title'] ?? 'Pembaruan akun'));
                    $message = trim((string) ($payload['message'] ?? 'Ada notifikasi baru untuk Anda.'));
                    $route = trim((string) ($payload['route'] ?? route('home.notifications.index')));

                    if ($route === '') {
                        $route = route('home.notifications.index');
                    }

                    return [
                        'open_route' => route('home.notifications.open', ['notification' => $notification->id]),
                        'title' => $title !== '' ? $title : 'Pembaruan akun',
                        'message' => \Illuminate\Support\Str::limit($message, 120),
                        'route' => $route,
                        'time' => optional($notification->created_at)->diffForHumans() ?? '-',
                        'is_unread' => $notification->read_at === null,
                    ];
                });
        }
    @endphp

    @if (!$isAdminUser)
        <a href="{{ route('home.cart') }}"
            class="relative rounded-lg p-2 transition {{ ($cartQuantity ?? 0) > 0 ? 'bg-primary-50 text-primary-600' : 'text-gray-500 hover:bg-gray-100 hover:text-primary-600' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            @if (($cartQuantity ?? 0) > 0)
                <span
                    class="absolute top-0 right-0 grid h-4 w-4 -translate-y-1/4 translate-x-1/4 place-items-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $cartQuantity }}</span>
            @endif
        </a>

        <div x-data="{ notificationOpen: false }" class="relative">
            <button x-on:click="notificationOpen = !notificationOpen" x-on:keydown.escape.window="notificationOpen = false"
                class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-600"
                aria-label="Notifikasi akun" x-bind:aria-expanded="notificationOpen.toString()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                @if ($userUnreadNotificationCount > 0)
                    <span
                        class="absolute top-0 right-0 grid h-4 w-4 -translate-y-1/4 translate-x-1/4 place-items-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                        {{ $userUnreadNotificationCount > 9 ? '9+' : $userUnreadNotificationCount }}
                    </span>
                @endif
            </button>

            <div x-cloak x-show="notificationOpen" x-on:click.away="notificationOpen = false"
                x-transition:enter="ease-out duration-150" x-transition:enter-start="-translate-y-1 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl shadow-gray-200/70">
                <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2.5">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Notifikasi</p>
                    @if ($userUnreadNotificationCount > 0)
                        <span
                            class="inline-flex rounded bg-primary-100 px-2 py-0.5 text-[10px] font-semibold text-primary-700">
                            {{ $userUnreadNotificationCount }} baru
                        </span>
                    @endif
                </div>

                <div class="max-h-80 overflow-y-auto">
                    @if (!$notificationsTableExists)
                        <p class="px-4 py-6 text-center text-xs text-gray-400">Fitur notifikasi belum tersedia.</p>
                    @else
                        @forelse ($userNotificationPreviews as $preview)
                            <a href="{{ $preview['open_route'] }}"
                                class="block border-b border-gray-100/80 px-4 py-3 transition hover:bg-gray-50">
                                <div class="flex items-start gap-2">
                                    <span
                                        class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $preview['is_unread'] ? 'bg-primary-500' : 'bg-gray-300' }}"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-xs font-semibold text-gray-800">{{ $preview['title'] }}</p>
                                            <p class="shrink-0 text-[10px] text-gray-400">{{ $preview['time'] }}</p>
                                        </div>
                                        <p class="mt-1 text-[11px] leading-relaxed text-gray-600">{{ $preview['message'] }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-center text-xs text-gray-400">Belum ada notifikasi baru.</p>
                        @endforelse
                    @endif
                </div>

                <div class="border-t border-gray-100">
                    <a href="{{ route('home.notifications.index') }}"
                        class="block px-4 py-2.5 text-center text-xs font-semibold text-primary-700 transition hover:bg-gray-50">
                        Lihat Semua Notifikasi
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div x-data="{ dropdownOpen: false }" class="relative">
        <button x-on:click="dropdownOpen = !dropdownOpen" x-on:keydown.escape.window="dropdownOpen = false"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            aria-label="Menu akun" x-bind:aria-expanded="dropdownOpen.toString()">
            <div class="h-8 w-8 overflow-hidden rounded-full bg-primary-100 text-center leading-8 text-primary-700">
                @if ($profilePhotoUrl)
                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $authUser->name }}"
                        class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr($authUser->name, 0, 1)) }}
                @endif
            </div>
            <span class="hidden text-left leading-tight sm:flex sm:flex-col">
                <span class="max-w-[140px] truncate">{{ $authUser->name }}</span>
                <span class="text-xs font-normal text-gray-500">{{ $emailHandle }}</span>
            </span>
            <svg class="h-4 w-4 text-gray-500 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                x-bind:class="dropdownOpen ? 'rotate-180' : ''">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-cloak x-show="dropdownOpen" x-on:click.away="dropdownOpen = false" x-transition:enter="ease-out duration-150"
            x-transition:enter-start="-translate-y-1 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl shadow-gray-200/70">
            <div class="px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-500">
                {{ $isAdminUser ? 'Admin Account' : 'My Account' }}
            </div>

            <div class="mx-2 h-px bg-gray-100"></div>

            <a href="{{ route('profile.edit') }}" x-on:click="dropdownOpen = false"
                class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5.121 17.804A4 4 0 018 16h8a4 4 0 012.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $isAdminUser ? 'Profil Admin' : 'Profil Saya' }}
            </a>

            @if ($isAdminUser)
                <a href="{{ route('admin.dashboard') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10h4v-6h6v6h4V10" />
                    </svg>
                    Admin Panel
                </a>

                <a href="{{ route('admin.products.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                    </svg>
                    Kelola Produk
                </a>

                <a href="{{ route('admin.categories.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    Kelola Kategori
                </a>

                <a href="{{ route('admin.orders.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v4H9z" />
                    </svg>
                    Kelola Pesanan
                </a>

                <a href="{{ route('admin.warranty-claims.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Klaim Garansi
                </a>

                <a href="{{ route('admin.notifications.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notifikasi Admin
                </a>

                @if ($authUser->hasRole('super-admin'))
                    <a href="{{ route('admin.settings.index') }}" x-on:click="dropdownOpen = false"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.325 4.317a1 1 0 011.35-.936l.6.26a1 1 0 00.8 0l.6-.26a1 1 0 011.35.936l.06.65a1 1 0 00.55.8l.56.33a1 1 0 01.35 1.36l-.33.56a1 1 0 000 .8l.33.56a1 1 0 01-.35 1.36l-.56.33a1 1 0 00-.55.8l-.06.65a1 1 0 01-1.35.936l-.6-.26a1 1 0 00-.8 0l-.6.26a1 1 0 01-1.35-.936l-.06-.65a1 1 0 00-.55-.8l-.56-.33a1 1 0 01-.35-1.36l.33-.56a1 1 0 000-.8l-.33-.56a1 1 0 01.35-1.36l.56-.33a1 1 0 00.55-.8l.06-.65z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a3 3 0 110-6 3 3 0 010 6z" />
                        </svg>
                        Pengaturan Toko
                    </a>
                @endif
            @else
                <a href="{{ route('profile.addresses.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Alamat Pengiriman
                </a>

                <a href="{{ route('home.transactions') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v4H9z" />
                    </svg>
                    Riwayat Transaksi
                </a>

                <a href="{{ route('home.tracking') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h5" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5 3h14a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z" />
                    </svg>
                    Cek Pesanan
                </a>

                <a href="{{ route('home.warranty') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Garansi Saya
                </a>

                <a href="{{ route('home.notifications.index') }}" x-on:click="dropdownOpen = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notifikasi
                </a>
            @endif

            <div class="mx-2 my-1 h-px bg-gray-100"></div>

            <form method="POST" action="{{ route('logout') }}" class="p-2">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3" />
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
@endauth
