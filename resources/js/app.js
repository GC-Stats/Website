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
