{{--
    GC-Stats — Developers sidebar navigation (shared by the desktop and mobile shells)

    Each group's collapsed/expanded state persists per-browser in
    localStorage, keyed by index — open by default so a first-time visitor
    sees everything.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@foreach ($navGroups as $groupIndex => $group)
    @if (collect($group['items'])->isNotEmpty())
        <div x-data="{ open: localStorage.getItem('gcs_developers_nav_{{ $groupIndex }}') !== '0' }">
            <div x-show="open" x-collapse.duration.150ms class="space-y-0.5">
                @foreach (collect($group['items']) as $item)
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                       @if(request()->routeIs($item['pattern'])) aria-current="page" @endif
                       class="flex items-center gap-2.5 px-3 py-1.5 text-[12.5px] font-medium normal-case tracking-normal rounded-lg transition-all {{ request()->routeIs($item['pattern']) ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        @svg($item['icon'], 'w-3.5 h-3.5 shrink-0', ['aria-hidden' => 'true'])
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
