{{--
    GC-Stats — About team member form (used inside <x-modal>)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
--}}
<form method="POST" action="{{ $member ? route('admin.about.team.update', $member) : route('admin.about.team.store') }}" class="space-y-4">
    @csrf
    @if ($member)
        @method('PUT')
    @endif

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.about.member_name_label') }}
        </label>
        <input type="text" name="name" required maxlength="100" value="{{ old('name', $member->name ?? '') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </div>

    @foreach ($locales as $locale)
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.about.member_role_label') }} ({{ strtoupper($locale) }})
            </label>
            <input type="text" name="role[{{ $locale }}]" maxlength="100" value="{{ $member->role[$locale] ?? '' }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </div>
    @endforeach

    @foreach ($locales as $locale)
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.about.member_bio_label') }} ({{ strtoupper($locale) }})
            </label>
            <textarea name="bio[{{ $locale }}]" rows="2"
                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ $member->bio[$locale] ?? '' }}</textarea>
        </div>
    @endforeach

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.about.socials_label') }}
        </label>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($socialPlatforms as $platform)
                <input type="text" name="socials[{{ $platform }}]" placeholder="{{ __('admin.about.social.'.$platform) }}"
                       value="{{ $member->socials[$platform] ?? '' }}"
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.about.order_label') }}
            </label>
            <input type="number" name="order" value="{{ old('order', $member->order ?? 0) }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
        </div>
        <label class="flex items-center gap-2 text-xs text-gray-400 mb-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $member->is_active ?? true))
                   class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
            {{ __('admin.about.active_label') }}
        </label>
    </div>

    <button type="submit"
            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
        {{ __('admin.about.save') }}
    </button>
</form>
