{{--
    GC-Stats — Scheduled maintenance banner

    Shown at the top of every page ahead of the V2 migration maintenance
    (config app.v2_maintenance_at), with a live countdown. The date is
    rendered client-side in the visitor's timezone (GCS.getTimezone), so it
    stays correct on CDN-cached pages. Hidden once the write freeze is on:
    the write freeze banner takes over from there.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $maintenanceAt = config('app.v2_maintenance_at') ? \Illuminate\Support\Carbon::parse(config('app.v2_maintenance_at')) : null;
@endphp
@if ($maintenanceAt && ! \App\Support\WriteFreeze::active())
    <div role="status"
         class="relative z-[60] bg-[var(--brand-yellow)] text-black mb-4"
         x-data="{
            target: {{ $maintenanceAt->getTimestampMs() }},
            date: '',
            remaining: '',
            started: false,
            init() {
                const locale = document.documentElement.lang || undefined;
                this.date = new Intl.DateTimeFormat(locale, {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: GCS.getTimeFormat() === '12h',
                    timeZone: GCS.getTimezone(),
                    timeZoneName: 'short',
                }).format(new Date(this.target));
                this.tick();
                const timer = setInterval(() => { if (this.tick()) clearInterval(timer); }, 1000);
            },
            tick() {
                const diff = Math.max(0, Math.floor((this.target - Date.now()) / 1000));
                const pad = (n) => String(n).padStart(2, '0');
                const days = Math.floor(diff / 86400);
                this.remaining = (days ? days + @js(__('layout.maintenance.days_short')) + ' ' : '')
                    + pad(Math.floor(diff % 86400 / 3600)) + ':' + pad(Math.floor(diff % 3600 / 60)) + ':' + pad(diff % 60);
                this.started = diff === 0;
                return this.started;
            },
         }">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center">
            <span class="text-xs font-bold uppercase tracking-widest" x-show="!started">
                ⚠ {{ __('layout.maintenance.scheduled') }} <span x-text="date">{{ $maintenanceAt->utc()->format('d/m H:i') }} UTC</span>
                <span x-cloak x-show="remaining">— {{ __('layout.maintenance.starts_in') }} <span class="font-black tabular-nums" x-text="remaining"></span></span>
            </span>
            <span x-cloak x-show="started" class="text-xs font-bold uppercase tracking-widest">⚠ {{ __('layout.maintenance.in_progress') }}</span>
            <a href="{{ route('v2-migration') }}" class="text-xs font-black uppercase tracking-widest underline underline-offset-2 hover:opacity-70 transition">
                {{ __('layout.maintenance.more_info') }}
            </a>
        </div>
    </div>
@endif
