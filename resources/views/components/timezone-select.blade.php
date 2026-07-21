{{--
    GC-Stats — Timezone-aware datetime picker

    Pairs a searchable IANA timezone combobox with a plain datetime-local
    input. The admin picks a timezone and types the local kickoff time; on
    every change the pair is converted client-side (Intl.DateTimeFormat
    offset math, no library) into a UTC value written to the real hidden
    `name` input that actually gets submitted — the server only ever sees
    UTC, no schema change needed. When editing an existing match, the
    initial UTC value is re-expressed in the browser's local timezone by
    default (the original entry timezone isn't stored anywhere).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['name', 'label', 'value' => null, 'required' => false])

<div
    x-data="{
        timezones: {{ \Illuminate\Support\Js::from(\DateTimeZone::listIdentifiers()) }},
        tzQuery: '',
        tzOpen: false,
        selectedTz: Intl.DateTimeFormat().resolvedOptions().timeZone,
        localValue: '',
        utcValue: {{ \Illuminate\Support\Js::from($value) }},

        get filteredTimezones() {
            if (this.tzQuery.length < 1) return this.timezones.slice(0, 50);
            const q = this.tzQuery.toLowerCase();
            return this.timezones.filter(tz => tz.toLowerCase().includes(q)).slice(0, 50);
        },

        tzOffsetMinutes(timeZone, date) {
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone, hourCycle: 'h23',
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
            }).formatToParts(date).reduce((acc, p) => { acc[p.type] = p.value; return acc; }, {});
            const asUTC = Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second);
            return (asUTC - date.getTime()) / 60000;
        },

        localToUTC(localStr, timeZone) {
            if (! localStr) return null;
            const [datePart, timePart] = localStr.split('T');

            // The 1900-01-01 sentinel means the date is unknown (see
            // MatchDisplay::UNKNOWN_DATE) — timezone conversion would shift
            // it off that exact date and break the sentinel check, so it's
            // stored verbatim, untouched by the selected timezone.
            if (datePart === '1900-01-01') {
                return new Date(`${datePart}T${timePart}:00Z`);
            }

            const [y, m, d] = datePart.split('-').map(Number);
            const [hh, mm] = timePart.split(':').map(Number);
            const guess = new Date(Date.UTC(y, m - 1, d, hh, mm, 0));
            const offset1 = this.tzOffsetMinutes(timeZone, guess);
            let actual = new Date(guess.getTime() - offset1 * 60000);
            const offset2 = this.tzOffsetMinutes(timeZone, actual);
            if (offset2 !== offset1) actual = new Date(guess.getTime() - offset2 * 60000);
            return actual;
        },

        utcToLocalInTZ(utcDate, timeZone) {
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone, hourCycle: 'h23',
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit',
            }).formatToParts(utcDate).reduce((acc, p) => { acc[p.type] = p.value; return acc; }, {});
            return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
        },

        recompute() {
            const utc = this.localToUTC(this.localValue, this.selectedTz);
            this.utcValue = utc ? utc.toISOString().slice(0, 19).replace('T', ' ') : '';
        },

        selectTz(tz) {
            this.selectedTz = tz;
            this.tzQuery = '';
            this.tzOpen = false;
            this.recompute();
        },

        init() {
            if (this.utcValue) {
                this.localValue = this.utcValue.startsWith('1900-01-01')
                    ? this.utcValue.slice(0, 16).replace(' ', 'T')
                    : this.utcToLocalInTZ(new Date(this.utcValue.replace(' ', 'T') + 'Z'), this.selectedTz);
            }
        },
    }"
>
    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ $label }}</span>

    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
        <input type="datetime-local" x-model="localValue" @change="recompute()" @if($required) required @endif
               class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

        <div class="relative">
            <button type="button" @click="tzOpen = ! tzOpen"
                    class="w-full h-[42px] sm:w-56 text-left bg-white/5 border border-white/10 rounded-lg px-4 text-xs text-white focus:outline-none focus:border-gc-yellow transition truncate">
                <span x-text="selectedTz"></span>
            </button>

            <div x-show="tzOpen" x-cloak @click.outside="tzOpen = false"
                 class="absolute z-10 mt-1 w-full sm:w-72 right-0 bg-bg-card border border-white/10 rounded-lg shadow-xl">
                <input type="text" x-model="tzQuery" x-ref="tzSearch" placeholder="{{ __('admin.matches.timezone_search') }}"
                       class="w-full bg-white/5 border-b border-white/10 rounded-t-sm px-3 py-2 text-xs text-white focus:outline-none">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="tz in filteredTimezones" :key="tz">
                        <button type="button" @click="selectTz(tz)" x-text="tz"
                                class="block w-full text-left px-3 py-1.5 text-xs text-white hover:bg-white/5 transition"></button>
                    </template>
                    <p x-show="filteredTimezones.length === 0" class="px-3 py-2 text-xs text-gray-500">—</p>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" x-model="utcValue">
</div>
