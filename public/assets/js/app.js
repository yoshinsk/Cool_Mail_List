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
    const popovers = new Map();

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

    function hideGuide(field) {
        const popover = popovers.get(field);
        if (popover) {
            popover.hide();
        }
    }

    function hideOtherGuides(activeField) {
        fields.forEach(function (field) {
            if (field !== activeField) {
                hideGuide(field);
            }
        });
    }

    function showGuide(field) {
        hideOtherGuides(field);
        popoverFor(field).show();
    }

    fields.forEach(function (field) {
        field.addEventListener('focus', function () {
            showGuide(field);
        });
        field.addEventListener('blur', function () {
            window.setTimeout(function () {
                hideGuide(field);
            }, 120);
        });
        field.addEventListener('input', function () {
            if (field.checkValidity()) {
                hideGuide(field);
            }
        });
        field.addEventListener('change', function () {
            if (field.checkValidity()) {
                hideGuide(field);
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
        showGuide(field);
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }, true);
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
