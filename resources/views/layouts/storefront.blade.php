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

    {{-- ═══════════════════════════════════════════════════════
         MOBILE BOTTOM NAVIGATION — Tokopedia-style sticky nav
         Hides automatically when AI chat panel is open via
         'chat-panel-opened' / 'chat-panel-closed' custom events.
         ═══════════════════════════════════════════════════════ --}}
    <nav x-data="{ chatOpen: false }"
        x-on:chat-panel-opened.window="chatOpen = true"
        x-on:chat-panel-closed.window="chatOpen = false"
        x-show="!chatOpen"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="fixed inset-x-0 bottom-0 z-dropdown border-t border-gray-200 bg-white/95 backdrop-blur-md lg:hidden"
        id="mobile-bottom-nav">
        <div class="mx-auto flex max-w-lg items-center justify-around px-2 py-1.5">
            {{-- Home --}}
            <a href="{{ route('home') }}"
                class="flex flex-col items-center gap-0.5 px-3 py-1 transition {{ request()->routeIs('home') && !request()->routeIs('home.cart') && !request()->routeIs('home.tracking') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="{{ request()->routeIs('home') && !request()->routeIs('home.cart') && !request()->routeIs('home.tracking') ? '2.2' : '1.8' }}">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] font-semibold">Home</span>
            </a>

            {{-- Kategori --}}
            <a href="{{ route('home') }}#catalog-section"
                class="flex flex-col items-center gap-0.5 px-3 py-1 text-gray-500 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="text-[10px] font-semibold">Kategori</span>
            </a>

            {{-- Keranjang --}}
            @auth
                <a href="{{ route('home.cart') }}"
                    class="relative flex flex-col items-center gap-0.5 px-3 py-1 transition {{ request()->routeIs('home.cart') ? 'text-primary-600' : 'text-gray-500' }}">
                    <span class="relative">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="{{ request()->routeIs('home.cart') ? '2.2' : '1.8' }}">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        @if (($cartQuantity ?? 0) > 0)
                            <span
                                class="absolute -top-1.5 -right-2 grid h-4 w-4 place-items-center rounded-full bg-red-500 text-[9px] font-bold text-white">{{ $cartQuantity > 9 ? '9+' : $cartQuantity }}</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-semibold">Keranjang</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="relative flex flex-col items-center gap-0.5 px-3 py-1 text-gray-500 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="text-[10px] font-semibold">Keranjang</span>
                </a>
            @endauth

            {{-- Chat / AI --}}
            <button type="button" onclick="document.querySelector('[data-ai-trigger]')?.click()"
                class="flex flex-col items-center gap-0.5 px-3 py-1 text-gray-500 transition">
                <span class="relative">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="absolute -top-0.5 -right-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                </span>
                <span class="text-[10px] font-semibold">Chat</span>
            </button>

            {{-- Akun --}}
            @auth
                <a href="{{ route('profile.edit') }}"
                    class="flex flex-col items-center gap-0.5 px-3 py-1 text-gray-500 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-[10px] font-semibold">Akun</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="flex flex-col items-center gap-0.5 px-3 py-1 text-gray-500 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-[10px] font-semibold">Masuk</span>
                </a>
            @endauth
        </div>
    </nav>

    <div x-data="storefrontAiAssistant({ chatEndpoint: @js(route('api.ai.chat', [], false)), feedbackEndpoint: @js(route('api.ai.feedback', [], false)) })" x-init="init()"
        x-on:header-panel-opened.window="closePanel()"
        class="fixed bottom-20 right-4 z-[90] sm:bottom-6 sm:right-6 lg:bottom-6">
        {{-- Mobile Backdrop — shown when chat is open on small screens --}}
        <div x-show="isOpen" x-cloak
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="closePanel()"
            class="fixed inset-0 z-[-1] bg-black/40 backdrop-blur-sm sm:hidden"
            aria-hidden="true"></div>
        {{-- Floating Trigger Button — Clean & Professional --}}
        <button x-show="!isOpen" x-cloak @click="openPanel()"
            class="group hidden lg:inline-flex items-center gap-2.5 rounded-full bg-white px-4 py-3 text-sm font-semibold text-gray-800 shadow-lg shadow-gray-900/10 ring-1 ring-gray-200 transition hover:shadow-xl hover:ring-primary-300 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/40"
            type="button" aria-label="Buka asisten toko" data-ai-trigger>
            <span
                class="relative inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span
                    class="absolute -top-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white"></span>
            </span>
            <span class="hidden sm:inline">Bantuan</span>
        </button>

        {{-- Chat Panel — Full-screen on mobile, floating card on tablet/desktop --}}
        <section x-show="isOpen" x-cloak x-transition:enter="transition duration-250 ease-out"
            x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-180 ease-in" x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-2 opacity-0"
            class="flex flex-col overflow-hidden bg-white shadow-2xl shadow-gray-900/15 ring-1 ring-gray-200
                   fixed inset-0 z-[91] rounded-none
                   sm:relative sm:inset-auto sm:z-auto sm:h-[min(76vh,620px)] sm:w-[min(94vw,390px)] sm:rounded-2xl">
            {{-- Header --}}
            <header class="relative border-b border-gray-100 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="relative flex h-9 w-9 items-center justify-center rounded-full bg-primary-600 text-white">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-emerald-400 ring-2 ring-white"></span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">HS Electric</p>
                            <p class="text-[11px] text-emerald-600 font-medium">Online — siap bantu</p>
                        </div>
                    </div>
                    <button @click="closePanel()" type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Tutup panel asisten">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>

            {{-- Quick Actions --}}
            <div class="border-b border-gray-100 bg-gray-50/50 px-3 py-2.5">
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="action in quickActions" :key="action.label">
                        <button type="button" @click="sendQuickAction(action.prompt)"
                            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-[11px] font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                            x-text="action.label"></button>
                    </template>
                </div>
            </div>

            {{-- Messages Area --}}
            <div x-ref="messageViewport" class="flex-1 space-y-3 overflow-y-auto bg-gray-50/30 px-3 py-3">
                <template x-for="message in messages" :key="message.id">
                    <article>
                        <div :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            class="flex gap-2">
                            {{-- Avatar for assistant --}}
                            <div x-show="message.role === 'assistant'" class="shrink-0 mt-1">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div
                                :class="message.role === 'user' ?
                                    'max-w-[80%] rounded-2xl rounded-br-sm bg-primary-600 px-3.5 py-2.5 text-sm text-white' :
                                    'max-w-[85%] rounded-2xl rounded-bl-sm border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-800 shadow-sm'">
                                <p x-show="!message.isTyping" class="whitespace-pre-line leading-relaxed"
                                    x-html="linkify(message.text)"></p>
                                <p x-show="message.isTyping" class="whitespace-pre-line leading-relaxed"
                                    x-text="message.text"></p>
                                <span x-show="message.role === 'assistant' && message.isTyping"
                                    class="ml-0.5 inline-block h-4 w-[2px] animate-pulse rounded bg-primary-400 align-middle"
                                    aria-hidden="true"></span>
                            </div>
                        </div>

                        {{-- Suggestions --}}
                        <div x-show="message.role === 'assistant' && !message.isTyping && message.suggestions.length > 0"
                            class="mt-2 ml-8 flex flex-wrap gap-1.5">
                            <template x-for="suggestion in message.suggestions" :key="message.id + suggestion">
                                <button type="button" @click="sendQuickAction(suggestion)"
                                    class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                                    x-text="suggestion"></button>
                            </template>
                        </div>

                        {{-- Feedback --}}
                        <div x-show="message.role === 'assistant' && !message.isTyping && message.allowFeedback && message.feedbackState !== 'saved'"
                            class="mt-2 ml-8 flex items-center gap-1.5">
                            <button type="button" @click.prevent.stop="submitFeedback(message, 1)"
                                :disabled="message.feedbackState === 'sending'"
                                class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-500 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 disabled:opacity-50">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                Membantu
                            </button>
                            <button type="button" @click.prevent.stop="submitFeedback(message, -1)"
                                :disabled="message.feedbackState === 'sending'"
                                class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-500 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-50">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                                </svg>
                                Kurang Tepat
                            </button>
                        </div>
                        <p x-show="message.feedbackState === 'saved'"
                            class="mt-1.5 ml-8 text-[11px] font-medium text-emerald-600">Terima kasih atas feedbacknya!
                        </p>
                        <p x-show="message.feedbackState === 'failed'"
                            class="mt-1.5 ml-8 text-[11px] font-medium text-rose-600">Gagal terkirim, coba lagi.</p>
                    </article>
                </template>

                {{-- Loading Indicator --}}
                <div x-show="isLoading" class="flex items-center gap-2 ml-8">
                    <div
                        class="flex gap-1 rounded-2xl rounded-bl-sm border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <span class="inline-block h-2 w-2 animate-bounce rounded-full bg-gray-400"
                            style="animation-delay: 0ms"></span>
                        <span class="inline-block h-2 w-2 animate-bounce rounded-full bg-gray-400"
                            style="animation-delay: 150ms"></span>
                        <span class="inline-block h-2 w-2 animate-bounce rounded-full bg-gray-400"
                            style="animation-delay: 300ms"></span>
                    </div>
                </div>
            </div>

            {{-- Input Area --}}
            <form @submit.prevent="sendMessage()" class="border-t border-gray-100 bg-white p-3">
                <div class="flex items-end gap-2">
                    <textarea x-model="draftMessage" rows="1" maxlength="2000"
                        class="max-h-28 min-h-[44px] flex-1 resize-y rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-primary-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition"
                        placeholder="Tulis pesan..."></textarea>
                    <button type="submit" :disabled="isLoading || draftMessage.trim() === ''"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary-600 text-white transition hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </form>
        </section>
    </div>

    @include('layouts.partials.storefront-notice-modal')

    @stack('scripts')
    <script>
        function storefrontAiAssistant(config) {
            return {
                chatEndpoint: config.chatEndpoint,
                feedbackEndpoint: config.feedbackEndpoint,
                isOpen: false,
                isLoading: false,
                draftMessage: '',
                sessionId: '',
                prefersReducedMotion: false,
                messages: [],
                typingTimers: {},
                quickActions: [{
                        label: 'Alamat & Kontak',
                        prompt: 'Dimana alamat toko ini? Apakah ada link Google Maps dan nomor WhatsApp?'
                    },
                    {
                        label: 'Panduan Belanja',
                        prompt: 'Bagaimana cara belanja di website ini?'
                    },
                    {
                        label: 'Rekomendasi Produk',
                        prompt: 'Rekomendasi lampu LED untuk ruang tamu budget 50rb'
                    },
                    {
                        label: 'Cek Pesanan',
                        prompt: 'Bagaimana cara cek status pesanan saya?'
                    },
                ],

                init() {
                    this.sessionId = this.resolveSessionId();
                    this.prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)')
                        .matches;
                    if (this.messages.length === 0) {
                        this.messages.push(this.createAssistantMessage(
                            'Halo kak! Selamat datang di HS Electric ⚡\n\nAda yang bisa kubantu hari ini? Mau tanya spesifikasi lampu yang cocok, cari kabel, ongkir pengiriman ke rumah, atau mau dibantu cek pesanan? Langsung chat santai aja ya! 😊'
                        ));
                    }
                },

                openPanel() {
                    this.isOpen = true;
                    window.dispatchEvent(new CustomEvent('chat-panel-opened'));
                    this.$nextTick(() => this.scrollToBottom());
                },

                closePanel() {
                    this.isOpen = false;
                    window.dispatchEvent(new CustomEvent('chat-panel-closed'));
                },

                resolveSessionId() {
                    const storageKey = 'hs_electric_ai_session_id';

                    try {
                        const existing = window.localStorage.getItem(storageKey);
                        if (existing) {
                            return existing;
                        }

                        const created = this.generateId();
                        window.localStorage.setItem(storageKey, created);

                        return created;
                    } catch (error) {
                        return this.generateId();
                    }
                },

                generateId() {
                    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                        return window.crypto.randomUUID();
                    }

                    return 'msg-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
                },

                createAssistantMessage(text, payload = {}) {
                    const llm = payload.llm || null;

                    return {
                        id: this.generateId(),
                        role: 'assistant',
                        text,
                        suggestions: Array.isArray(payload.suggestions) ? payload.suggestions : [],
                        intent: payload.intent || null,
                        messageId: payload.messageId || null,
                        llm,
                        llmLabel: this.buildLlmLabel(llm),
                        latencyMs: Number.isFinite(payload.latencyMs) ? Math.max(0, Math.round(payload.latencyMs)) : null,
                        promptVersion: payload.promptVersion || null,
                        ruleVersion: payload.ruleVersion || null,
                        responseSource: payload.responseSource || (llm ? 'provider_rewrite' : 'tool_first'),
                        allowFeedback: payload.allowFeedback === true,
                        feedbackState: 'idle',
                        isTyping: payload.isTyping === true,
                    };
                },

                createUserMessage(text) {
                    return {
                        id: this.generateId(),
                        role: 'user',
                        text,
                        suggestions: [],
                        intent: null,
                        messageId: null,
                        llm: null,
                        llmLabel: '',
                        allowFeedback: false,
                        feedbackState: 'idle',
                    };
                },

                requestHeaders() {
                    const headers = {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    };

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (csrfToken) {
                        headers['X-CSRF-TOKEN'] = csrfToken;
                    }

                    return headers;
                },

                buildRequestContext() {
                    const metaDescription = document.querySelector('meta[name="description"]')?.getAttribute('content') ||
                        '';
                    const metaKeywordsRaw = document.querySelector('meta[name="keywords"]')?.getAttribute('content') || '';
                    const ogTitle = document.querySelector('meta[property="og:title"]')?.getAttribute('content') || '';

                    const productKeywords = metaKeywordsRaw
                        .split(',')
                        .map((keyword) => keyword.trim())
                        .filter((keyword) => keyword !== '')
                        .slice(0, 8);

                    return {
                        locale: 'id',
                        channel: 'storefront_widget',
                        page_title: document.title || null,
                        page_path: window.location.pathname || null,
                        product_name: ogTitle || null,
                        product_description: metaDescription || null,
                        product_keywords: productKeywords,
                    };
                },

                buildHistorySnapshot(limit = 12) {
                    return this.messages
                        .filter((messageItem) => messageItem.role === 'user' || messageItem.role === 'assistant')
                        .slice(-limit)
                        .map((messageItem) => ({
                            role: messageItem.role,
                            text: (messageItem.text || '').toString().trim().slice(0, 500),
                        }))
                        .filter((historyItem) => historyItem.text !== '');
                },

                buildLlmLabel(llm) {
                    // Never expose AI/bot labels to users — assistant presents as human staff
                    return '';
                },

                safeAssistantFallbackText(fullText) {
                    const normalizedText = (fullText || '').toString().trim();

                    return normalizedText !== '' ? normalizedText : 'Maaf, saya belum bisa menjawab pertanyaan ini.';
                },

                clearTypingTimers(messageId) {
                    const timers = this.typingTimers[messageId];
                    if (!timers) {
                        return;
                    }

                    if (timers.intervalId) {
                        window.clearInterval(timers.intervalId);
                    }

                    if (timers.watchdogId) {
                        window.clearTimeout(timers.watchdogId);
                    }

                    delete this.typingTimers[messageId];
                },

                /**
                 * Find a message from the reactive messages array by ID.
                 *
                 * This is critical for Alpine.js reactivity.  When we push a
                 * plain object into the reactive `messages` array, Alpine wraps
                 * it in a Proxy.  The original local variable still points to
                 * the RAW (non-Proxy) object, so mutations on it bypass
                 * Alpine's Proxy set-trap and the UI never re-renders.
                 *
                 * Always use this helper to get the Proxy-wrapped reference
                 * before mutating message properties inside async callbacks
                 * (setInterval, setTimeout, fetch .then, etc.).
                 */
                findReactiveMessage(messageId) {
                    return this.messages.find(m => m.id === messageId) || null;
                },

                finalizeAssistantTyping(messageId, fullText) {
                    const message = this.findReactiveMessage(messageId);
                    if (!message) {
                        this.clearTypingTimers(messageId);
                        return;
                    }

                    this.clearTypingTimers(messageId);
                    message.text = this.safeAssistantFallbackText(fullText);
                    message.isTyping = false;
                    this.$nextTick(() => this.scrollToBottom());
                },

                pushAssistantMessageWithTyping(fullText, payload = {}) {
                    const normalizedFullText = (fullText || '').toString();
                    const rawMessage = this.createAssistantMessage('', {
                        ...payload,
                        isTyping: true,
                    });

                    // Push to reactive array — Alpine wraps it in a Proxy.
                    this.messages.push(rawMessage);

                    // CRITICAL: Capture the message ID so we can always look up
                    // the Proxy-wrapped version from the reactive array.  Using
                    // the raw `rawMessage` reference for later mutations would
                    // bypass Alpine's reactivity and the UI would never update
                    // (this was the root cause of the "1 character" bug).
                    const messageId = rawMessage.id;

                    this.$nextTick(() => this.scrollToBottom());

                    if (this.prefersReducedMotion) {
                        this.finalizeAssistantTyping(messageId, normalizedFullText);
                        return;
                    }

                    const characters = [...normalizedFullText];
                    if (characters.length < 4) {
                        this.finalizeAssistantTyping(messageId, normalizedFullText);
                        return;
                    }

                    // Set initial empty text through the reactive reference.
                    const initialRef = this.findReactiveMessage(messageId);
                    if (initialRef) {
                        initialRef.text = '';
                    }

                    let cursor = 0;
                    const totalCharacters = characters.length;
                    const chunkSize = totalCharacters > 320 ? 6 : (totalCharacters > 180 ? 4 : 3);
                    const intervalMs = 24;

                    const intervalId = window.setInterval(() => {
                        // Always look up the Proxy-wrapped message so Alpine
                        // registers the mutation and re-renders the DOM.
                        const msg = this.findReactiveMessage(messageId);
                        if (!msg || !msg.isTyping) {
                            this.clearTypingTimers(messageId);
                            return;
                        }

                        cursor = Math.min(totalCharacters, cursor + chunkSize);
                        msg.text = characters.slice(0, cursor).join('');

                        if (cursor % (chunkSize * 4) === 0 || msg.text.endsWith('\n')) {
                            this.scrollToBottom();
                        }

                        if (cursor >= totalCharacters) {
                            this.finalizeAssistantTyping(messageId, normalizedFullText);
                        }
                    }, intervalMs);

                    const watchdogDelay = Math.max(2500, Math.min(15000, totalCharacters * 55));
                    const watchdogId = window.setTimeout(() => {
                        this.finalizeAssistantTyping(messageId, normalizedFullText);
                    }, watchdogDelay);

                    this.typingTimers[messageId] = {
                        intervalId,
                        watchdogId,
                    };
                },

                async sendQuickAction(prompt) {
                    this.draftMessage = prompt;
                    await this.sendMessage();
                },

                async sendMessage() {
                    const message = this.draftMessage.trim();
                    if (message === '' || this.isLoading) {
                        return;
                    }

                    const requestStartedAt = window.performance && typeof window.performance.now === 'function' ?
                        window.performance.now() :
                        Date.now();

                    const historySnapshot = this.buildHistorySnapshot(8);

                    this.messages.push(this.createUserMessage(message));
                    this.draftMessage = '';
                    this.isLoading = true;
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const response = await fetch(this.chatEndpoint, {
                            method: 'POST',
                            headers: this.requestHeaders(),
                            body: JSON.stringify({
                                session_id: this.sessionId,
                                message,
                                history: historySnapshot,
                                context: this.buildRequestContext(),
                            }),
                        });

                        const payload = await response.json();
                        if (!response.ok) {
                            throw new Error(payload.message || 'Permintaan AI gagal diproses.');
                        }

                        const replyText = typeof payload.reply === 'string' && payload.reply.trim() !== '' ? payload
                            .reply :
                            'Maaf, saya belum bisa menjawab pertanyaan ini.';

                        this.pushAssistantMessageWithTyping(replyText, {
                            suggestions: payload.suggestions,
                            intent: payload.intent,
                            messageId: payload.message_id,
                            llm: payload.data && payload.data.llm ? payload.data.llm : null,
                            latencyMs: (window.performance && typeof window.performance.now === 'function' ?
                                window.performance.now() :
                                Date.now()) - requestStartedAt,
                            promptVersion: payload.data && payload.data.assistant_meta ? payload.data
                                .assistant_meta
                                .prompt_version : null,
                            ruleVersion: payload.data && payload.data.assistant_meta ? payload.data
                                .assistant_meta
                                .rule_version : null,
                            responseSource: payload.data && payload.data.llm ? 'provider_rewrite' :
                                'tool_first',
                            allowFeedback: true,
                        });
                    } catch (error) {
                        this.pushAssistantMessageWithTyping(
                            'Maaf, layanan AI sedang sibuk. Silakan coba lagi dalam beberapa saat.', {
                                suggestions: ['Ongkir berapa?', 'Cara cek status pesanan'],
                                allowFeedback: true,
                            }
                        );
                    } finally {
                        this.isLoading = false;
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                async submitFeedback(message, rating) {
                    if (message.role !== 'assistant' || message.feedbackState === 'sending' || message.feedbackState ===
                        'saved') {
                        return;
                    }

                    message.feedbackState = 'sending';

                    try {
                        const response = await fetch(this.feedbackEndpoint, {
                            method: 'POST',
                            headers: this.requestHeaders(),
                            body: JSON.stringify({
                                session_id: this.sessionId,
                                message_id: message.messageId || null,
                                intent: message.intent,
                                intent_detected: message.intent || null,
                                intent_resolved: message.intent || null,
                                rating,
                                reason: rating > 0 ? 'Membantu' : 'Kurang tepat',
                                reason_code: rating > 0 ? 'helpful_generic' : 'not_helpful_generic',
                                reason_detail: null,
                                provider: message.llm ? (message.llm.provider || null) : null,
                                model: message.llm ? (message.llm.model || null) : null,
                                llm_status: message.llm ? (message.llm.status || null) : null,
                                fallback_used: message.llm ? !!message.llm.fallback_used : null,
                                response_latency_ms: Number.isFinite(message.latencyMs) ? message
                                    .latencyMs : null,
                                prompt_version: message.promptVersion || null,
                                rule_version: message.ruleVersion || null,
                                response_source: message.responseSource || null,
                                feedback_version: 2,
                                metadata: message.llm ? {
                                    provider: message.llm.provider || null,
                                    model: message.llm.model || null,
                                    fallback_used: message.llm.fallback_used || false,
                                    status: message.llm.status || null,
                                } : null,
                            }),
                        });

                        if (!response.ok) {
                            throw new Error('Feedback request gagal.');
                        }

                        message.feedbackState = 'saved';
                    } catch (error) {
                        message.feedbackState = 'failed';
                    }
                },

                scrollToBottom() {
                    const viewport = this.$refs.messageViewport;
                    if (!viewport) {
                        return;
                    }

                    viewport.scrollTop = viewport.scrollHeight;
                },

                linkify(text) {
                    if (!text) return '';

                    // Escape HTML to prevent XSS - including single quote
                    const escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');

                    // Only allow http/https URLs and validate them
                    const urlPattern = /^https?:\/\/[^\s<>"')\]]+$/;

                    // Convert valid URLs to safe clickable links with nofollow
                    return escaped.replace(urlPattern, (url) => {
                        const safeUrl = url.replace(/"/g, '%22'); // Extra safety for quotes in URL
                        return '<a href="' + safeUrl +
                            '" target="_blank" rel="noopener noreferrer nofollow" class="text-primary-600 underline decoration-primary-300 hover:text-primary-800 hover:decoration-primary-500 transition-colors break-all">' +
                            url + '</a>';
                    });
                },
            };
        }
    </script>
</body>

</html>
