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

                        {{-- Animated Warranty Banner --}}
                        <div x-data="warrantyBanner()" x-init="start()"
                            class="hidden lg:flex flex-1 max-w-xl mx-6 relative overflow-hidden rounded-xl h-14">
                            {{-- Banner 1: Garansi Resmi --}}
                            <div x-show="active === 0"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="absolute inset-0 flex items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 px-5 py-2.5 cursor-pointer hover:shadow-lg transition-shadow"
                                @click="window.location.href='{{ route('home.warranty') }}'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex-shrink-0 rounded-lg bg-white/20 p-2 backdrop-blur-sm">
                                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-white/90 uppercase tracking-wider">Garansi Resmi</p>
                                        <p class="text-[11px] text-white/80 truncate">Klaim garansi produk elektronik hingga 7 hari</p>
                                    </div>
                                </div>
                                <span class="flex-shrink-0 rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur-sm hover:bg-white/30 transition">
                                    Klaim →
                                </span>
                            </div>

                            {{-- Banner 2: Proses Mudah --}}
                            <div x-show="active === 1"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="absolute inset-0 flex items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-500 to-violet-500 px-5 py-2.5 cursor-pointer hover:shadow-lg transition-shadow"
                                @click="window.location.href='{{ route('home.warranty') }}'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex-shrink-0 rounded-lg bg-white/20 p-2 backdrop-blur-sm">
                                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-white/90 uppercase tracking-wider">Proses Mudah</p>
                                        <p class="text-[11px] text-white/80 truncate">Upload bukti kerusakan, admin review dalam 48 jam</p>
                                    </div>
                                </div>
                                <span class="flex-shrink-0 rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur-sm hover:bg-white/30 transition">
                                    Info →
                                </span>
                            </div>

                            {{-- Banner Indicators --}}
                            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                <button @click="goTo(0)" :class="active === 0 ? 'bg-white w-4' : 'bg-white/40 w-1.5'"
                                    class="h-1.5 rounded-full transition-all duration-300"></button>
                                <button @click="goTo(1)" :class="active === 1 ? 'bg-white w-4' : 'bg-white/40 w-1.5'"
                                    class="h-1.5 rounded-full transition-all duration-300"></button>
                            </div>
                        </div>

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
    <script>
        function warrantyBanner() {
            return {
                active: 0,
                total: 2,
                interval: null,
                start() {
                    this.interval = setInterval(() => {
                        this.active = (this.active + 1) % this.total;
                    }, 4000);
                },
                goTo(index) {
                    this.active = index;
                    clearInterval(this.interval);
                    this.start();
                }
            };
        }
    </script>
</body>

</html>
