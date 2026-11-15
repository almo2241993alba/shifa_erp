function initializeFormEnhancements() {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        if (toggle.dataset.initialized) return;
        toggle.dataset.initialized = 'true';

        toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!input) return;

            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(visible));
            const labels = document.documentElement.lang === 'en'
                ? { show: 'Show password', hide: 'Hide password' }
                : { show: 'إظهار كلمة المرور', hide: 'إخفاء كلمة المرور' };
            toggle.setAttribute('aria-label', visible ? labels.hide : labels.show);
        });
    });

    document.querySelectorAll('form[data-wizard]').forEach((form) => {
        if (form.dataset.initialized) return;
        form.dataset.initialized = 'true';
        const steps = [...form.querySelectorAll('[data-wizard-step]')];
        let currentStep = Number.parseInt(form.dataset.wizardStart ?? '0', 10) || 0;

        const showStep = (index) => {
            currentStep = index;
            steps.forEach((step, stepIndex) => {
                step.hidden = stepIndex !== currentStep;
            });
        };

        form.querySelectorAll('[data-wizard-next]').forEach((button) => {
            button.addEventListener('click', () => {
                for (const input of steps[currentStep].querySelectorAll('input, select, textarea')) {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        return;
                    }
                }

                showStep(Math.min(currentStep + 1, steps.length - 1));
            });
        });

        form.querySelectorAll('[data-wizard-previous]').forEach((button) => {
            button.addEventListener('click', () => showStep(Math.max(currentStep - 1, 0)));
        });

        showStep(Math.min(currentStep, steps.length - 1));
    });
}

document.addEventListener('DOMContentLoaded', initializeFormEnhancements);
document.addEventListener('livewire:navigated', initializeFormEnhancements);
