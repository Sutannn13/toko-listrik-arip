<style>
    @media (max-width: 375px) {
        [data-auth-shell] {
            padding-top: 1rem;
            padding-bottom: 1rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        [data-auth-grid] {
            gap: 0.875rem;
        }

        [data-auth-card] {
            border-radius: 1.25rem;
            padding: 1.125rem;
        }

        [data-auth-title] {
            font-size: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.015em;
        }

        [data-auth-subtitle] {
            font-size: 0.8125rem;
            line-height: 1.45;
        }

        [data-auth-form] {
            gap: 0.875rem;
        }

        [data-auth-form] input[type='text'],
        [data-auth-form] input[type='email'],
        [data-auth-form] input[type='password'] {
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
            font-size: 0.875rem;
        }

        [data-auth-form] button[type='submit'] {
            padding-top: 0.6875rem;
            padding-bottom: 0.6875rem;
        }
    }
</style>

<script>
    (() => {
        const getSubmitButton = (form, submitEvent) => {
            if (submitEvent && submitEvent.submitter) {
                return submitEvent.submitter;
            }

            return form.querySelector('button[type="submit"], input[type="submit"]');
        };

        const applyLoadingState = (button, loadingText) => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('cursor-wait', 'opacity-80');

            if (button.tagName === 'BUTTON') {
                if (!button.dataset.originalHtml) {
                    button.dataset.originalHtml = button.innerHTML;
                }

                button.innerHTML =
                    `<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" class="opacity-25"></circle><path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" class="opacity-90"></path></svg><span>${loadingText}</span>`;
            } else if (button.tagName === 'INPUT') {
                if (!button.dataset.originalValue) {
                    button.dataset.originalValue = button.value;
                }

                button.value = loadingText;
            }
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.matches('form[data-ui-form]')) {
                return;
            }

            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            const submitButton = getSubmitButton(form, event);
            if (!submitButton) {
                return;
            }

            const loadingText = submitButton.dataset.loadingText || 'Memproses...';
            form.dataset.submitting = '1';
            applyLoadingState(submitButton, loadingText);
        });

        const toggleButtons = document.querySelectorAll('[data-password-toggle]');
        toggleButtons.forEach((button) => {
            const targetId = button.dataset.target;
            if (!targetId) {
                return;
            }

            const input = document.getElementById(targetId);
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const showIcon = button.querySelector('[data-icon-show]');
            const hideIcon = button.querySelector('[data-icon-hide]');

            button.addEventListener('click', () => {
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-label', isVisible ? 'Tampilkan password' :
                    'Sembunyikan password');
                button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');

                if (showIcon && hideIcon) {
                    showIcon.classList.toggle('hidden', !isVisible);
                    hideIcon.classList.toggle('hidden', isVisible);
                }
            });
        });

        const getStrengthResult = (password) => {
            if (!password) {
                return {
                    score: 0,
                    label: 'Belum diisi',
                    color: 'bg-slate-300',
                    hint: 'Gunakan minimal 8 karakter.',
                };
            }

            let score = 0;

            if (password.length >= 8) {
                score += 1;
            }
            if (password.length >= 12) {
                score += 1;
            }
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) {
                score += 1;
            }
            if (/\d/.test(password)) {
                score += 1;
            }
            if (/[^A-Za-z0-9]/.test(password)) {
                score += 1;
            }

            if (score <= 1) {
                return {
                    score: 1,
                    label: 'Sangat lemah',
                    color: 'bg-red-500',
                    hint: 'Tambah panjang password dan variasi karakter.',
                };
            }

            if (score === 2) {
                return {
                    score: 2,
                    label: 'Lemah',
                    color: 'bg-orange-500',
                    hint: 'Tambahkan huruf besar, angka, atau simbol.',
                };
            }

            if (score === 3) {
                return {
                    score: 3,
                    label: 'Sedang',
                    color: 'bg-amber-500',
                    hint: 'Cukup baik, tapi masih bisa diperkuat.',
                };
            }

            if (score === 4) {
                return {
                    score: 4,
                    label: 'Kuat',
                    color: 'bg-emerald-500',
                    hint: 'Sudah bagus untuk akun Anda.',
                };
            }

            return {
                score: 4,
                label: 'Sangat kuat',
                color: 'bg-emerald-600',
                hint: 'Password sangat aman.',
            };
        };

        const strengthBlocks = document.querySelectorAll('[data-password-strength]');
        strengthBlocks.forEach((block) => {
            const targetId = block.dataset.target;
            if (!targetId) {
                return;
            }

            const input = document.getElementById(targetId);
            const fill = block.querySelector('[data-strength-fill]');
            const label = block.querySelector('[data-strength-label]');
            const hint = block.querySelector('[data-strength-hint]');

            if (!(input instanceof HTMLInputElement) || !fill || !label || !hint) {
                return;
            }

            const updateStrength = () => {
                const result = getStrengthResult(input.value);

                fill.className = `h-full rounded-full transition-all duration-300 ${result.color}`;
                fill.style.width = `${result.score * 25}%`;
                label.textContent = result.label;
                hint.textContent = result.hint;
            };

            input.addEventListener('input', updateStrength);
            updateStrength();
        });
    })();
</script>
