{{--
    GC-Stats — Tournament phase node

    Recursively renders a phase (or one of its children/grandchildren): a
    group of sub-phases, or a single format view (bracket, swiss, round
    robin). Replaces the previous hand-unrolled 3-level nesting with one
    component that calls itself.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['node', 'teams' => [], 'showHeading' => false])

@php
    $children = $node['children'] ?? [];
    $isLeafBracketGroup = false;

    if (!empty($children)) {
        $childrenColl = collect($children);
        $hasNestedChildren = $childrenColl->some(fn($c) => !empty($c['children']));
        $isLeafBracketGroup = !$hasNestedChildren && $childrenColl->every(fn($c) => ($c['format'] ?? '') === 'bracket');
    }
@endphp

@if(!empty($children))
    @if($isLeafBracketGroup)
        <x-tournament.pan-zoom-bracket>
            <div class="flex flex-col gap-20">
                @foreach($children as $child)
                    <div class="flex flex-col items-start">
                        <h3 class="text-[11px] font-black uppercase text-gc-yellow tracking-widest mb-6">
                            {{ $child['name'] }}
                        </h3>
                        <x-tournament.bracket-grid :matches="$child['matches'] ?? []" />
                    </div>
                @endforeach
            </div>
        </x-tournament.pan-zoom-bracket>

        <div class="flex flex-col gap-8">
            @foreach($children as $child)
                <x-tournament.leaderboard :phase="$child" :teams="$teams" />
            @endforeach
        </div>
    @else
        <div class="flex flex-col gap-12">
            @foreach($children as $child)
                <div class="flex flex-col">
                    <h3 class="text-[11px] font-black uppercase text-[var(--gc-yellow)] tracking-widest mb-4">
                        {{ $child['name'] }}
                    </h3>

                    <x-tournament.phase-node :node="$child" :teams="$teams" />
                </div>
            @endforeach
        </div>
    @endif
@elseif(($node['format'] ?? '') === 'swiss')
    <x-tournament.swiss-standings
        :matches="$node['matches'] ?? []"
        :phase="$node"
        :teams="$teams"
    />
    <x-tournament.leaderboard :phase="$node" :teams="$teams" />
@elseif(($node['format'] ?? '') === 'round_robin')
    <x-tournament.round-robin
        :matches="$node['matches'] ?? []"
        :phase="$node"
        :teams="$teams"
    />
    <x-tournament.leaderboard :phase="$node" :teams="$teams" />
@else
    <x-tournament.pan-zoom-bracket>
        @if($showHeading)
            <div class="flex flex-col gap-20">
                <div class="flex flex-col items-start">
                    <h3 class="text-[11px] font-black uppercase text-gc-yellow tracking-widest mb-6">
                        {{ $node['name'] }}
                    </h3>
                    <x-tournament.bracket-grid :matches="$node['matches'] ?? []" />
                </div>
            </div>
        @else
            <x-tournament.bracket-grid :matches="$node['matches'] ?? []" />
        @endif
    </x-tournament.pan-zoom-bracket>
    <x-tournament.leaderboard :phase="$node" :teams="$teams" />
@endif
