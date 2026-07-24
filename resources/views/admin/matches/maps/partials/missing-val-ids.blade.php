{{--
    GC-Stats — Admin: missing val_id player mapping rows

    Rendered server-side by GameMapController::fetchMapData() when the Riot
    match response has players it couldn't match to a roster (see
    missingValIdPlayers()) and injected into show.blade.php's fetch panel
    via x-html — Livewire's own MutationObserver picks up each freshly
    inserted <livewire:entity-picker> and hydrates it exactly like any
    component present at initial page load. Each picker submits its pick as
    puuid_mapping[<puuid>], read directly by fetchMapData()'s existing
    `puuid_mapping` validation, so no controller-side wiring beyond this
    render was needed.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@foreach ($missingPlayers as $p)
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1 min-w-0">
            <p class="text-sm text-white font-bold truncate">{{ $p['name'] }}</p>
            <p class="text-xs text-gray-500">{{ $p['agent'] ?? '—' }} — {{ $p['team'] ?? '—' }}</p>
        </div>
        <div class="w-full sm:w-72 sm:shrink-0">
            <livewire:entity-picker
                type="player"
                :name="'puuid_mapping['.$p['puuid'].']'"
                :placeholder="__('admin.matches.maps.select_player')"
                thumb-size="w-8 h-5"
                :browse-ids="$rosterPlayerIds ?? []"
                :limit="max(count($rosterPlayerIds ?? []), 8)"
                :key="'missing-val-id-'.$p['puuid']"
            />
        </div>
    </div>
@endforeach
