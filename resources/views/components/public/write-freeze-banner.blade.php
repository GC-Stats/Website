{{--
    GC-Stats — Write freeze banner

    Shown at the top of every public page while public writes are frozen
    for the V2 migration (see App\Support\WriteFreeze).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@if (\App\Support\WriteFreeze::active())
    <div role="status" class="relative z-[60] bg-[var(--brand-yellow)] text-black mb-4">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-2 text-center">
            <span class="text-xs font-bold uppercase tracking-widest">{{ __('layout.write_freeze.banner') }}</span>
        </div>
    </div>
@endif
