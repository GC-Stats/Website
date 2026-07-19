{{--
    GC-Stats — Logo history list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'logos',
    'folder',
    'addUrl',
    'updateUrl',
    'deleteUrl',
    'title',
    'fromLabel',
    'untilLabel',
    'saveLabel',
    'addLabel',
    'removeLabel',
    'removeConfirmTitle',
    'removeConfirmBody',
    'emptyLabel',
])

<div class="pt-4 border-t border-border-subtle space-y-3">
    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ $title }}</p>

    <div class="space-y-2">
        @forelse ($logos as $logo)
            <div class="flex flex-wrap items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($folder.'/'.$logo->id.'/200x200.webp') }}" alt=""
                     class="w-10 h-10 object-contain shrink-0">

                <form method="POST" action="{{ $updateUrl($logo) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    @method('PUT')
                    <input type="date" name="from" value="{{ $logo->from->format('Y-m-d') }}" aria-label="{{ $fromLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    <input type="date" name="until" value="{{ $logo->until?->format('Y-m-d') }}" aria-label="{{ $untilLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    <button type="submit"
                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-white/10 border border-border-subtle text-white hover:bg-white/20">
                        {{ $saveLabel }}
                    </button>
                </form>

                <form method="POST" action="{{ $deleteUrl($logo) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="$removeConfirmTitle"
                        :body="$removeConfirmBody($logo)"
                        :trigger-label="$removeLabel"
                        :submit-label="$removeLabel"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            </div>
        @empty
            <p class="text-xs text-gray-500">{{ $emptyLabel }}</p>
        @endforelse
    </div>

    <div x-data="{ preview: null }" class="flex flex-wrap items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
        <img x-show="preview" :src="preview" alt="" class="w-10 h-10 object-contain shrink-0">

        <form method="POST" action="{{ $addUrl }}" enctype="multipart/form-data" class="flex-1 flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="logo" accept="image/*" required
                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                   class="flex-1 min-w-[10rem] text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white hover:file:bg-white/10">
            <input type="date" name="from" required aria-label="{{ $fromLabel }}"
                   class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            <input type="date" name="until" required aria-label="{{ $untilLabel }}"
                   class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
                {{ $addLabel }}
            </button>
        </form>
    </div>
</div>
