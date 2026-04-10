@auth
    <a href="{{ route('home.tracking') }}"
        class="hidden rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary-500 hover:text-primary-600 sm:block">
        Cek Pesanan
    </a>

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

    <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>
@endauth

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
    @if (Auth::user()->hasAnyRole(['super-admin', 'admin']))
        <a href="{{ route('admin.dashboard') }}"
            class="hidden rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 sm:inline-flex">
            Admin Panel
        </a>
    @endif

    <a href="{{ route('profile.edit') }}"
        class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:flex">
        <div class="h-5 w-5 overflow-hidden rounded-full bg-primary-100 text-center leading-5 text-primary-700">
            {{ substr(Auth::user()->name, 0, 1) }}
        </div>
        {{ Auth::user()->name }}
    </a>

    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
        @csrf
        <button type="submit"
            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100">
            Logout
        </button>
    </form>
@endauth
