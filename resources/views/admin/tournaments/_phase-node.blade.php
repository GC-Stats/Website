{{--
    GC-Stats — Admin: recursive tournament phase node (tournament show page)

    Renders one phase as a badge and includes itself for each child so a
    sub-phase of a sub-phase (and deeper) still shows, not just the first
    two levels.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $formatLabels = [
        'bracket' => __('admin.tournaments.phases.format_options.bracket'),
        'round_robin' => __('admin.tournaments.phases.format_options.round_robin'),
        'swiss' => __('admin.tournaments.phases.format_options.swiss'),
    ];
@endphp

<div class="bg-white/5 border border-white/10 rounded-lg p-3">
    <div class="text-[10px] font-black uppercase text-blue-400 mb-1">{{ $phase->name }}</div>
    @if ($phase->format)
        <div class="text-xs font-bold uppercase text-gray-300">{{ $formatLabels[$phase->format] ?? $phase->format }}</div>
        @include('admin.tournaments._phase-qualifications', ['phase' => $phase, 'tournament' => $tournament])
    @endif

    @if ($phase->children->isNotEmpty())
        <div class="grid grid-cols-1 gap-2 mt-2 pl-3 border-l border-white/10">
            @foreach ($phase->children as $child)
                @include('admin.tournaments._phase-node', ['phase' => $child, 'tournament' => $tournament])
            @endforeach
        </div>
    @endif
</div>
