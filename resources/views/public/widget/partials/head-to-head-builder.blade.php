{{--
    GC-Stats — Face to Face widget link builder (modal content)

    Included from resources/views/public/widget/index.blade.php inside that
    widget's "Configure" modal. Submits as a plain GET back to widget.index
    so the chosen options round-trip through the URL — see
    WidgetController::index() for how $generatedUrl gets computed and how
    the modal re-opens itself on reload once team_a/team_b are present.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<form method="GET" action="{{ route('widget.index') }}" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <livewire:entity-picker type="team" name="team_a" :label="__('widgets.builder.team_a')" :selected="$teamA?->id" />
        <livewire:entity-picker type="team" name="team_b" :label="__('widgets.builder.team_b')" :selected="$teamB?->id" />
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
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.patch') }}</label>
        <input type="text" name="patch" value="{{ request()->query('patch') }}" placeholder="{{ __('widgets.builder.patch_placeholder') }}"
               class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition">
        <p class="text-[10px] text-gray-500 mt-1.5">{{ __('widgets.builder.patch_hint') }}</p>
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('widgets.builder.mappool') }}</label>
        <input type="text" name="mappool" value="{{ request()->query('mappool') }}" placeholder="{{ __('widgets.builder.mappool_placeholder') }}"
               class="w-full py-2.5 px-4 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow transition">
        <p class="text-[10px] text-gray-500 mt-1.5">{{ __('widgets.builder.mappool_hint') }}</p>
    </div>

    <button type="submit" class="w-full py-3 text-[10px] font-black uppercase tracking-wider rounded-sm bg-gc-yellow text-black hover:opacity-90 transition-opacity">
        {{ __('widgets.builder.submit') }}
    </button>
</form>

@if ($generatedUrl)
    <div class="border-t border-border-subtle pt-4 mt-2"
         x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ $generatedUrl }}'); this.copied = true; setTimeout(() => this.copied = false, 1200); } }">
        <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3">{{ __('widgets.result.title') }}</h3>

        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $generatedUrl }}" onclick="this.select()"
                   class="flex-1 min-w-0 py-2.5 px-4 text-xs font-mono rounded-sm bg-[#050505] border border-border-subtle text-gray-300 focus:outline-none">
            <div class="flex gap-2 shrink-0">
                <button type="button" @click="copy()" class="px-4 py-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm bg-gc-yellow text-black hover:opacity-90 transition-opacity">
                    <span x-show="!copied">{{ __('widgets.result.copy') }}</span>
                    <span x-show="copied" x-cloak>{{ __('widgets.result.copied') }}</span>
                </button>
                <a href="{{ $generatedUrl }}" target="_blank" rel="noopener"
                   class="px-4 py-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm border border-border-subtle text-gray-300 hover:text-white hover:border-gc-yellow transition-colors">
                    {{ __('widgets.result.open') }}
                </a>
            </div>
        </div>

        <p class="text-[10px] text-gray-500 mt-3">{{ __('widgets.result.embed_hint') }}</p>

        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-6 mb-2">{{ __('widgets.result.preview') }}</p>
        <div class="rounded-sm border border-border-subtle bg-[#050505] overflow-hidden" style="height: 380px;">
            <iframe src="{{ $generatedUrl }}" class="w-full h-full" style="border: 0;" loading="lazy"></iframe>
        </div>
    </div>
@endif
