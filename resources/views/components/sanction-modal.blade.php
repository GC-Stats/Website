{{--
    GC-Stats — Sanction modal

    Reusable "issue a sanction" dialog — wrap any trigger element (a button,
    an icon, ...) as the default slot. Pass `user` to pre-fill and lock the
    sanctioned account (also links to their admin profile); omit it for a
    free-text target (e.g. the standalone sanctions list).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['user' => null])

<x-modal :title="__('admin.sanctions.issue.title')" max-width="max-w-md">
    <x-slot:trigger>
        {{ $slot }}
    </x-slot:trigger>

    @if ($user)
        @can('users.view')
            <a href="{{ route('admin.users.show', $user) }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-gc-yellow transition">
                {{ __('admin.sanctions.issue.view_profile') }}
                @svg('fas-arrow-up-right-from-square', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])
            </a>
        @endcan
    @endif

    @include('admin.sanctions._form', ['user' => $user])
</x-modal>
