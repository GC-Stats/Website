<?php

/**
 * GC-Stats — Notification bell Livewire component
 *
 * Navbar dropdown showing the signed-in user's most recent notifications
 * (see App\Services\NotificationService) with an unread badge. Polls on an
 * interval rather than pushing over websockets — good enough for this
 * volume of notifications without needing a broadcasting stack.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use Livewire\Volt\Component;
use App\Services\NotificationService;

new class extends Component {
    public function with()
    {
        $user = auth()->user();

        return [
            'notifications' => $user->notifications()->with('author:id,name,username')->limit(8)->get(),
            'unreadCount' => app(NotificationService::class)->unreadCount($user),
        ];
    }

    public function markAllRead()
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());
    }
}; ?>

<div class="relative"
     x-data="{ open: false }"
     @click.away="open = false"
     wire:poll.30s>
    <button
        @click="open = !open"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
        aria-label="{{ __('notifications.bell_label') }}"
        class="relative flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-xl bg-white/5 border border-white/10 hover:border-[var(--brand-yellow)]/50 transition-all">
        <x-fas-bell class="w-4 h-4 text-gray-400" aria-hidden="true" />
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 flex items-center justify-center rounded-full bg-[var(--brand-yellow)] text-black text-[9px] font-black">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         role="menu"
         class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-bg-main/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.7)] z-50 overflow-hidden origin-top-right"
         x-cloak>
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('notifications.title') }}</span>
            @if($unreadCount > 0)
                <button wire:click="markAllRead" class="text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all">
                    {{ __('notifications.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-white/5">
            @forelse ($notifications as $notification)
                <a href="{{ route('account.notifications.open', $notification) }}" role="menuitem"
                   class="block px-4 py-3 hover:bg-white/5 transition-all {{ $notification->isRead() ? '' : 'bg-white/[0.03]' }}">
                    <p class="text-xs font-semibold text-white flex items-center gap-1.5">
                        @unless($notification->isRead())
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-[var(--brand-yellow)]" aria-hidden="true"></span>
                        @endunless
                        {{ $notification->title }}
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1 line-clamp-2">{{ $notification->description }}</p>
                    <p class="text-[10px] text-gray-600 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-6 text-xs text-gray-500 text-center">{{ __('notifications.empty') }}</p>
            @endforelse
        </div>

        <a href="{{ route('account.notifications.index') }}" role="menuitem"
           class="block px-4 py-3 text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-white/5 hover:text-white transition-all border-t border-white/10">
            {{ __('notifications.see_all') }}
        </a>
    </div>
</div>
