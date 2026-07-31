{{--
    GC-Stats — Positions Heatmap widget link builder (modal content)

    Included from resources/views/public/widget/index.blade.php inside that
    widget's "Configure" modal. Submits as a plain GET back to widget.index
    so the chosen options round-trip through the URL — see
    WidgetController::index() for how $heatmapGeneratedUrl gets computed.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<form method="GET" action="{{ route('widget.index') }}" class="space-y-4">
    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.map') }}</label>
        <select name="map" required
                class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition uppercase tracking-wider">
            <option value="" disabled {{ $selectedMap ? '' : 'selected' }}>{{ __('widgets.builder.map_placeholder') }}</option>
            @foreach ($mapList as $map)
                <option value="{{ $map }}" {{ $selectedMap === $map ? 'selected' : '' }}>{{ ucfirst($map) }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <livewire:entity-picker type="tournament" name="tournament_id" :label="__('widgets.builder.tournament')" :selected="$tournament?->id" />
        <p class="text-[10px] text-gray-500 mt-1.5">{{ __('widgets.builder.tournament_hint') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.start_date') }}</label>
            <input type="date" name="start_date" value="{{ request()->query('start_date') }}"
                   class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.end_date') }}</label>
            <input type="date" name="end_date" value="{{ request()->query('end_date') }}"
                   class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.side') }}</label>
        <select name="side"
                class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition uppercase tracking-wider">
            <option value="" {{ $selectedSide ? '' : 'selected' }}>{{ __('widgets.builder.side_all') }}</option>
            <option value="atk" {{ $selectedSide === 'atk' ? 'selected' : '' }}>{{ __('widgets.builder.side_atk') }}</option>
            <option value="def" {{ $selectedSide === 'def' ? 'selected' : '' }}>{{ __('widgets.builder.side_def') }}</option>
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <livewire:entity-picker type="team" name="team_id" :label="__('widgets.builder.team')" :selected="$heatmapTeam?->id" />
        <livewire:entity-picker type="player" name="player_id" :label="__('widgets.builder.player')" :selected="$heatmapPlayer?->id" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.time_start') }}</label>
            <input type="number" name="time_start" min="0" step="1" value="{{ $selectedTimeStart }}" placeholder="0"
                   class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.time_end') }}</label>
            <input type="number" name="time_end" min="0" step="1" value="{{ $selectedTimeEnd }}" placeholder="100"
                   class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition">
        </div>
    </div>
    <p class="text-[10px] text-gray-500 -mt-2">{{ __('widgets.builder.time_hint') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.agent') }}</label>
            <select name="agent"
                    class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition uppercase tracking-wider">
                <option value="" {{ $selectedAgent ? '' : 'selected' }}>{{ __('widgets.builder.agent_all') }}</option>
                @foreach ($agentList as $agent)
                    <option value="{{ $agent }}" {{ $selectedAgent === $agent ? 'selected' : '' }}>{{ $agent }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.color') }}</label>
            <div class="flex items-center gap-2">
                <input type="color" name="color" value="#{{ $selectedColor ?? '2a78d6' }}"
                       class="h-[38px] w-14 shrink-0 rounded-sm border border-border-subtle bg-[#050505] cursor-pointer">
                <p class="text-[10px] text-gray-500">{{ __('widgets.builder.color_hint') }}</p>
            </div>
        </div>
    </div>

    <div x-data="{ types: {{ Illuminate\Support\Js::from($selectedEventTypes) }} }">
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.event_types') }}</label>
        <input type="hidden" name="event_type" :value="types.join(',')">
        <div class="flex flex-wrap gap-4">
            @foreach (['kill', 'plant', 'defuse'] as $type)
                <label class="flex items-center gap-2 text-xs text-gray-300">
                    <input type="checkbox" value="{{ $type }}" x-model="types"
                           class="rounded-sm bg-[#050505] border-border-subtle text-gc-yellow focus:ring-gc-yellow">
                    {{ __('widgets.builder.event_'.$type) }}
                </label>
            @endforeach
        </div>
    </div>

    <button type="submit" class="w-full py-3 text-[10px] font-black uppercase tracking-wider rounded-sm bg-gc-yellow text-black hover:opacity-90 transition-opacity">
        {{ __('widgets.builder.submit') }}
    </button>
</form>

@if ($heatmapGeneratedUrl)
    <div class="border-t border-border-subtle pt-4 mt-2"
         x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ $heatmapGeneratedUrl }}'); this.copied = true; setTimeout(() => this.copied = false, 1200); } }">
        <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3">{{ __('widgets.result.title') }}</h3>

        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $heatmapGeneratedUrl }}" onclick="this.select()"
                   class="flex-1 min-w-0 py-2.5 px-4 text-xs font-mono rounded-sm bg-[#050505] border border-border-subtle text-gray-300 focus:outline-none">
            <div class="flex gap-2 shrink-0">
                <button type="button" @click="copy()" class="px-4 py-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm bg-gc-yellow text-black hover:opacity-90 transition-opacity">
                    <span x-show="!copied">{{ __('widgets.result.copy') }}</span>
                    <span x-show="copied" x-cloak>{{ __('widgets.result.copied') }}</span>
                </button>
                <a href="{{ $heatmapGeneratedUrl }}" target="_blank" rel="noopener"
                   class="px-4 py-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm border border-border-subtle text-gray-300 hover:text-white hover:border-gc-yellow transition-colors">
                    {{ __('widgets.result.open') }}
                </a>
            </div>
        </div>

        <p class="text-[10px] text-gray-500 mt-3">{{ __('widgets.result.embed_hint') }}</p>

        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-6 mb-2">{{ __('widgets.result.preview') }}</p>
        <div class="rounded-sm border border-border-subtle bg-[#050505] overflow-hidden" style="height: 380px;">
            <iframe src="{{ $heatmapGeneratedUrl }}" class="w-full h-full" style="border: 0;" loading="lazy"></iframe>
        </div>
    </div>
@endif
