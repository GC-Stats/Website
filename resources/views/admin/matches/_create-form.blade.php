{{--
    GC-Stats — Admin: quick match creation (index sidebar)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<form method="POST" action="{{ route('admin.matches.store', $tournament) }}" class="space-y-4">
    @csrf

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.phase') }}</span>
        <select name="phase_id" required
                    class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            @foreach ($phases as $p)
                <option value="{{ $p->id }}" @selected(old('phase_id', $sticky['phase_id'] ?? '') == $p->id)>{{ \App\Support\MatchDisplay::phaseLabel($p, $phases) }}</option>
            @endforeach
        </select>
        @error('phase_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </label>

    <x-timezone-select
        name="scheduled_at"
        :label="__('admin.matches.scheduled_at')"
        :value="old('scheduled_at')"
        :required="true"
    />
    @error('scheduled_at') <p class="-mt-2 text-xs text-red-400">{{ $message }}</p> @enderror

    <div class="grid grid-cols-2 gap-3">
        <x-team-select name="team_a_id" :label="__('admin.matches.team_a')" :teams="$teams" :selected="old('team_a_id')" />
        <x-team-select name="team_b_id" :label="__('admin.matches.team_b')" :teams="$teams" :selected="old('team_b_id')" />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.best_of') }}</span>
            <select name="best_of"
                    class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                @foreach ([1, 2, 3, 4, 5] as $f)
                    <option value="{{ $f }}" @selected(old('best_of', $sticky['best_of'] ?? 3) == $f)>BO{{ $f }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.match_order') }}</span>
            <input type="number" name="match_order" value="{{ old('match_order') }}"
                   class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
        </label>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.round_name') }}</span>
            <input type="text" name="round_name" value="{{ old('round_name') }}"
                   class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.round_number') }}</span>
            <input type="number" name="round_number" value="{{ old('round_number') }}"
                   class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
        </label>
    </div>

    <button type="submit"
            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
        {{ __('admin.matches.create.submit') }}
    </button>
</form>
