@php
    $metaTitle = trim($__env->yieldContent('title'));
    $pageTitle = $metaTitle !== '' ? $metaTitle : \App\Models\Setting::get('store_name', 'Toko Listrik');

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
                        <a href="{{ route('home') }}" class="flex items-center transition-transform hover:scale-105">
                            <img src="{{ asset('img/gemini_generated_image.png') }}"
                                alt="{{ \App\Models\Setting::get('store_name', 'Toko') }}"
                                class="h-12 w-auto object-contain sm:h-14">
                        </a>

                        <div class="flex flex-1 lg:flex-none items-center justify-end gap-3 sm:gap-4">
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
                @include('layouts.partials.flowbite-footer', ['footerClass' => $footerClass])
            @endif
        @endif
    </div>

    @stack('scripts')
</body>

</html>
