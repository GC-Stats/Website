@props(['usage'])

{{--
    4 states, driven by DataExplorerQuotaService::usageSummary():
    a) authorized + under quota      -> "X/Y requests this month" (platform key)
    b) source === personal           -> using own key (whether that's because
                                         they're unauthorized for the platform
                                         key at all, or authorized but over quota
                                         — the distinction doesn't matter to the
                                         user, the outcome is the same)
    c) blocked, authorized           -> quota exceeded, CTA to link a key
    d) blocked, not authorized       -> feature needs a personal key, CTA to link one
--}}
@php
    $percent = $usage['quota'] > 0 ? min(100, round($usage['used'] / $usage['quota'] * 100)) : 100;
@endphp

@if ($usage['source'] === 'platform')
    <div class="bg-white/5 border border-border-subtle rounded-sm px-4 py-3 space-y-2">
        <p class="text-sm text-gray-300">{{ __('data_explorer.banner.under_quota', ['used' => $usage['used'], 'quota' => $usage['quota']]) }}</p>
        <div class="h-1.5 rounded-full bg-white/5 overflow-hidden">
            <div class="h-full bg-gc-yellow transition-[width]" style="width: {{ $percent }}%"></div>
        </div>
    </div>
@elseif ($usage['source'] === 'personal')
    <div class="bg-gc-yellow/10 border border-gc-yellow/40 rounded-sm px-4 py-3 space-y-2">
        <p class="text-sm text-gc-yellow">{{ __('data_explorer.banner.using_personal_key') }}</p>
        <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
            <div class="h-full bg-gc-yellow" style="width: 100%"></div>
        </div>
    </div>
@else
    <div class="bg-red-500/10 border border-red-500/30 rounded-sm px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
        <p class="text-sm text-red-400">
            {{ $usage['authorized'] ? __('data_explorer.banner.quota_exceeded') : __('data_explorer.banner.key_required') }}
        </p>
        <a href="{{ route('data-explorer.settings') }}"
           class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
            {{ __('data_explorer.banner.add_key_cta') }}
        </a>
    </div>
@endif
