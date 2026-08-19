{{--
    GC-Stats — Stats insights sidebar

    Small "leader" cards (top ACS/ADR/KAST/first kills/utility) summarizing
    the stats list already computed by the calling controller — no extra
    query, purely derived from $stats. Same pattern as maps-insights.blade.php.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['insights', 'namespace'])

<div class="space-y-3">
    <h3 class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">
        {{ __($namespace.'.insights.title') }}
    </h3>

    <div class="grid grid-cols-2 gap-3">
        @foreach($insights as $insight)
            <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4">
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-2">
                    {{ __($namespace.'.insights.'.$insight['label']) }}
                </p>

                @if($insight['name'])
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-black text-white uppercase tracking-tight truncate">{{ $insight['name'] }}</span>
                        <span class="text-xs font-black text-[var(--brand-yellow)] shrink-0">{{ $insight['value'] }}</span>
                    </div>
                @else
                    <span class="text-xs text-gray-600">—</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
