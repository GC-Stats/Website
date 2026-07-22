{{--
    GC-Stats — Admin: point type create/edit form (shared partial)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4 mb-6">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.point_types.title') }}</h2>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.point_types.name') }}</span>
        <input type="text" name="name" value="{{ old('name', $pointType->name ?? '') }}" required
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.point_types.label') }}</span>
        <input type="text" name="label" value="{{ old('label', $pointType->label ?? '') }}" placeholder="{{ __('admin.point_types.label_placeholder') }}" required
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </label>

    <div class="grid grid-cols-2 gap-4">
        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.point_types.start_date') }}</span>
            <input type="date" name="start_date" value="{{ old('start_date', optional($pointType->start_date ?? null)->format('Y-m-d')) }}" required
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.point_types.end_date') }}</span>
            <input type="date" name="end_date" value="{{ old('end_date', optional($pointType->end_date ?? null)->format('Y-m-d')) }}" required
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>
    </div>
</div>
