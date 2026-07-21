{{--
    GC-Stats — Issue sanction form (used inside <x-modal>)

    Optionally pass $user to pre-fill and lock the target; otherwise shows
    a free-text user id field.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
--}}
@php
    $sanctionTypes = [
        \App\Models\Sanction::TYPE_WARNING,
        \App\Models\Sanction::TYPE_MUTE,
        \App\Models\Sanction::TYPE_SUSPENSION,
        \App\Models\Sanction::TYPE_BAN,
    ];
@endphp

<form method="POST" action="{{ route('admin.sanctions.store') }}" class="space-y-4">
    @csrf

    @if (isset($user))
        <input type="hidden" name="username" value="{{ $user->username }}">
        <p class="text-sm text-white font-semibold">
            {{ $user->name }}
            @if ($user->username)
                <span class="text-gray-500 font-normal">{{ '@'.$user->username }}</span>
            @endif
        </p>
    @else
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.sanctions.issue.user_label') }}
            </label>
            <input type="text" name="username" required placeholder="@username"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            @error('username')
                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.sanctions.issue.type_label') }}
        </label>
        <select name="type" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            @foreach ($sanctionTypes as $type)
                <option value="{{ $type }}">{{ __('admin.sanctions.type.'.$type) }}</option>
            @endforeach
        </select>
        @error('type')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.sanctions.issue.reason_label') }}
        </label>
        <textarea name="reason" rows="3" required
                  class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
        @error('reason')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.sanctions.issue.ends_at_label') }}
        </label>
        <input type="datetime-local" name="ends_at"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('ends_at')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit"
            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
        {{ __('admin.sanctions.issue.submit') }}
    </button>
</form>
