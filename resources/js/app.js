/**
 * GC-Stats — Application JavaScript entry point
 *
 * Bundled and loaded on every page via Vite.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

/**
 * Convert match date/time elements from UTC to the visitor's local timezone.
 *
 * Looks for elements with `[data-utc-datetime]` (an ISO-8601 UTC timestamp)
 * and rewrites their `.js-match-date` / `.js-match-time` children using the
 * browser's detected locale and timezone.
 */
function localizeMatchTimes() {
    const locale = document.documentElement.lang || undefined;
    const timeZone = GCS.getTimezone();

    document.querySelectorAll('[data-utc-datetime]').forEach((el) => {
        const date = new Date(el.dataset.utcDatetime);
        if (Number.isNaN(date.getTime())) return;

        const dateEl = el.querySelector('.js-match-date');
        const timeEl = el.querySelector('.js-match-time');

        if (dateEl) {
            dateEl.textContent = new Intl.DateTimeFormat(locale, {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                timeZone,
            }).format(date);
        }

        if (timeEl) {
            timeEl.textContent = new Intl.DateTimeFormat(locale, {
                hour: '2-digit',
                minute: '2-digit',
                hour12: GCS.getTimeFormat() === '12h',
                timeZone,
            }).format(date);
        }
    });
}

document.addEventListener('DOMContentLoaded', localizeMatchTimes);

/**
 * User preferences (theme & timezone)
 *
 * Persisted in localStorage and applied via a `data-theme` attribute on
 * <html>. The "dark" theme is the default (no attribute set). A small
 * inline snippet in the layout head applies the saved theme before first
 * paint to avoid a flash of the default theme.
 */
const GCS_THEME_KEY = 'gcs_theme';
const GCS_ACCENT_KEY = 'gcs_accent';
const GCS_TIMEZONE_KEY = 'gcs_timezone';
const GCS_TIME_FORMAT_KEY = 'gcs_time_format';

// Preferences are persisted as numeric indexes into these tables rather
// than as full strings, to keep localStorage entries as light as possible.
const GCS_THEMES = ['dark', 'white'];
const GCS_ACCENTS = ['none', 'pride'];
const GCS_TIME_FORMATS = ['24h', '12h'];
const GCS_TIMEZONES = [
    'UTC',
    'Europe/London',
    'Europe/Paris',
    'Europe/Berlin',
    'Europe/Moscow',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'America/Sao_Paulo',
    'Asia/Dubai',
    'Asia/Kolkata',
    'Asia/Shanghai',
    'Asia/Seoul',
    'Asia/Tokyo',
    'Australia/Sydney',
];

function readIndexed(key, values, fallback) {
    const index = parseInt(localStorage.getItem(key), 10);
    return values[index] ?? fallback;
}

function writeIndexed(key, values, value) {
    const index = values.indexOf(value);
    localStorage.setItem(key, index === -1 ? 0 : index);
}

window.GCS = window.GCS || {};

window.GCS.getTheme = function () {
    return readIndexed(GCS_THEME_KEY, GCS_THEMES, 'dark');
};

window.GCS.setTheme = function (theme) {
    writeIndexed(GCS_THEME_KEY, GCS_THEMES, theme);

    if (theme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
};

window.GCS.getAccent = function () {
    return readIndexed(GCS_ACCENT_KEY, GCS_ACCENTS, 'none');
};

window.GCS.setAccent = function (accent) {
    writeIndexed(GCS_ACCENT_KEY, GCS_ACCENTS, accent);

    if (accent === 'none') {
        document.documentElement.removeAttribute('data-accent');
    } else {
        document.documentElement.setAttribute('data-accent', accent);
    }
};

window.GCS.getTimezone = function () {
    return readIndexed(GCS_TIMEZONE_KEY, GCS_TIMEZONES, null) || Intl.DateTimeFormat().resolvedOptions().timeZone;
};

window.GCS.setTimezone = function (timeZone) {
    writeIndexed(GCS_TIMEZONE_KEY, GCS_TIMEZONES, timeZone);
    localizeMatchTimes();
};

window.GCS.getTimeFormat = function () {
    return readIndexed(GCS_TIME_FORMAT_KEY, GCS_TIME_FORMATS, '24h');
};

window.GCS.setTimeFormat = function (format) {
    writeIndexed(GCS_TIME_FORMAT_KEY, GCS_TIME_FORMATS, format);
    localizeMatchTimes();
};

window.GCS.getTimezones = function () {
    return GCS_TIMEZONES.slice();
};

/**
 * Account security (two-factor authentication & passkeys)
 *
 * Talks directly to Fortify's and Laravel Passkeys' JSON endpoints. Any
 * mutating request behind the `password.confirm` middleware answers 423
 * when the session's password confirmation has expired — rather than
 * following the redirect (which breaks for non-GET requests), we surface
 * an inline password prompt and replay the original action once confirmed.
 */
window.GCS.apiFetch = async function (url, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    return fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers || {}),
        },
    });
};

function base64UrlToBuffer(base64url) {
    const padding = '='.repeat((4 - (base64url.length % 4)) % 4);
    const base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const bytes = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
    return bytes.buffer;
}

function bufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = '';
    for (let i = 0; i < bytes.byteLength; i++) str += String.fromCharCode(bytes[i]);
    return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function decodePasskeyCreationOptions(options) {
    return {
        ...options,
        challenge: base64UrlToBuffer(options.challenge),
        user: { ...options.user, id: base64UrlToBuffer(options.user.id) },
        excludeCredentials: (options.excludeCredentials || []).map((c) => ({ ...c, id: base64UrlToBuffer(c.id) })),
    };
}

function encodePasskeyCreationCredential(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
            attestationObject: bufferToBase64Url(credential.response.attestationObject),
            transports: credential.response.getTransports ? credential.response.getTransports() : [],
        },
    };
}

window.accountSecurity = function (config) {
    return {
        routes: config.routes,

        twoFactorEnabled: config.twoFactorEnabled,
        twoFactorPending: config.twoFactorPending,
        qrSvg: '',
        secretKey: '',
        recoveryCodes: [],
        showRecoveryCodes: false,
        code: '',
        twoFactorError: '',
        twoFactorLoading: false,

        passkeys: config.passkeys,
        passkeyName: '',
        passkeyError: '',
        passkeyLoading: false,

        confirmOpen: false,
        confirmPassword: '',
        confirmError: '',
        confirmAction: null,

        init() {
            if (this.twoFactorPending) {
                this.loadTwoFactorSetup();
            }
        },

        // Runs a request; on 423 (password confirmation expired) it opens
        // the modal and stashes `retry` to be re-run once confirmed.
        async guarded(url, options, retry) {
            const response = await window.GCS.apiFetch(url, options);

            if (response.status === 423) {
                this.confirmAction = retry;
                this.confirmError = '';
                this.confirmOpen = true;
                return null;
            }

            return response;
        },

        async submitConfirmPassword() {
            this.confirmError = '';

            const response = await window.GCS.apiFetch(this.routes.confirmPassword, {
                method: 'POST',
                body: JSON.stringify({ password: this.confirmPassword }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                this.confirmError = data.errors?.password?.[0] || data.message || 'Invalid password.';

                return;
            }

            this.confirmOpen = false;
            this.confirmPassword = '';

            const action = this.confirmAction;
            this.confirmAction = null;

            if (action) await action();
        },

        async loadTwoFactorSetup() {
            const [qrResponse, keyResponse] = await Promise.all([
                window.GCS.apiFetch(this.routes.twoFactorQrCode),
                window.GCS.apiFetch(this.routes.twoFactorSecretKey),
            ]);

            if (qrResponse.ok) {
                this.qrSvg = (await qrResponse.json()).svg || '';
            }

            if (keyResponse.ok) {
                this.secretKey = (await keyResponse.json()).secretKey || '';
            }
        },

        async enableTwoFactor() {
            this.twoFactorError = '';
            this.twoFactorLoading = true;

            const response = await this.guarded(this.routes.twoFactorEnable, { method: 'POST' }, () => this.enableTwoFactor());

            this.twoFactorLoading = false;

            if (response && response.ok) {
                this.twoFactorPending = true;
                await this.loadTwoFactorSetup();
            }
        },

        async confirmTwoFactorCode() {
            this.twoFactorError = '';
            this.twoFactorLoading = true;

            const response = await this.guarded(this.routes.twoFactorConfirm, {
                method: 'POST',
                body: JSON.stringify({ code: this.code }),
            }, () => this.confirmTwoFactorCode());

            this.twoFactorLoading = false;

            if (!response) return;

            if (response.ok) {
                this.twoFactorEnabled = true;
                this.twoFactorPending = false;
                this.code = '';
                await this.loadRecoveryCodes();
                this.showRecoveryCodes = true;
            } else {
                const data = await response.json().catch(() => ({}));
                this.twoFactorError = data.errors?.code?.[0] || data.message || 'Invalid code.';
            }
        },

        async loadRecoveryCodes() {
            const response = await window.GCS.apiFetch(this.routes.twoFactorRecoveryCodes);

            if (response.ok) {
                this.recoveryCodes = await response.json();
            }
        },

        async toggleRecoveryCodes() {
            if (!this.showRecoveryCodes && this.recoveryCodes.length === 0) {
                await this.loadRecoveryCodes();
            }

            this.showRecoveryCodes = !this.showRecoveryCodes;
        },

        async regenerateRecoveryCodes() {
            const response = await this.guarded(this.routes.twoFactorRecoveryCodes, { method: 'POST' }, () => this.regenerateRecoveryCodes());

            if (response && response.ok) {
                await this.loadRecoveryCodes();
            }
        },

        async disableTwoFactor() {
            this.twoFactorLoading = true;

            const response = await this.guarded(this.routes.twoFactorDisable, { method: 'DELETE' }, () => this.disableTwoFactor());

            this.twoFactorLoading = false;

            if (response && response.ok) {
                this.twoFactorEnabled = false;
                this.twoFactorPending = false;
                this.qrSvg = '';
                this.secretKey = '';
                this.recoveryCodes = [];
                this.showRecoveryCodes = false;
            }
        },

        async registerPasskey() {
            this.passkeyError = '';

            if (!this.passkeyName.trim()) {
                this.passkeyError = 'Please name this passkey.';

                return;
            }

            if (!window.PublicKeyCredential) {
                this.passkeyError = 'Passkeys are not supported on this device or browser.';

                return;
            }

            this.passkeyLoading = true;

            try {
                const optionsResponse = await this.guarded(this.routes.passkeyOptions, {}, () => this.registerPasskey());

                if (!optionsResponse) {
                    this.passkeyLoading = false;

                    return;
                }

                if (!optionsResponse.ok) {
                    throw new Error('Could not start passkey registration.');
                }

                const { options } = await optionsResponse.json();
                const credential = await navigator.credentials.create({ publicKey: decodePasskeyCreationOptions(options) });

                const response = await window.GCS.apiFetch(this.routes.passkeyStore, {
                    method: 'POST',
                    body: JSON.stringify({
                        name: this.passkeyName,
                        credential: encodePasskeyCreationCredential(credential),
                    }),
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.errors?.name?.[0] || data.message || 'Could not save this passkey.');
                }

                const data = await response.json();
                this.passkeys.push({ id: data.id, name: data.name });
                this.passkeyName = '';
            } catch (error) {
                if (error.name !== 'NotAllowedError') {
                    this.passkeyError = error.message || 'Passkey registration failed.';
                }
            } finally {
                this.passkeyLoading = false;
            }
        },

        async deletePasskey(id) {
            const response = await this.guarded(`${this.routes.passkeyDestroyBase}/${id}`, { method: 'DELETE' }, () => this.deletePasskey(id));

            if (response && response.ok) {
                this.passkeys = this.passkeys.filter((passkey) => passkey.id !== id);
            }
        },
    };
};
