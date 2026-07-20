{{--
    GC-Stats — About project form (used inside <x-modal>)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
--}}
<form method="POST" action="{{ $project ? route('admin.about.projects.update', $project) : route('admin.about.projects.store') }}" class="space-y-4">
    @csrf
    @if ($project)
        @method('PUT')
    @endif

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.about.project_name_label') }}
        </label>
        <input type="text" name="name" required maxlength="100" value="{{ old('name', $project->name ?? '') }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.about.project_type_label') }}
        </label>
        <select name="type"
                class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <option value=""></option>
            @foreach ($projectTypes as $type)
                <option value="{{ $type }}" @selected(old('type', $project->type ?? '') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>

    @foreach ($locales as $locale)
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.about.project_description_label') }} ({{ strtoupper($locale) }})
            </label>
            <textarea name="description[{{ $locale }}]" rows="2"
                      class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ $project->description[$locale] ?? '' }}</textarea>
        </div>
    @endforeach

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.about.project_url_label') }}
        </label>
        <input type="url" name="url" maxlength="255" value="{{ old('url', $project->url ?? '') }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </div>

    <div class="grid grid-cols-2 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.about.order_label') }}
            </label>
            <input type="number" name="order" value="{{ old('order', $project->order ?? 0) }}"
                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </div>
        <label class="flex items-center gap-2 text-xs text-gray-400 mb-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $project->is_active ?? true))
                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
            {{ __('admin.about.active_label') }}
        </label>
    </div>

    <button type="submit"
            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
        {{ __('admin.about.save') }}
    </button>
</form>
