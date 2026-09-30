export function initializeCookieConsent(root = document) {
    root.querySelectorAll('.cookie-consent').forEach((banner) => {
        if (banner.dataset.initialized) return;
        banner.dataset.initialized = 'true';
        const dialog = banner.closest('dialog');
        banner.querySelector('.js-show-settings')?.addEventListener('click', () => {
            banner.querySelector('.cookie-consent-simple').style.display = 'none';
            banner.querySelector('.cookie-consent-detail').style.display = 'block';
        });

        banner.querySelectorAll('.consent-form-category').forEach((category) => {
            const toggle = category.querySelector('input[type="checkbox"]');
            const vendors = [...category.querySelectorAll('.consent-form-vendors input[type="checkbox"]')];
            if (!toggle || !vendors.length) return;
            const sync = () => {
                toggle.checked = vendors.every((vendor) => vendor.checked);
                toggle.indeterminate = !toggle.checked && vendors.some((vendor) => vendor.checked);
            };
            toggle.addEventListener('change', () => {
                vendors.forEach((vendor) => { vendor.checked = toggle.checked; });
                sync();
            });
            vendors.forEach((vendor) => vendor.addEventListener('change', sync));
            sync();
        });

        banner.querySelectorAll('.cookie-consent__form').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (form.dataset.submitting) return;
                form.dataset.submitting = 'true';
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form, event.submitter),
                        credentials: 'same-origin'
                    });
                    if (!response.ok) throw new Error(`Consent request failed: ${response.status}`);
                    if (dialog) {
                        dialog.close();
                        dialog.remove();
                    } else {
                        banner.remove();
                    }
                    for (const name of ['cookie-consent-form-submit-successful', 'cookie-consent.form-submit-successful']) {
                        document.dispatchEvent(new CustomEvent(name, {detail: event.submitter}));
                    }
                } catch (error) {
                    console.error(error);
                    document.dispatchEvent(new CustomEvent('cookie-consent-form-submit-failed', {detail: error}));
                } finally {
                    delete form.dataset.submitting;
                }
            });
        });
        if (dialog && !dialog.open) dialog.showModal();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initializeCookieConsent());
} else {
    initializeCookieConsent();
}
