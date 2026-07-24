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
