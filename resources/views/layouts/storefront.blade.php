@php
    $metaTitle = trim($__env->yieldContent('title'));
    $pageTitle = $metaTitle !== '' ? $metaTitle : config('app.name', 'Toko Listrik Arip');

    $bodyClass = trim($__env->yieldContent('body_class'));
    $bodyClass =
        $bodyClass !== ''
            ? $bodyClass
            : 'min-h-screen bg-gray-50 font-sans text-gray-800 antialiased selection:bg-primary-500 selection:text-white';

    $headerClass = trim($__env->yieldContent('header_class'));
    $headerClass = $headerClass !== '' ? $headerClass : 'sticky top-0 z-30 border-b border-gray-200 bg-white shadow-sm';

    $mainContainerClass = trim($__env->yieldContent('main_container_class'));
    $mainContainerClass =
        $mainContainerClass !== '' ? $mainContainerClass : 'mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8';

    $headerSubtitle = trim($__env->yieldContent('header_subtitle'));
    $headerSubtitle = $headerSubtitle !== '' ? $headerSubtitle : 'Pasti Nyala, Pasti Murah';

    $footerClass = trim($__env->yieldContent('footer_class'));
    $footerClass = $footerClass !== '' ? $footerClass : 'mt-auto bg-gray-900 py-8 text-center text-gray-400';

    $showHeader = trim($__env->yieldContent('show_header')) !== 'off';
    $showFooter = trim($__env->yieldContent('show_footer')) !== 'off';
    $showDefaultStoreActions = trim($__env->yieldContent('show_default_store_actions')) !== 'off';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="{{ $bodyClass }}">
    @yield('background')

    <div class="relative z-10 flex min-h-screen flex-col">
        @if ($showHeader)
            @hasSection('header')
                @yield('header')
            @else
                <header class="{{ $headerClass }}">
                    <div
                        class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                        <a href="{{ route('home') }}"
                            class="flex items-center gap-3 transition-transform hover:scale-105">
                            <span
                                class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-sm font-extrabold text-white shadow-md shadow-primary-500/30">TA</span>
                            <div>
                                <p class="text-sm font-bold tracking-widest text-primary-600 uppercase">Toko Listrik
                                    Arip</p>
                                <p class="text-xs font-medium text-gray-500">{{ $headerSubtitle }}</p>
                            </div>
                        </a>

                        <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
                            @yield('header_actions')

                            @if ($showDefaultStoreActions)
                                @include('layouts.partials.storefront-actions')
                            @endif
                        </div>
                    </div>
                </header>
            @endif
        @endif

        <main class="{{ $mainContainerClass }}">
            @yield('content')
        </main>

        @if ($showFooter)
            @hasSection('footer')
                @yield('footer')
            @else
                <footer class="{{ $footerClass }}">
                    <div
                        class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <p class="text-sm">&copy; {{ date('Y') }} Toko Listrik Arip. Hak Cipta Dilindungi.</p>
                        <div class="flex gap-4">
                            <a href="#" class="hover:text-white transition">Tentang Kami</a>
                            <a href="#" class="hover:text-white transition">Syarat & Ketentuan</a>
                        </div>
                    </div>
                </footer>
            @endif
        @endif
    </div>

    @stack('scripts')
</body>

</html>
