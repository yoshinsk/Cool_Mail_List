/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\public\assets\js\app.js
 * UI補助用の最小JavaScript。現時点ではフォームの二重送信を抑止する。
 */

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
