/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\public\assets\js\app.js
 * UI補助用JavaScript。フォーム入力ガイドのバルーン表示とフォームの二重送信抑止を行う。
 */

(function initFormGuideBalloons() {
    const forms = Array.from(document.querySelectorAll('form[method="post"]:not([hidden])'));
    if (forms.length === 0) {
        return;
    }

    const hideTimers = new Map();
    const balloon = document.createElement('div');
    const balloonTitle = document.createElement('strong');
    const balloonBody = document.createElement('span');
    let activeField = null;
    let lockedField = null;

    balloon.className = 'guide-inline-balloon';
    balloon.setAttribute('role', 'tooltip');
    balloon.hidden = true;
    balloon.append(balloonTitle, balloonBody);
    document.body.appendChild(balloon);

    const fallbackGuides = {
        action: 'このフォームで実行する処理を指定します。',
        account_name: 'SMTP設定を管理画面で見分けるための名前です。複数の送信経路を使う場合は用途が分かる名前にしてください。',
        from_name: '受信者に表示される差出人名です。会社名やサービス名など、受信者が認識できる名前にします。',
        from_email: '受信者に表示される差出人メールアドレスです。SMTP側で送信が許可されているアドレスを指定してください。',
        reply_to: '返信を受け取りたいメールアドレスです。空欄ならFromメール宛の返信になります。',
        smtp_host: 'メール送信に接続するSMTPサーバ名です。ホスト名の入力ミスがあると送信できません。',
        smtp_port: 'SMTP接続ポートです。一般的にTLSは587、SSLは465を使います。',
        encryption: 'SMTP接続の暗号化方式です。587ならTLS、465ならSSLが一般的です。',
        auth_username: 'SMTP認証に使うユーザー名です。認証不要なSMTPでは空欄にします。',
        smtp_password: 'SMTP認証パスワードです。保存時に暗号化されます。変更しない場合は空欄のままで構いません。',
        per_minute_limit: '1分あたりの送信上限です。最初は小さめにして、到達状況とSMTP制限を見ながら調整します。',
        daily_limit: '1日あたりの送信上限です。SMTP契約や運用ルールに合わせて設定します。',
        dkim_policy: 'DKIM確認の扱いです。通常はDKIM推奨で開始し、DNSが整ったら必須へ上げます。',
        is_active: 'この設定を配信に使える状態にするかを選びます。停止したい場合は無効にします。',
        organization_id: 'この利用者が操作する組織を選びます。宛先、送信者、テンプレート、キャンペーンは組織ごとに分かれます。',
        role: '利用者に許可する操作範囲です。必要最小限のロールを選んでください。',
        status: '利用者や宛先の利用状態です。activeだけが通常の利用対象になります。',
        sender_identity_id: '使用する送信者を選びます。本配信前にSMTPチェックとDNS診断を確認してください。',
        template_id: '配信またはテスト送信に使うテンプレートを選びます。本文に購読停止URLが必要です。',
        test_to: 'テストメールの送信先です。本配信前に自分または確認担当者へ送って表示を確認します。',
        result_id: 'テンプレート化するAI生成結果です。採用後にテンプレート編集で内容を確認してください。'
    };

    function formIsVisible(form) {
        return form.offsetParent !== null || form.classList.contains('show') || form.closest('.show');
    }

    function isGuideField(field) {
        if (!(field instanceof HTMLElement) || field.disabled || field.hidden) {
            return false;
        }
        if (!['INPUT', 'SELECT', 'TEXTAREA'].includes(field.tagName)) {
            return false;
        }
        const type = (field.getAttribute('type') || '').toLowerCase();
        return !['hidden', 'submit', 'button', 'reset'].includes(type);
    }

    function labelText(field) {
        if (field.id) {
            const escapedId = window.CSS && CSS.escape ? CSS.escape(field.id) : field.id.replace(/"/g, '\\"');
            const label = document.querySelector('label[for="' + escapedId + '"]');
            if (label) {
                return label.textContent.trim();
            }
        }
        const parentLabel = field.closest('label');
        return parentLabel ? parentLabel.textContent.trim() : '';
    }

    function helpText(field) {
        let next = field.nextElementSibling;
        while (next) {
            if (next.classList && next.classList.contains('form-help')) {
                return next.textContent.trim();
            }
            if (next.matches && next.matches('input, select, textarea, button')) {
                break;
            }
            next = next.nextElementSibling;
        }

        const parent = field.parentElement;
        if (!parent) {
            return '';
        }
        const directHelp = Array.from(parent.children).find(function (child) {
            return child !== field && child.classList && child.classList.contains('form-help');
        });
        return directHelp ? directHelp.textContent.trim() : '';
    }

    function guideTitle(field) {
        return field.dataset.guideTitle || labelText(field) || field.getAttribute('placeholder') || field.name || '入力項目';
    }

    function fallbackMessage(field) {
        const key = field.name || field.id || '';
        if (fallbackGuides[key]) {
            return fallbackGuides[key];
        }
        if (field.required) {
            return '必須項目です。内容を確認して入力してください。';
        }
        if (field.getAttribute('placeholder')) {
            return '入力例を参考に、必要な場合だけ設定してください。';
        }
        return 'この項目の内容を確認して設定します。';
    }

    function guideMessage(field) {
        return field.dataset.guideMessage || helpText(field) || fallbackMessage(field);
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
        balloonTitle.textContent = guideTitle(field);
        balloonBody.textContent = guideMessage(field);
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

    function firstInvalidField(fields) {
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

    forms.forEach(function (form) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        const fields = Array.from(form.elements).filter(isGuideField);
        if (fields.length === 0) {
            return;
        }
        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        form.noValidate = true;

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
            if (!isGuideField(field)) {
                return;
            }
            event.preventDefault();
            guideInvalidField(field);
        }, true);

        form.addEventListener('submit', function (event) {
            const field = firstInvalidField(fields);
            if (!field) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            guideInvalidField(field);
        });

        submitButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (!formIsVisible(form)) {
                    return;
                }
                const field = firstInvalidField(fields);
                if (!field) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                guideInvalidField(field);
            });
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
