@php
    $storeName = \App\Models\Setting::get('store_name', 'HS ELECTRIC');
    $storePhoneRaw = (string) \App\Models\Setting::get('store_phone', '');
    $storePhoneDigits = preg_replace('/\D+/', '', $storePhoneRaw);
    if ($storePhoneDigits !== '' && str_starts_with($storePhoneDigits, '0')) {
        $storePhoneDigits = '62' . substr($storePhoneDigits, 1);
    }

    $whatsAppUrl = $storePhoneDigits !== '' ? 'https://wa.me/' . $storePhoneDigits : route('home');
    $storeEmail = (string) \App\Models\Setting::get('store_email', 'admin@example.com');
    $emailUrl = 'mailto:' . $storeEmail;

    $storeAddress = (string) \App\Models\Setting::get('store_address', '');
    $mapsUrl =
        $storeAddress !== ''
            ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($storeAddress)
            : route('home');

    $normalizeExternalUrl = static function (string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! preg_match('/^https?:\/\//i', $url)) {
            return 'https://' . ltrim($url, '/');
        }
        return $url;
    };

    $instagramUrl = $normalizeExternalUrl((string) \App\Models\Setting::get('social_instagram_url', ''));
    $facebookUrl = $normalizeExternalUrl((string) \App\Models\Setting::get('social_facebook_url', ''));
    $tiktokUrl = $normalizeExternalUrl((string) \App\Models\Setting::get('social_tiktok_url', ''));

    $instagramHref = $instagramUrl !== '' ? $instagramUrl : route('home');
    $facebookHref = $facebookUrl !== '' ? $facebookUrl : route('home');
    $tiktokHref = $tiktokUrl !== '' ? $tiktokUrl : route('home');
@endphp

<footer class="mt-auto border-t border-slate-700/70 bg-slate-900 text-slate-300 font-body {{ $footerClass ?? '' }}">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="gap-8 md:flex md:justify-between">
            <div class="mb-8 md:mb-0">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <img src="{{ asset('img/gemini_generated_image.png') }}" alt="{{ $storeName }}"
                        class="h-9 w-9 object-contain">
                    <span class="self-center whitespace-nowrap text-2xl font-semibold text-white">
                        {{ $storeName }}
                    </span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 sm:gap-10">
                <div>
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-wide text-white">Resources</h2>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <a href="{{ route('home') }}"
                                class="text-slate-400 transition hover:text-brand-300">Katalog</a>
                        </li>
                        <li>
                            <a href="{{ route('home.tracking') }}"
                                class="text-slate-400 transition hover:text-brand-300">Lacak Pesanan</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-wide text-white">Follow Us</h2>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <a href="{{ $instagramHref }}" target="_blank" rel="noopener noreferrer"
                                class="text-slate-400 transition hover:text-brand-300">Instagram</a>
                        </li>
                        <li>
                            <a href="{{ $facebookHref }}" target="_blank" rel="noopener noreferrer"
                                class="text-slate-400 transition hover:text-brand-300">Facebook</a>
                        </li>
                        <li>
                            <a href="{{ $tiktokHref }}" target="_blank" rel="noopener noreferrer"
                                class="text-slate-400 transition hover:text-brand-300">TikTok</a>
                        </li>
                        <li>
                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer"
                                class="text-slate-400 transition hover:text-brand-300">WhatsApp</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h2 class="mb-5 text-sm font-semibold uppercase tracking-wide text-white">Legal</h2>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <a href="{{ route('legal.privacy') }}"
                                class="text-slate-400 transition hover:text-brand-300">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="{{ route('legal.terms') }}"
                                class="text-slate-400 transition hover:text-brand-300">Terms & Conditions</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <hr class="my-6 border-slate-700/80 sm:mx-auto lg:my-8" />

        <div class="sm:flex sm:items-center sm:justify-between">
            <span class="text-sm text-slate-400">
                &copy; {{ date('Y') }} {{ $storeName }}. All Rights Reserved.
            </span>
            <div class="mt-4 flex gap-5 sm:mt-0 sm:justify-center">
                <a href="{{ $instagramHref }}" target="_blank" rel="noopener noreferrer"
                    class="text-slate-400 transition hover:text-brand-300" aria-label="Instagram">
                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M12 2.03c2.72 0 3.06.01 4.12.06 1.02.05 1.71.21 2.11.37.54.21.92.47 1.32.87.4.4.66.78.87 1.32.16.4.32 1.09.37 2.11.05 1.06.06 1.4.06 4.12s-.01 3.06-.06 4.12c-.05 1.02-.21 1.71-.37 2.11-.21.54-.47.92-.87 1.32-.4.4-.78.66-1.32.87-.4.16-1.09.32-2.11.37-1.06.05-1.4.06-4.12.06s-3.06-.01-4.12-.06c-1.02-.05-1.71-.21-2.11-.37a3.52 3.52 0 0 1-1.32-.87 3.52 3.52 0 0 1-.87-1.32c-.16-.4-.32-1.09-.37-2.11-.05-1.06-.06-1.4-.06-4.12s.01-3.06.06-4.12c.05-1.02.21-1.71.37-2.11.21-.54.47-.92.87-1.32.4-.4.78-.66 1.32-.87.4-.16 1.09-.32 2.11-.37 1.06-.05 1.4-.06 4.12-.06ZM12 7.44a4.56 4.56 0 1 0 0 9.12 4.56 4.56 0 0 0 0-9.12Zm0 7.53a2.97 2.97 0 1 1 0-5.94 2.97 2.97 0 0 1 0 5.94Zm5.82-7.71a1.07 1.07 0 1 1-2.14 0 1.07 1.07 0 0 1 2.14 0Z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="{{ $facebookHref }}" target="_blank" rel="noopener noreferrer"
                    class="text-slate-400 transition hover:text-brand-300" aria-label="Facebook">
                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M12 2.04C6.5 2.04 2 6.53 2 12.06c0 5 3.65 9.14 8.44 9.92v-7.02H7.9v-2.9h2.54V9.84c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.23.2 2.23.2v2.47h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.77l-.44 2.9h-2.33V22c4.79-.78 8.44-4.92 8.44-9.92 0-5.53-4.5-10.02-10-10.02Z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="{{ $tiktokHref }}" target="_blank" rel="noopener noreferrer"
                    class="text-slate-400 transition hover:text-brand-300" aria-label="TikTok">
                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M16.5 3c.32 1.56 1.26 2.9 2.63 3.76A6.8 6.8 0 0 0 22 7.7v3.15a10 10 0 0 1-4.18-1.07v6.04a6.81 6.81 0 1 1-5.37-6.65v3.26a3.54 3.54 0 1 0 2.1 3.24V3h1.95z" />
                    </svg>
                </a>
                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer"
                    class="text-slate-400 transition hover:text-brand-300" aria-label="WhatsApp">
                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20.52 3.48A11.88 11.88 0 0 0 12.06 0C5.4 0 0 5.4 0 12.06c0 2.13.56 4.21 1.62 6.03L0 24l6.08-1.6a12.05 12.05 0 0 0 5.98 1.53h.01C18.72 23.93 24 18.53 24 11.88c0-3.2-1.25-6.2-3.48-8.4zM12.07 21.9h-.01a9.88 9.88 0 0 1-5.03-1.37l-.36-.21-3.6.95.96-3.51-.23-.37a9.86 9.86 0 0 1-1.51-5.29c0-5.45 4.43-9.88 9.88-9.88a9.8 9.8 0 0 1 7 2.9 9.8 9.8 0 0 1 2.89 6.98c0 5.45-4.54 9.8-9.99 9.8zm5.42-7.43c-.3-.15-1.77-.87-2.05-.97-.27-.1-.47-.15-.67.15s-.77.97-.95 1.17c-.17.2-.35.22-.65.07-.3-.15-1.28-.47-2.44-1.5-.9-.8-1.5-1.79-1.67-2.09-.18-.3-.02-.46.13-.61.14-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.48-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49 0 1.47 1.06 2.88 1.21 3.08.15.2 2.08 3.17 5.03 4.44.7.3 1.25.49 1.67.62.7.22 1.34.19 1.84.12.56-.08 1.77-.72 2.02-1.41.25-.69.25-1.27.17-1.4-.07-.12-.27-.2-.57-.35z" />
                    </svg>
                </a>
                <a href="{{ $emailUrl }}" class="text-slate-400 transition hover:text-brand-300"
                    aria-label="Email">
                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 3.25-8 5-8-5V6l8 5 8-5v1.25z" />
                    </svg>
                </a>
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"
                    class="text-slate-400 transition hover:text-brand-300" aria-label="Google Maps">
                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2a7 7 0 0 0-7 7c0 4.99 6.07 12.14 6.33 12.44a.9.9 0 0 0 1.34 0C12.93 21.14 19 13.99 19 9a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>
