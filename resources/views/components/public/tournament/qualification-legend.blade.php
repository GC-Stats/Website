{{--
    GC-Stats — Qualification legend for a swiss/round_robin phase

    Shown once below the whole standings table (not per row): one full
    sentence per rank range explaining where it qualifies to (another
    phase, possibly in a different tournament) or what final placement it
    earns — the destination name is linked when it points to a phase.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['qualifications' => []])

@php
    $sortedQualifications = collect($qualifications)->sortBy('rank_from')->values();
@endphp

@if ($sortedQualifications->isNotEmpty())
    <div class="border-t border-border-subtle bg-white/[0.02] px-3 py-2.5 md:px-4 space-y-1.5">
        @foreach ($sortedQualifications as $rule)
            @php
                $destination = $rule['url'] ?? null
                    ? '<a href="'.e($rule['url']).'" class="text-white hover:text-gc-yellow transition-colors underline">'.e($rule['label']).'</a>'
                    : '<span class="text-white">'.e($rule['label']).'</span>';

                $sentence = $rule['rank_from'] === $rule['rank_to']
                    ? __('tournament.swiss_stage.qualification_single', ['rank' => $rule['rank_from'], 'destination' => $destination])
                    : __('tournament.swiss_stage.qualification_range', ['from' => $rule['rank_from'], 'to' => $rule['rank_to'], 'destination' => $destination]);
            @endphp
            <p class="text-[9px] md:text-[10px] font-bold uppercase tracking-wide not-italic text-gray-400">
                {!! $sentence !!}
            </p>
        @endforeach
    </div>
@endif
