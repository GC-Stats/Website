{{--
    GC-Stats — Shared Alpine data factory for stats tables

    Backs both tournament/stats.blade.php (rows = players, played_agents[])
    and player/stats.blade.php (rows = agents, agent_name) — sort, agent/role
    filter (layered on top of the server-side phase/period filters), a
    total-vs-average display mode, and column-visibility (persisted to
    localStorage, keyed by `storageKey` so the tournament and player pages
    don't clobber each other's preference).

    Every stat is stored server-side as a total_<key>/avg_<key> pair (see
    App\Services\UtilityStatsAggregator); columns here only carry the base
    <key>, and read whichever prefix `mode` points at.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<script>
    function statsWeaponSlug(weapon) {
        return (weapon || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }

    function statsTable(initialStats, weapons, storageKey) {
        const defaultCols = ['acs', 'kd', 'kda', 'adr', 'kast', 'first_kills', 'first_deaths', 'hs'];

        const baseCols = [
            { key: 'acs', name: {{ Js::from(__('match.stats.acs')) }} },
            { key: 'kills', name: {{ Js::from(__('match.stats.kills')) }} },
            { key: 'deaths', name: {{ Js::from(__('match.stats.deaths')) }} },
            { key: 'assists', name: {{ Js::from(__('match.stats.assists')) }} },
            { key: 'kd', name: {{ Js::from(__('match.stats.kd')) }} },
            { key: 'kda', name: {{ Js::from(__('match.stats.kda')) }} },
            { key: 'adr', name: {{ Js::from(__('match.stats.adr')) }} },
            { key: 'kast', name: {{ Js::from(__('match.stats.kast_percentage')) }} },
            { key: 'first_kills', name: {{ Js::from(__('match.stats.first_kills')) }} },
            { key: 'first_deaths', name: {{ Js::from(__('match.stats.first_deaths')) }} },
            { key: 'hs', name: {{ Js::from(__('match.stats.headshot_percentage')) }} },
            { key: 'ability1_kills', name: {{ Js::from(__('match.stats.ability1_kills')) }} },
            { key: 'ability2_kills', name: {{ Js::from(__('match.stats.ability2_kills')) }} },
            { key: 'grenade_kills', name: {{ Js::from(__('match.stats.grenade_kills')) }} },
            { key: 'ultimate_kills', name: {{ Js::from(__('match.stats.ultimate_kills')) }} },
            { key: 'fall_deaths', name: {{ Js::from(__('match.stats.fall_deaths')) }} },
            { key: 'multi_2k', name: {{ Js::from(__('match.stats.multi_2k')) }} },
            { key: 'multi_3k', name: {{ Js::from(__('match.stats.multi_3k')) }} },
            { key: 'multi_4k', name: {{ Js::from(__('match.stats.multi_4k')) }} },
            { key: 'multi_5k', name: {{ Js::from(__('match.stats.multi_5k')) }} },
            { key: 'clutches', name: {{ Js::from(__('match.stats.clutches')) }} },
            { key: 'plants', name: {{ Js::from(__('match.stats.plants')) }} },
            { key: 'defuses', name: {{ Js::from(__('match.stats.defuses')) }} },
            ...weapons.map(w => ({ key: 'weapon_' + statsWeaponSlug(w), name: w })),
        ];

        return {
            stats: initialStats,
            sortCol: 'acs',
            sortAsc: false,
            colPickerOpen: false,
            mode: 'avg',
            allCols: baseCols,
            visibleCols: [],

            init() {
                try {
                    const stored = localStorage.getItem('gcstats.stats.cols.' + storageKey);
                    this.visibleCols = stored ? JSON.parse(stored) : [...defaultCols];
                } catch (e) {
                    this.visibleCols = [...defaultCols];
                }
            },

            toggleCol(key) {
                this.visibleCols = this.visibleCols.includes(key)
                    ? this.visibleCols.filter(c => c !== key)
                    : [...this.visibleCols, key];

                localStorage.setItem('gcstats.stats.cols.' + storageKey, JSON.stringify(this.visibleCols));
            },

            setMode(mode) {
                this.mode = mode;
            },

            closeColPicker() {
                this.colPickerOpen = false;
            },

            val(stat, key) {
                if (key === 'kd') {
                    const deaths = Number(stat[this.mode + '_deaths'] ?? 0);
                    return deaths > 0 ? Number(stat[this.mode + '_kills'] ?? 0) / deaths : Number(stat[this.mode + '_kills'] ?? 0);
                }

                if (key === 'kda') {
                    const deaths = Number(stat[this.mode + '_deaths'] ?? 0);
                    const kda = Number(stat[this.mode + '_kills'] ?? 0) + Number(stat[this.mode + '_assists'] ?? 0);
                    return deaths > 0 ? kda / deaths : kda;
                }

                return stat[this.mode + '_' + key] ?? 0;
            },

            formatVal(stat, key) {
                const v = Number(this.val(stat, key) ?? 0);

                if (key === 'kast' || key === 'hs') return Math.round(v) + '%';

                if (key === 'kd' || key === 'kda') return v.toFixed(2);

                return this.mode === 'avg' ? v.toFixed(1) : Math.round(v);
            },

            abilityTitle(stat, key) {
                const slot = { ability1_kills: 'Ability1', ability2_kills: 'Ability2', grenade_kills: 'GrenadeAbility', ultimate_kills: 'Ultimate' }[key];

                return slot && stat.ability_names ? (stat.ability_names[slot] || '') : '';
            },

            agentSlug(agent) {
                return (agent || '').toLowerCase().replaceAll('/', '');
            },

            sortBy(col) {
                if (this.sortCol === col) this.sortAsc = !this.sortAsc;
                else { this.sortCol = col; this.sortAsc = false; }
            },

            get filteredStats() {
                const stringCols = ['player_handle', 'player_country_code'];

                let rows = [...this.stats].sort((a, b) => {
                    let valA = stringCols.includes(this.sortCol) ? (a[this.sortCol] ?? '') : this.val(a, this.sortCol);
                    let valB = stringCols.includes(this.sortCol) ? (b[this.sortCol] ?? '') : this.val(b, this.sortCol);

                    if (!isNaN(valA) && !isNaN(valB)) {
                        return this.sortAsc ? valA - valB : valB - valA;
                    }
                    return this.sortAsc
                        ? String(valA).localeCompare(String(valB))
                        : String(valB).localeCompare(String(valA));
                });

                return rows;
            },
        };
    }
</script>
