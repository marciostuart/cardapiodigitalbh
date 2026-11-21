(function () {
    'use strict';

    const root = document.querySelector('[data-cookie-consent]');
    if (!root) return;

    const banner = root.querySelector('[data-cookie-banner]');
    const settings = root.querySelector('[data-cookie-settings]');
    const manage = root.querySelector('[data-cookie-manage]');
    const analytics = root.querySelector('[data-cookie-analytics]');
    const marketing = root.querySelector('[data-cookie-marketing]');
    const save = root.querySelector('[data-cookie-save]');
    const name = 'cd_tracking_consent';

    function readChoice() {
        const prefix = `${name}=`;
        const item = document.cookie.split(';').map((part) => part.trim()).find((part) => part.startsWith(prefix));
        const value = item ? item.slice(prefix.length) : '';
        return /^a[01]m[01]$/.test(value) ? value : '';
    }

    function writeChoice(value) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `${name}=${value}; Max-Age=15552000; Path=/; SameSite=Lax${secure}`;
        banner.hidden = true;
        manage.hidden = false;
        window.location.reload();
    }

    function loadSettings(value) {
        analytics.checked = value.startsWith('a1');
        marketing.checked = value.endsWith('m1');
    }

    function openSettings() {
        loadSettings(readChoice());
        banner.hidden = false;
        settings.hidden = false;
        root.querySelector('[data-cookie-customize]').hidden = true;
        save.hidden = false;
        analytics.focus();
    }

    const choice = readChoice();
    if (choice) {
        banner.hidden = true;
        manage.hidden = false;
        loadSettings(choice);
    }

    root.querySelector('[data-cookie-accept]').addEventListener('click', () => writeChoice('a1m1'));
    root.querySelector('[data-cookie-necessary]').addEventListener('click', () => writeChoice('a0m0'));
    root.querySelector('[data-cookie-customize]').addEventListener('click', openSettings);
    root.querySelector('[data-cookie-save]').addEventListener('click', () => {
        writeChoice(`a${analytics.checked ? '1' : '0'}m${marketing.checked ? '1' : '0'}`);
    });
    manage.addEventListener('click', openSettings);
})();
