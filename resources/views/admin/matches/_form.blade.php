{{--
    GC-Stats — Admin: match create/edit form (shared partial)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.phase') }}</span>
        <select name="phase_id" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            @foreach ($phases as $p)
                <option value="{{ $p->id }}" @selected(old('phase_id', $match->phase_id ?? ($sticky['phase_id'] ?? '')) == $p->id)>{{ \App\Support\MatchDisplay::phaseLabel($p, $phases) }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.status_column') }}</span>
        <select name="status"
                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            @foreach (['upcoming', 'live', 'finished'] as $s)
                <option value="{{ $s }}" @selected(old('status', $match->status ?? 'upcoming') === $s)>{{ __('admin.matches.status.'.$s) }}</option>
            @endforeach
        </select>
    </label>

    <x-team-select name="team_a_id" :label="__('admin.matches.team_a')" :teams="$teams" :selected="old('team_a_id', $match->team_a_id ?? null)" />
    <x-team-select name="team_b_id" :label="__('admin.matches.team_b')" :teams="$teams" :selected="old('team_b_id', $match->team_b_id ?? null)" />

    @if (isset($match))
        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.team_a_score') }}</span>
            <input type="number" name="team_a_score" value="{{ old('team_a_score', $match->team_a_score ?? '') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.team_b_score') }}</span>
            <input type="number" name="team_b_score" value="{{ old('team_b_score', $match->team_b_score ?? '') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
        </label>
    @endif

    <div class="md:col-span-2">
        <x-timezone-select
            name="scheduled_at"
            :label="__('admin.matches.scheduled_at')"
            :value="old('scheduled_at', optional($match->scheduled_at ?? null)->format('Y-m-d H:i:s'))"
            :required="true"
        />
    </div>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.best_of') }}</span>
        <input type="number" name="best_of" value="{{ old('best_of', $match->best_of ?? ($sticky['best_of'] ?? 3)) }}" min="1"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.patch') }}</span>
        <input type="text" name="patch" value="{{ old('patch', $match->patch ?? '') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.round_name') }}</span>
        <input type="text" name="round_name" value="{{ old('round_name', $match->round_name ?? '') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.round_number') }}</span>
        <input type="number" name="round_number" value="{{ old('round_number', $match->round_number ?? '') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.match_order') }}</span>
        <input type="number" name="match_order" value="{{ old('match_order', $match->match_order ?? '') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
    </label>
</div>
