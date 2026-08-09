{{--
    GC-Stats — Organization dashboard sidebar navigation (shared by the
    desktop and mobile shells)

    Mirrors admin/partials/nav.blade.php, adapted for two differences: items
    are already pre-filtered by permission via array_filter() in
    organization/layout.blade.php (no per-item 'can' Gate lookup here,
    since visibility depends on the *specific organization* being viewed,
    not a global ability), and every route() call carries $routeParams (the
    layout's $homeRouteParams — [$organization], or [] in personal-dashboard
    mode) as its route parameters. Each group's collapsed/expanded state persists
    per-browser in localStorage, keyed by index — open by default so a
    first-time visitor sees everything.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@foreach ($navGroups as $groupIndex => $group)
    @if (!empty($group['items']))
        <div x-data="{ open: localStorage.getItem('gcs_org_nav_{{ $groupIndex }}') !== '0' }">
            <button type="button"
                    @click="open = !open; localStorage.setItem('gcs_org_nav_{{ $groupIndex }}', open ? '1' : '0')"
                    class="w-full flex items-center justify-between gap-2 px-3 mb-2.5 text-[9px] font-black uppercase tracking-[0.2em] text-gray-600 hover:text-gray-400 transition">
                <span>{{ $group['label'] }}</span>
                @svg('fas-chevron-down', 'w-2.5 h-2.5 shrink-0 transition-transform duration-200', ['aria-hidden' => 'true', ':class' => "{ '-rotate-90': !open }"])
            </button>
            <div x-show="open" x-collapse.duration.150ms class="space-y-0.5">
                @foreach ($group['items'] as $item)
                    <a href="{{ route($item['route'], $routeParams) }}"
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
