{{--
    GC-Stats — Admin: emote create/edit form (shared partial)

    Expects $emote (null when creating), $teams and $sources (distinct
    existing source folders, for the datalist suggestions). Note the
    surrounding <form> must carry enctype="multipart/form-data" for the
    image input.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4 mb-6">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.emotes.title') }}</h2>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.emotes.fields.name') }}</span>
        <input type="text" name="name" value="{{ old('name', $emote->name ?? '') }}" required maxlength="80"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        <span class="block text-xs text-gray-500 mt-1">{{ __('admin.emotes.fields.name_help') }}</span>
        @error('name')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $emote->is_active ?? true))
               class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
        <span class="text-sm text-gray-300">{{ __('admin.emotes.fields.is_active') }}</span>
    </label>

    @if ($emote)
        <div>
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.emotes.edit.image_current') }}</span>
            <img src="{{ $emote->image_url }}" alt="{{ $emote->name }}" class="w-12 h-12 object-contain bg-white/5 border border-white/10 rounded-lg p-1">
        </div>
    @endif

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.emotes.fields.image') }}</span>
        <input type="file" name="image" accept=".svg,.png,.jpg,.jpeg,image/svg+xml,image/png,image/jpeg"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:uppercase file:bg-gc-yellow file:text-black">
        <span class="block text-xs text-gray-500 mt-1">
            {{ __('admin.emotes.fields.image_help') }}
            @if ($emote)
                {{ __('admin.emotes.edit.image_replace_help') }}
            @endif
        </span>
        @error('image')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.emotes.fields.source') }}</span>
        <input type="text" name="source" list="source-suggestions" maxlength="40"
               value="{{ old('source', $emote->source ?? '') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        <datalist id="source-suggestions">
            @foreach ($sources as $existingSource)
                <option value="{{ $existingSource }}"></option>
            @endforeach
        </datalist>
        <span class="block text-xs text-gray-500 mt-1">{{ __('admin.emotes.fields.source_help') }}</span>
        @error('source')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.emotes.fields.team') }}</span>
        <select name="team_id" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <option value="">{{ __('admin.emotes.fields.team_none') }}</option>
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
        <span class="block text-xs text-gray-500 mt-1">{{ __('admin.emotes.fields.team_help') }}</span>
        @error('team_id')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>
</div>
