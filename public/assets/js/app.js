/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\public\assets\js\app.js
 * UI補助用JavaScript。キャンペーン作成ガイドのバルーン表示とフォームの二重送信抑止を行う。
 */

(function initCampaignGuideBalloons() {
    const form = document.querySelector('[data-campaign-guide-form]');
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const fields = Array.from(form.querySelectorAll('[data-guide-message]'));
    const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
    const hideTimers = new Map();
    const balloon = document.createElement('div');
    const balloonTitle = document.createElement('strong');
    const balloonBody = document.createElement('span');
    let activeField = null;
    let lockedField = null;
    form.noValidate = true;

    balloon.className = 'guide-inline-balloon';
    balloon.setAttribute('role', 'tooltip');
    balloon.hidden = true;
    balloon.append(balloonTitle, balloonBody);
    document.body.appendChild(balloon);

    function clearHideTimer(field) {
        const timer = hideTimers.get(field);
        if (timer) {
            window.clearTimeout(timer);
            hideTimers.delete(field);
        }
    }

    function hideGuideNow(field) {
        clearHideTimer(field);
        if (lockedField === field) {
            lockedField = null;
        }
        if (!field || activeField === field) {
            activeField = null;
            balloon.hidden = true;
        }
    }

    function scheduleHideGuide(field) {
        if (lockedField === field) {
            return;
        }
        clearHideTimer(field);
        hideTimers.set(field, window.setTimeout(function () {
            hideTimers.delete(field);
            hideGuideNow(field);
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
        activeField = field;
        balloonTitle.textContent = field.dataset.guideTitle || '';
        balloonBody.textContent = field.dataset.guideMessage || '';
        balloon.hidden = false;
        positionGuideBalloon(field);
    }

    function positionGuideBalloon(field) {
        const rect = field.getBoundingClientRect();
        const scrollX = window.scrollX || document.documentElement.scrollLeft;
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const margin = 10;
        const maxLeft = scrollX + document.documentElement.clientWidth - balloon.offsetWidth - margin;
        const left = Math.max(scrollX + margin, Math.min(scrollX + rect.left, maxLeft));
        let top = scrollY + rect.top - balloon.offsetHeight - margin;
        balloon.classList.remove('is-below');
        if (top < scrollY + margin) {
            top = scrollY + rect.bottom + margin;
            balloon.classList.add('is-below');
        }
        balloon.style.left = left + 'px';
        balloon.style.top = top + 'px';
    }

    function firstInvalidField() {
        return fields.find(function (field) {
            return !field.checkValidity();
        }) || null;
    }

    function guideInvalidField(field) {
        lockedField = field;
        showGuide(field);
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    fields.forEach(function (field) {
        field.addEventListener('focus', function () {
            if (lockedField !== field) {
                lockedField = null;
            }
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

    window.addEventListener('resize', function () {
        if (activeField && !balloon.hidden) {
            positionGuideBalloon(activeField);
        }
    });
    window.addEventListener('scroll', function () {
        if (activeField && !balloon.hidden) {
            positionGuideBalloon(activeField);
        }
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
