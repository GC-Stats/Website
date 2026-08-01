{{--
    GC-Stats — Change request status badge

    Colors a ChangeRequest/ChangeRequestItem status consistently everywhere
    it's shown (admin review, the requester's own tracking page): green for
    accepted, red for rejected, amber for the request-level partially
    accepted state, neutral gray for pending/withdrawn.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['status', 'context' => 'request'])

@php
    $classes = match ($status) {
        'accepted' => 'bg-green-500/10 border border-green-500/30 text-green-400',
        'rejected' => 'bg-red-500/10 border border-red-500/30 text-red-400',
        'partially_accepted' => 'bg-amber-500/10 border border-amber-500/30 text-amber-400',
        'withdrawn' => 'bg-white/5 border border-white/10 text-gray-500',
        default => 'bg-white/5 border border-white/10 text-gray-300', // pending
    };

    $label = __('admin.change_requests.'.($context === 'item' ? 'item_status' : 'status').'.'.$status);
@endphp

<span {{ $attributes->merge(['class' => "px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-sm {$classes}"]) }}>
    {{ $label }}
</span>
