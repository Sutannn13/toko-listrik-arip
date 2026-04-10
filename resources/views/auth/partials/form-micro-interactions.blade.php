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
    })();
</script>
