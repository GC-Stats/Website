{{--
    GC-Stats — My notifications

    Full list of the signed-in user's in-app notifications (see
    NotificationService), most recent first. Clicking one goes through
    NotificationController::open(), which marks it read then redirects.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('notifications.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('notifications.title') }}</h1>
                    <p class="text-sm text-gray-400 mt-2">{{ __('notifications.intro') }}</p>
                </div>

                <form method="POST" action="{{ route('account.notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest rounded-xl bg-white/5 border border-white/10 text-gray-300 hover:border-[var(--brand-yellow)]/50 hover:text-white transition-all">
                        {{ __('notifications.mark_all_read') }}
                    </button>
                </form>
            </div>

            @if (session('status') === 'email-preferences-updated')
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('notifications.email_preferences.saved') }}
                </div>
            @endif

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <div>
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('notifications.email_preferences.title') }}</h2>
                    <p class="text-xs text-gray-400 mt-2">{{ __('notifications.email_preferences.intro') }}</p>
                </div>

                <form method="POST" action="{{ route('account.notifications.email-preferences.update') }}" class="space-y-3">
                    @csrf
                    @method('PUT')

                    @foreach ($emailCategories as $category)
                        <label class="flex items-center gap-3 text-sm text-gray-300">
                            <input type="checkbox" name="categories[]" value="{{ $category }}"
                                   @checked(\App\Support\EmailNotificationPreferences::enabled(auth()->user(), $category))
                                   class="rounded border-white/20 bg-white/5 text-[var(--brand-yellow)] focus:ring-[var(--brand-yellow)]">
                            {{ __('notifications.email_preferences.'.$category) }}
                        </label>
                    @endforeach

                    <button type="submit"
                            class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest rounded-xl bg-[var(--brand-yellow)] text-black hover:brightness-110 transition-all">
                        {{ __('notifications.email_preferences.submit') }}
                    </button>
                </form>
            </div>

            <div class="flex gap-2 text-[10px] font-bold uppercase tracking-widest">
                <a href="{{ route('account.notifications.index') }}"
                   class="px-3 py-1.5 rounded-lg transition {{ ! request()->boolean('unread') ? 'bg-[var(--brand-yellow)] text-black' : 'bg-white/5 text-gray-400 hover:text-white' }}">
                    {{ __('notifications.show_all') }}
                </a>
                <a href="{{ route('account.notifications.index', ['unread' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ request()->boolean('unread') ? 'bg-[var(--brand-yellow)] text-black' : 'bg-white/5 text-gray-400 hover:text-white' }}">
                    {{ __('notifications.unread_only') }}
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($notifications as $notification)
                    <a href="{{ route('account.notifications.open', $notification) }}"
                       class="block bg-bg-card border rounded-sm p-5 shadow-xl transition {{ $notification->isRead() ? 'border-border-subtle hover:border-gc-yellow/40' : 'border-[var(--brand-yellow)]/40 bg-white/[0.03]' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    @unless($notification->isRead())
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-[var(--brand-yellow)] mr-1.5 align-middle" aria-hidden="true"></span>
                                    @endunless
                                    {{ $notification->title }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">{{ $notification->description }}</p>
                                <p class="text-[11px] text-gray-500 mt-2">
                                    {{ $notification->author ? __('notifications.from', ['name' => $notification->author->name]) : __('notifications.from_system') }}
                                </p>
                            </div>

                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-8 text-center">
                        <p class="text-sm text-gray-500">{{ __('notifications.empty') }}</p>
                    </div>
                @endforelse
            </div>

            {{ $notifications->links() }}
        </section>
    </div>
@endsection
