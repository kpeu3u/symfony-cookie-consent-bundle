import {test} from 'node:test';
import assert from 'node:assert/strict';
import {JSDOM} from 'jsdom';

const dom = new JSDOM('', {url: 'https://example.test'});
globalThis.document = dom.window.document;
globalThis.CustomEvent = dom.window.CustomEvent;
globalThis.FormData = dom.window.FormData;
const {initializeCookieConsent} = await import('../../assets/js/cookie-consent.mjs');

function setup() {
    document.body.innerHTML = `<dialog><div class="cookie-consent">
        <section class="cookie-consent-simple"></section><section class="cookie-consent-detail"></section>
        <button class="js-show-settings"></button>
        <form class="cookie-consent__form" action="/custom/consent">
        <button name="consent_simple[accept_all]" value="1">Accept</button></form>
    </div></dialog>`;
    const dialog = document.querySelector('dialog');
    dialog.showModal = () => { dialog.open = true; };
    dialog.close = () => { dialog.open = false; };
    initializeCookieConsent();
    initializeCookieConsent();
    return document.querySelector('form');
}

test('sends one request to configured action with clicked button and dismisses banner', async () => {
    const form = setup();
    let calls = 0;
    globalThis.fetch = async (url, options) => {
        calls++;
        assert.equal(url, 'https://example.test/custom/consent');
        assert.equal(options.body.get('consent_simple[accept_all]'), '1');
        return {ok: true};
    };
    const success = new Promise(resolve => document.addEventListener('cookie-consent-form-submit-successful', resolve, {once: true}));
    form.dispatchEvent(new dom.window.SubmitEvent('submit', {cancelable: true, submitter: form.querySelector('button')}));
    await success;
    assert.equal(calls, 1);
    assert.equal(document.querySelector('dialog'), null);
});

test('failed response keeps consent visible and permits retry', async () => {
    const form = setup();
    globalThis.fetch = async () => ({ok: false, status: 400});
    const originalError = console.error;
    console.error = () => {};
    try {
        const failed = new Promise(resolve => document.addEventListener('cookie-consent-form-submit-failed', resolve, {once: true}));
        form.dispatchEvent(new dom.window.SubmitEvent('submit', {cancelable: true, submitter: form.querySelector('button')}));
        await failed;
        assert.ok(document.querySelector('dialog').open);
        assert.equal(form.dataset.submitting, undefined);
    } finally { console.error = originalError; }
});

test('pages without a banner initialize safely', () => {
    document.body.innerHTML = '';
    assert.doesNotThrow(() => initializeCookieConsent());
});
