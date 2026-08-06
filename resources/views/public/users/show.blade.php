{{--
    GC-Stats — Public user profile

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', $profileUser->name)

@section('content')
    @include('public.users.header')

    <div class="grid grid-cols-12 gap-6">
        <aside class="col-span-12 lg:col-span-3 space-y-6">
            <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('user.profile.global_roles_title') }}</p>
                @forelse ($profileUser->roles as $role)
                    <span class="inline-block px-2 py-1 mr-1 mb-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-300">
                        {{ $role->name }}
                    </span>
                @empty
                    <p class="text-xs text-gray-600">{{ __('user.profile.no_roles') }}</p>
                @endforelse
            </div>
        </aside>

        <section class="col-span-12 lg:col-span-9 space-y-4">
            @if ($profileUser->bio || ! empty($profileUser->socials))
                <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4 space-y-4">
                    @if ($profileUser->bio)
                        <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-line">
                            {{ $profileUser->bio }}
                        </p>
                    @endif

                    <x-social-links :socials="$profileUser->socials" />
                </div>
            @endif

            {{-- TBD: additional public profile content (activity, badges, etc.) --}}
        </section>
    </div>
@endsection
