{{--
    GC-Stats — Logo upload form (with live preview)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'currentUrl',
    'actionUrl',
    'submitLabel',
    'themeable' => false,
    'themeUniversalLabel' => null,
    'themeDarkLabel' => null,
    'themeLightLabel' => null,
])

<div x-data="{ preview: null }" class="flex items-center gap-4">
    <img :src="preview || @js($currentUrl)" alt="" class="w-16 h-16 object-contain border border-white/10 rounded-lg bg-black/40 p-2">

    <form method="POST" action="{{ $actionUrl }}" enctype="multipart/form-data" class="flex-1 flex items-center gap-3">
        @csrf
        <input type="file" name="logo" accept="image/*" required
               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
               class="flex-1 text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-sm file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white hover:file:bg-white/10">
        @if ($themeable)
            <select name="theme"
                    class="bg-black/40 border border-border-subtle rounded-sm px-2 py-2.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ $themeUniversalLabel }}</option>
                <option value="dark">{{ $themeDarkLabel }}</option>
                <option value="light">{{ $themeLightLabel }}</option>
            </select>
        @endif
        <button type="submit"
                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
            {{ $submitLabel }}
        </button>
    </form>
</div>
