/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\public\assets\js\app.js
 * UI補助用JavaScript。キャンペーン作成ガイドのバルーン表示とフォームの二重送信抑止を行う。
 */

(function initCampaignGuideBalloons() {
    const form = document.querySelector('[data-campaign-guide-form]');
    if (!(form instanceof HTMLFormElement) || !window.bootstrap || !bootstrap.Popover) {
        return;
    }

    const fields = Array.from(form.querySelectorAll('[data-guide-message]'));
    const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
    const popovers = new Map();
    const hideTimers = new Map();
    form.noValidate = true;

    function popoverFor(field) {
        if (!popovers.has(field)) {
            popovers.set(field, new bootstrap.Popover(field, {
                container: 'body',
                customClass: 'guide-balloon',
                placement: field.dataset.guidePlacement || 'top',
                trigger: 'manual',
                title: field.dataset.guideTitle || '',
                content: field.dataset.guideMessage || ''
            }));
        }
        return popovers.get(field);
    }

    function clearHideTimer(field) {
        const timer = hideTimers.get(field);
        if (timer) {
            window.clearTimeout(timer);
            hideTimers.delete(field);
        }
    }

    function hideGuideNow(field) {
        clearHideTimer(field);
        const popover = popovers.get(field);
        if (popover) {
            popover.hide();
        }
    }

    function scheduleHideGuide(field) {
        clearHideTimer(field);
        hideTimers.set(field, window.setTimeout(function () {
            hideTimers.delete(field);
            const popover = popovers.get(field);
            if (popover) {
                popover.hide();
            }
        }, 120));
    }

    function hideOtherGuides(activeField) {
        fields.forEach(function (field) {
            if (field !== activeField) {
                hideGuideNow(field);
            }
        });
    }

    function showGuide(field) {
        clearHideTimer(field);
        hideOtherGuides(field);
        popoverFor(field).show();
    }

    function firstInvalidField() {
        return fields.find(function (field) {
            return !field.checkValidity();
        }) || null;
    }

    function guideInvalidField(field) {
        showGuide(field);
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    fields.forEach(function (field) {
        field.addEventListener('focus', function () {
            showGuide(field);
        });
        field.addEventListener('blur', function () {
            scheduleHideGuide(field);
        });
        field.addEventListener('input', function () {
            if (field.checkValidity()) {
                hideGuideNow(field);
            }
        });
        field.addEventListener('change', function () {
            if (field.checkValidity()) {
                hideGuideNow(field);
            } else {
                showGuide(field);
            }
        });
    });

    form.addEventListener('invalid', function (event) {
        const field = event.target;
        if (!(field instanceof HTMLElement) || !field.matches('[data-guide-message]')) {
            return;
        }
        event.preventDefault();
        guideInvalidField(field);
    }, true);

    form.addEventListener('submit', function (event) {
        const field = firstInvalidField();
        if (!field) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        guideInvalidField(field);
    });

    submitButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            const field = firstInvalidField();
            if (!field) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            guideInvalidField(field);
        });
    });
})();

document.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    const submitter = event.submitter;
    if (submitter instanceof HTMLButtonElement) {
        submitter.disabled = true;
        submitter.dataset.originalText = submitter.textContent || '';
        submitter.textContent = '処理中';
    }
});
