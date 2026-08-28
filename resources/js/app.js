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
const GCS_ACCENTS_KEY = 'gcs_accents';
const GCS_WINGMAN_UNLOCKED_KEY = 'gcs_wingman_unlocked';
const GCS_TIMEZONE_KEY = 'gcs_timezone';
const GCS_TIME_FORMAT_KEY = 'gcs_time_format';

// Preferences are persisted as numeric indexes into these tables rather
// than as full strings, to keep localStorage entries as light as possible.
// Accents are the exception: any number of them can be active at once, so
// they're persisted as a JSON array of slugs (see GCS_KNOWN_ACCENTS) and
// applied as a space-separated `data-accent` attribute (`[data-accent~=]`
// selectors in resources/css/accent/*.css match one token in that list).
const GCS_THEMES = ['dark', 'white'];
const GCS_KNOWN_ACCENTS = ['pride', 'wingman'];
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

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch('/preferences/theme', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ theme }),
        keepalive: true,
    })
        .catch(() => {})
        .finally(() => window.location.reload());
};

function readAccents() {
    let stored;

    try {
        stored = JSON.parse(localStorage.getItem(GCS_ACCENTS_KEY) || '[]');
    } catch (e) {
        stored = [];
    }

    return Array.isArray(stored) ? stored.filter((a) => GCS_KNOWN_ACCENTS.includes(a)) : [];
}

function applyAccents(accents) {
    if (accents.length === 0) {
        document.documentElement.removeAttribute('data-accent');
    } else {
        document.documentElement.setAttribute('data-accent', accents.join(' '));
    }
}

function writeAccents(accents) {
    localStorage.setItem(GCS_ACCENTS_KEY, JSON.stringify(accents));
    applyAccents(accents);

    return accents;
}

window.GCS.getAccents = function () {
    return readAccents();
};

/** Toggles one accent on/off, keeping any other active accents untouched. */
window.GCS.toggleAccent = function (accent) {
    const current = readAccents();
    const next = current.includes(accent) ? current.filter((a) => a !== accent) : [...current, accent];

    return writeAccents(next);
};

/** "None" in the config panel: deselects every active accent. */
window.GCS.clearAccents = function () {
    return writeAccents([]);
};

/**
 * "Wingman" accent — a hidden easter egg unlocked from the "More plants"
 * stats card (see stats-insights.blade.php). Once unlocked it stays
 * unlocked (persisted in localStorage): the navbar logo becomes visible
 * and the accent becomes pickable in the config panel. The unlock also
 * turns the accent on immediately, alongside whatever else is already
 * active, and fires a `wingman-unlocked` window event so the navbar — a
 * separate Alpine component — can react without a page reload. Idempotent:
 * does nothing once already unlocked, so it won't keep re-enabling the
 * accent every time the easter egg is re-triggered after the visitor turns
 * it back off.
 */
window.GCS.isWingmanUnlocked = function () {
    return localStorage.getItem(GCS_WINGMAN_UNLOCKED_KEY) === '1';
};

window.GCS.unlockWingman = function () {
    if (window.GCS.isWingmanUnlocked()) {
        return;
    }

    localStorage.setItem(GCS_WINGMAN_UNLOCKED_KEY, '1');

    const current = readAccents();
    if (!current.includes('wingman')) {
        writeAccents([...current, 'wingman']);
    }

    window.dispatchEvent(new CustomEvent('wingman-unlocked'));
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
 * "67 mode" — a silly options toggle that rewrites every standalone "67"
 * (and "6.7") found in the page text into "68" (and "6.8"). Kept simple on
 * purpose: it walks text nodes once on load/toggle and again whenever new
 * content is added to the DOM (Livewire updates, AJAX-loaded stats tables).
 */
const GCS_67_KEY = 'gcs_67_mode';
let gcs67Observer = null;

function apply67ToTextNode(node) {
    if (!node.nodeValue || (!node.nodeValue.includes('67') && !node.nodeValue.includes('6.7'))) return;

    node.nodeValue = node.nodeValue
        .replace(/\b6\.7\b/g, '6.8')
        .replace(/\b67\b/g, '68');
}

function apply67ToTree(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            const tag = node.parentElement?.tagName;
            return tag === 'SCRIPT' || tag === 'STYLE' ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
        },
    });

    let current;
    while ((current = walker.nextNode())) {
        apply67ToTextNode(current);
    }
}

function start67Observer() {
    if (gcs67Observer) return;

    gcs67Observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.TEXT_NODE) {
                    apply67ToTextNode(node);
                } else if (node.nodeType === Node.ELEMENT_NODE) {
                    apply67ToTree(node);
                }
            });
        });
    });

    gcs67Observer.observe(document.body, { childList: true, subtree: true });
}

function stop67Observer() {
    gcs67Observer?.disconnect();
    gcs67Observer = null;
}

window.GCS.is67ModeEnabled = function () {
    return localStorage.getItem(GCS_67_KEY) === '1';
};

window.GCS.set67Mode = function (enabled) {
    localStorage.setItem(GCS_67_KEY, enabled ? '1' : '0');

    if (enabled) {
        apply67ToTree(document.body);
        start67Observer();
    } else {
        stop67Observer();
        window.location.reload();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.GCS.is67ModeEnabled()) {
        apply67ToTree(document.body);
        start67Observer();
    }
});

/**
 * Admin: manual map stat entry
 *
 * Lets an admin type in a map's player stats (and, optionally, per-round
 * stats) by hand — used when a map has no linked Riot match ID to fetch
 * from (LAN matches, matches too old for the relay's cache, etc). Ported
 * from the old dashboard's Matches/Map.vue.
 */
window.GCS.manualMapStats = function (config) {
    function emptyPlayerStatRow(playerId, teamId) {
        return { player_id: playerId ?? '', team_id: teamId ?? '', agent_name: '', kills: 0, deaths: 0, assists: 0, acs: '', adr: '', kast_percentage: '', first_kills: '', first_deaths: '', headshot_percentage: '' };
    }

    function emptyRoundPlayerStatRow(playerId, teamId) {
        return { player_id: playerId ?? '', team_id: teamId ?? '', kills: 0, assists: 0, score: 0, loadout_value: -1, economy_spent: -1, economy_remaining: -1, weapon_id: '', armor: '' };
    }

    function initPlayerStatsRows() {
        const teamAId = config.teamA?.id ?? '';
        const teamBId = config.teamB?.id ?? '';

        let rows;

        if (config.initialPlayerStats.length > 0) {
            rows = config.initialPlayerStats.map((stat) => ({ ...stat }));
        } else {
            rows = [];
            (config.teamAPlayers ?? []).forEach((p) => rows.push(emptyPlayerStatRow(p.id, teamAId)));
            (config.teamBPlayers ?? []).forEach((p) => rows.push(emptyPlayerStatRow(p.id, teamBId)));
        }

        const countFor = (teamId) => rows.filter((r) => String(r.team_id) === String(teamId)).length;

        while (teamAId !== '' && countFor(teamAId) < 5) rows.push(emptyPlayerStatRow('', teamAId));
        while (teamBId !== '' && countFor(teamBId) < 5) rows.push(emptyPlayerStatRow('', teamBId));

        return rows;
    }

    return {
        playerStats: initPlayerStatsRows(),
        rounds: config.initialRounds.map((r) => ({ ...r, player_stats: (r.player_stats ?? []).map((ps) => ({ ...ps })) })),
        editingRoundIndex: null,
        submitting: false,
        error: '',

        byTeam(rows) {
            return {
                teamA: rows.filter((r) => String(r.team_id) === String(config.teamA?.id)),
                teamB: rows.filter((r) => String(r.team_id) === String(config.teamB?.id)),
            };
        },

        get mainStatsByTeam() {
            return this.byTeam(this.playerStats);
        },

        removePlayerRow(stat) {
            const idx = this.playerStats.indexOf(stat);
            if (idx !== -1) this.playerStats.splice(idx, 1);
        },

        get editingRound() {
            return this.editingRoundIndex !== null ? this.rounds[this.editingRoundIndex] : null;
        },

        get editingRoundByTeam() {
            return this.editingRound ? this.byTeam(this.editingRound.player_stats) : { teamA: [], teamB: [] };
        },

        defaultRoundPlayerStats() {
            const rows = [];
            this.mainStatsByTeam.teamA.forEach((s) => rows.push(emptyRoundPlayerStatRow(s.player_id, s.team_id)));
            this.mainStatsByTeam.teamB.forEach((s) => rows.push(emptyRoundPlayerStatRow(s.player_id, s.team_id)));
            return rows;
        },

        openRoundEditor(index) {
            const round = this.rounds[index];
            if (!round.player_stats || round.player_stats.length === 0) {
                round.player_stats = this.defaultRoundPlayerStats();
            }
            this.editingRoundIndex = index;
        },

        removeRoundPlayerRow(ps) {
            const idx = this.editingRound.player_stats.indexOf(ps);
            if (idx !== -1) this.editingRound.player_stats.splice(idx, 1);
        },

        addRound() {
            const next = this.rounds.length > 0 ? Math.max(...this.rounds.map((r) => Number(r.round_number) || 0)) + 1 : 1;
            this.rounds.push({ round_number: next, winning_team: '', win_type: '', player_stats: [] });
        },

        removeRound(index) {
            this.rounds.splice(index, 1);
        },

        async submit() {
            this.error = '';
            this.submitting = true;

            const payload = {
                player_stats: this.playerStats.filter((s) => s.player_id !== '' && s.team_id !== '').map((s) => {
                    const out = {
                        player_id: parseInt(s.player_id),
                        team_id: parseInt(s.team_id),
                        agent_name: s.agent_name,
                        kills: parseInt(s.kills) || 0,
                        deaths: parseInt(s.deaths) || 0,
                        assists: parseInt(s.assists) || 0,
                    };
                    if (s.acs !== '' && s.acs !== null) out.acs = parseFloat(s.acs);
                    if (s.adr !== '' && s.adr !== null) out.adr = parseFloat(s.adr);
                    if (s.kast_percentage !== '' && s.kast_percentage !== null) out.kast_percentage = parseFloat(s.kast_percentage);
                    if (s.first_kills !== '' && s.first_kills !== null) out.first_kills = parseInt(s.first_kills);
                    if (s.first_deaths !== '' && s.first_deaths !== null) out.first_deaths = parseInt(s.first_deaths);
                    if (s.headshot_percentage !== '' && s.headshot_percentage !== null) out.headshot_percentage = parseFloat(s.headshot_percentage);
                    return out;
                }),
                rounds: this.rounds.filter((r) => r.round_number !== '' && r.winning_team !== '').map((r) => ({
                    round_number: parseInt(r.round_number),
                    winning_team: parseInt(r.winning_team),
                    win_type: r.win_type || null,
                    player_stats: (r.player_stats || []).filter((ps) => ps.player_id !== '').map((ps) => ({
                        player_id: parseInt(ps.player_id),
                        kills: parseInt(ps.kills) || 0,
                        assists: parseInt(ps.assists) || 0,
                        score: parseInt(ps.score) || 0,
                        loadout_value: ps.loadout_value === '' || ps.loadout_value === null ? null : parseInt(ps.loadout_value),
                        economy_spent: ps.economy_spent === '' || ps.economy_spent === null ? null : parseInt(ps.economy_spent),
                        economy_remaining: ps.economy_remaining === '' || ps.economy_remaining === null ? null : parseInt(ps.economy_remaining),
                        weapon_id: ps.weapon_id || null,
                        armor: ps.armor || null,
                    })),
                })),
            };

            try {
                const response = await window.GCS.apiFetch(config.updateUrl, {
                    method: 'PUT',
                    body: JSON.stringify(payload),
                });

                if (response.ok) {
                    window.location.reload();
                    return;
                }

                const data = await response.json().catch(() => ({}));
                this.error = Object.values(data.errors ?? {})[0]?.[0] ?? data.message ?? config.errorText;
            } catch (e) {
                this.error = config.errorText;
            } finally {
                this.submitting = false;
            }
        },
    };
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

function decodePasskeyRequestOptions(options) {
    return {
        ...options,
        challenge: base64UrlToBuffer(options.challenge),
        allowCredentials: (options.allowCredentials || []).map((c) => ({ ...c, id: base64UrlToBuffer(c.id) })),
    };
}

function encodePasskeyAssertionCredential(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
            authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
            signature: bufferToBase64Url(credential.response.signature),
            userHandle: credential.response.userHandle ? bufferToBase64Url(credential.response.userHandle) : null,
        },
    };
}

/**
 * Passwordless login via a previously-registered passkey (see
 * window.accountSecurity's registerPasskey for the registration side).
 * No password-confirmation gate here — these routes are guest-accessible.
 */
window.passkeyLogin = function (config) {
    return {
        optionsUrl: config.optionsUrl,
        loginUrl: config.loginUrl,
        unsupportedText: config.unsupportedText,
        errorText: config.errorText,
        loading: false,
        error: '',

        async signIn() {
            this.error = '';

            if (!window.PublicKeyCredential) {
                this.error = this.unsupportedText;

                return;
            }

            this.loading = true;

            try {
                const optionsResponse = await window.GCS.apiFetch(this.optionsUrl);

                if (!optionsResponse.ok) {
                    throw new Error(this.errorText);
                }

                const { options } = await optionsResponse.json();
                const credential = await navigator.credentials.get({ publicKey: decodePasskeyRequestOptions(options) });

                const response = await window.GCS.apiFetch(this.loginUrl, {
                    method: 'POST',
                    body: JSON.stringify({ credential: encodePasskeyAssertionCredential(credential) }),
                });

                if (!response.ok) {
                    throw new Error(this.errorText);
                }

                const data = await response.json();
                window.location.href = data.redirect || '/';
            } catch (error) {
                if (error.name !== 'NotAllowedError') {
                    this.error = error.message || this.errorText;
                }
            } finally {
                this.loading = false;
            }
        },
    };
};

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

window.dataExplorer = function (config) {
    return {
        executeUrl: config.executeUrl,
        blocked: config.blocked,

        prompt: '',
        loading: false,
        error: '',
        errorId: '',
        canRetry: false,
        response: null,
        copied: false,

        get resultColumns() {
            const rows = this.response?.result;

            return Array.isArray(rows) && rows.length > 0 ? Object.keys(rows[0]) : [];
        },

        get resultRows() {
            return Array.isArray(this.response?.result) ? this.response.result : [];
        },

        get rawJson() {
            return this.response ? JSON.stringify(this.response, null, 2) : '';
        },

        async submit() {
            this.loading = true;
            this.error = '';
            this.errorId = '';
            this.canRetry = false;
            this.response = null;
            this.copied = false;

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 25000);

            try {
                const res = await window.GCS.apiFetch(this.executeUrl, {
                    method: 'POST',
                    body: JSON.stringify({ prompt: this.prompt }),
                    signal: controller.signal,
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    this.error = data.error || data.message || 'Something went wrong.';
                    this.errorId = data.error_id || '';
                    this.canRetry = Boolean(data.retry);
                    return;
                }

                this.response = data;
            } catch (e) {
                this.error = e.name === 'AbortError' ? 'The request timed out.' : 'Something went wrong.';
                this.canRetry = true;
            } finally {
                clearTimeout(timeout);
                this.loading = false;
            }
        },

        async copyResult() {
            if (!this.rawJson) return;

            await navigator.clipboard.writeText(this.rawJson);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    };
};

window.dataExplorerBuilder = function (config) {
    return {
        executeUrl: config.executeUrl,
        operators: config.operators,

        measuresList: config.schema.measures,
        dimensionsList: config.schema.dimensions,
        measureSearch: '',
        dimensionSearch: '',
        selectedMeasures: [],
        selectedDimensions: [],
        pendingAgg: {},
        filters: [],
        limit: 100,

        loading: false,
        error: '',
        errorId: '',
        canRetry: false,
        response: null,
        copied: false,

        get filteredMeasures() {
            const q = this.measureSearch.trim().toLowerCase();

            return q ? this.measuresList.filter((m) => m.toLowerCase().includes(q)) : this.measuresList;
        },

        // Cube measures are pre-aggregated (Cube has no "pick any field, pick
        // any aggregation" mode — each combination is its own named measure,
        // e.g. matches.avg_team_a_score). Rather than show that flat list of
        // raw names, group by base field + detected aggregation prefix
        // (avg_/total_/max_/min_) so the UI reads as "team_a_score: [Avg]"
        // instead of forcing the user to already know the naming convention.
        get measureGroups() {
            const aggLabels = { avg: 'Avg', total: 'Sum', max: 'Max', min: 'Min' };
            const groups = {};

            for (const full of this.filteredMeasures) {
                const dot = full.indexOf('.');
                const cube = full.slice(0, dot);
                const name = full.slice(dot + 1);

                let base = name;
                let aggLabel = null;

                for (const prefix of Object.keys(aggLabels)) {
                    if (name.startsWith(prefix + '_')) {
                        base = name.slice(prefix.length + 1);
                        aggLabel = aggLabels[prefix];
                        break;
                    }
                }

                if (aggLabel === null) {
                    aggLabel = name === 'count' || name.endsWith('_count') ? 'Count' : name;
                }

                const key = `${cube}.${base}`;
                if (!groups[key]) groups[key] = { key, cube, base, items: [] };
                groups[key].items.push({ full, aggLabel });
            }

            return Object.values(groups).sort((a, b) => a.key.localeCompare(b.key));
        },

        get filteredDimensions() {
            const q = this.dimensionSearch.trim().toLowerCase();

            return q ? this.dimensionsList.filter((d) => d.toLowerCase().includes(q)) : this.dimensionsList;
        },

        // Filters only make sense against fields already picked as a measure
        // or dimension — showing the full catalogue here would let someone
        // filter on a field that isn't even part of the query.
        get selectedFields() {
            return [...this.selectedMeasures, ...this.selectedDimensions];
        },

        get selectedFieldsWithType() {
            return [
                ...this.selectedMeasures.map((field) => ({ field, type: 'measure' })),
                ...this.selectedDimensions.map((field) => ({ field, type: 'dimension' })),
            ];
        },

        get resultColumns() {
            const rows = this.response?.result;

            return Array.isArray(rows) && rows.length > 0 ? Object.keys(rows[0]) : [];
        },

        get resultRows() {
            return Array.isArray(this.response?.result) ? this.response.result : [];
        },

        get rawJson() {
            return this.response ? JSON.stringify(this.response, null, 2) : '';
        },

        toggleMeasure(field) {
            this.selectedMeasures = this.selectedMeasures.includes(field)
                ? this.selectedMeasures.filter((f) => f !== field)
                : [...this.selectedMeasures, field];
        },

        // Picks whichever aggregation is currently selected in that group's
        // dropdown (defaulting to the first available one) and adds it.
        addMeasureFromGroup(group) {
            const chosen = this.pendingAgg[group.key] || group.items[0]?.full;

            if (chosen && !this.selectedMeasures.includes(chosen)) {
                this.selectedMeasures = [...this.selectedMeasures, chosen];
            }
        },

        toggleDimension(field) {
            this.selectedDimensions = this.selectedDimensions.includes(field)
                ? this.selectedDimensions.filter((f) => f !== field)
                : [...this.selectedDimensions, field];
        },

        deselectField(field) {
            this.selectedMeasures = this.selectedMeasures.filter((f) => f !== field);
            this.selectedDimensions = this.selectedDimensions.filter((f) => f !== field);
            this.filters = this.filters.filter((f) => f.member !== field);
        },

        addFilter() {
            this.filters.push({ member: this.selectedFields[0] || '', operator: 'equals', values: '' });
        },

        removeFilter(index) {
            this.filters.splice(index, 1);
        },

        async submit() {
            this.loading = true;
            this.error = '';
            this.errorId = '';
            this.canRetry = false;
            this.response = null;
            this.copied = false;

            const payload = {
                measures: this.selectedMeasures,
                dimensions: this.selectedDimensions,
                filters: this.filters
                    .filter((f) => f.member)
                    .map((f) => ({
                        member: f.member,
                        operator: f.operator,
                        values:
                            f.operator === 'set' || f.operator === 'notSet'
                                ? []
                                : f.values
                                      .split(',')
                                      .map((v) => v.trim())
                                      .filter(Boolean),
                    })),
                limit: this.limit ? parseInt(this.limit, 10) : null,
            };

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 25000);

            try {
                const res = await window.GCS.apiFetch(this.executeUrl, {
                    method: 'POST',
                    body: JSON.stringify(payload),
                    signal: controller.signal,
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    this.error = data.error || data.message || 'Something went wrong.';
                    this.errorId = data.error_id || '';
                    this.canRetry = Boolean(data.retry);
                    return;
                }

                this.response = data;
            } catch (e) {
                this.error = e.name === 'AbortError' ? 'The request timed out.' : 'Something went wrong.';
                this.canRetry = true;
            } finally {
                clearTimeout(timeout);
                this.loading = false;
            }
        },

        async copyResult() {
            if (!this.rawJson) return;

            await navigator.clipboard.writeText(this.rawJson);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    };
};
