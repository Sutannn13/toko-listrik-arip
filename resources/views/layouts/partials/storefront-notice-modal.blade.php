<div id="storefront-notice-modal" class="pointer-events-none fixed inset-0 z-[120] hidden" aria-hidden="true">
    <div data-notice-overlay
        class="absolute inset-0 bg-slate-900/35 opacity-0 backdrop-blur-[1px] transition-opacity duration-200"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <section data-notice-panel role="dialog" aria-modal="true" aria-labelledby="storefront-notice-title"
            aria-describedby="storefront-notice-message" tabindex="-1"
            class="w-full max-w-md translate-y-2 overflow-hidden rounded-xl border border-slate-200 bg-white opacity-0 shadow-lg shadow-slate-900/10 transition-all duration-200">
            <header class="border-b border-slate-100 bg-slate-50/80 px-4 py-3.5 sm:px-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-primary-600 ring-1 ring-primary-200">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m0 3.75h.008v.008H12v-.008z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.34 3.94 1.82 18.06A1.9 1.9 0 0 0 3.48 21h17.04a1.9 1.9 0 0 0 1.66-2.94L13.66 3.94a1.9 1.9 0 0 0-3.32 0Z" />
                            </svg>
                        </span>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Peringatan
                            Sistem</p>
                    </div>

                    <button type="button" data-notice-close-btn
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Tutup notifikasi">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>

            <div class="px-4 py-4 sm:px-5 sm:py-5">
                <h3 id="storefront-notice-title" class="text-base font-semibold text-slate-900">Login Diperlukan</h3>
                <p id="storefront-notice-message" class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Anda perlu login terlebih dahulu sebelum melanjutkan proses ini.
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-2 sm:justify-end">
                    <a id="storefront-notice-action" href="{{ route('login') }}"
                        class="inline-flex min-w-[128px] items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-500/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400/30">
                        Masuk Sekarang
                    </a>
                    <button type="button" data-notice-close-btn
                        class="inline-flex min-w-[96px] items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300/30">
                        Tutup
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    (function() {
        const modal = document.getElementById('storefront-notice-modal');
        if (!modal) {
            return;
        }

        const overlay = modal.querySelector('[data-notice-overlay]');
        const panel = modal.querySelector('[data-notice-panel]');
        const titleElement = modal.querySelector('#storefront-notice-title');
        const messageElement = modal.querySelector('#storefront-notice-message');
        const actionElement = modal.querySelector('#storefront-notice-action');
        const closeButtons = modal.querySelectorAll('[data-notice-close-btn]');

        let previousActiveElement = null;
        let isOpen = false;

        function closeNotice() {
            if (!isOpen) {
                return;
            }

            isOpen = false;
            overlay.classList.remove('opacity-100');
            panel.classList.remove('translate-y-0', 'opacity-100');
            panel.classList.add('translate-y-2', 'opacity-0');

            window.setTimeout(function() {
                modal.classList.add('hidden', 'pointer-events-none');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');

                if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                    previousActiveElement.focus();
                }
            }, 200);
        }

        window.showStorefrontNotice = function(options) {
            const resolved = options || {};

            titleElement.textContent = resolved.title || 'Login Diperlukan';
            messageElement.textContent = resolved.message ||
                'Anda perlu login terlebih dahulu sebelum melanjutkan proses ini.';

            if (resolved.actionUrl) {
                actionElement.href = resolved.actionUrl;
                actionElement.textContent = resolved.actionLabel || 'Masuk Sekarang';
                actionElement.classList.remove('hidden');
            } else {
                actionElement.href = '#';
                actionElement.classList.add('hidden');
            }

            previousActiveElement = document.activeElement instanceof HTMLElement ? document.activeElement :
                null;
            isOpen = true;

            modal.classList.remove('hidden', 'pointer-events-none');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');

            window.requestAnimationFrame(function() {
                overlay.classList.add('opacity-100');
                panel.classList.remove('translate-y-2', 'opacity-0');
                panel.classList.add('translate-y-0', 'opacity-100');
                panel.focus();
            });
        };

        closeButtons.forEach(function(button) {
            button.addEventListener('click', closeNotice);
        });

        overlay.addEventListener('click', closeNotice);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isOpen) {
                closeNotice();
            }
        });
    })();
</script>
