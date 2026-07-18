{{--
    GC-Stats — Role members panel

    Shared by admin/roles/show and team/roles/show: the role's current
    member list (each removable via a confirm modal) plus a search-and-add
    modal. Callers own all routing/translation strings so this stays
    agnostic to whether it's rendering a global or per-team role.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'members',
    'search',
    'searchResults',
    'searchUrl',
    'addMemberUrl',
    'removeMemberUrl',
    'title',
    'addLabel',
    'searchPlaceholder',
    'searchSubmitLabel',
    'assignLabel',
    'removeLabel',
    'removeConfirmTitle',
    'removeConfirmBody',
    'searchEmptyLabel',
    'membersEmptyLabel',
    'headingTag' => 'h3',
])

<div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
    <{{ $headingTag }} class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</{{ $headingTag }}>

    <div class="space-y-2">
        @forelse ($members as $member)
            <div class="flex items-center justify-between gap-4 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                <div>
                    <p class="text-sm text-white font-semibold">{{ $member->name }}</p>
                    <p class="text-xs text-gray-500">{{ $member->email }}</p>
                </div>
                <form method="POST" action="{{ $removeMemberUrl($member) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="$removeConfirmTitle"
                        :body="$removeConfirmBody($member)"
                        :trigger-label="$removeLabel"
                        :submit-label="$removeLabel"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            </div>
        @empty
            <p class="text-xs text-gray-500">{{ $membersEmptyLabel }}</p>
        @endforelse
    </div>

    <x-modal :title="$addLabel" :open-by-default="$search !== ''">
        <x-slot:trigger>
            <button type="button"
                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ $addLabel }}
            </button>
        </x-slot:trigger>

        <form method="GET" action="{{ $searchUrl }}" class="flex gap-2">
            <input type="text" name="q" x-ref="search" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}"
                   class="flex-1 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ $searchSubmitLabel }}
            </button>
        </form>

        @if ($search)
            <div class="space-y-2 pt-4">
                @forelse ($searchResults as $found)
                    <form method="POST" action="{{ $addMemberUrl }}" class="flex items-center justify-between gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $found->id }}">
                        <div>
                            <p class="text-xs text-white font-semibold">{{ $found->name }}</p>
                            <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                        </div>
                        <button type="submit"
                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ $assignLabel }}
                        </button>
                    </form>
                @empty
                    <p class="text-xs text-gray-500">{{ $searchEmptyLabel }}</p>
                @endforelse
            </div>
        @endif
    </x-modal>
</div>
