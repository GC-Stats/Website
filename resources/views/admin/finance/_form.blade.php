{{--
    GC-Stats — Finance entry form (used inside <x-modal>)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
--}}
@php
    $entry ??= null;
@endphp

@php
    $initialCategory = old('category', ($entry && ! in_array($entry->category, $categories, true)) ? 'Other' : ($entry->category ?? ''));
@endphp
<form method="POST" action="{{ $entry ? route('admin.finance.update', $entry) : route('admin.finance.store') }}" class="space-y-4"
      x-data="{ category: '{{ $initialCategory }}' }">
    @csrf
    @if ($entry)
        @method('PATCH')
    @endif

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.finance.form.entry_date_label') }}
            </label>
            <input type="date" name="entry_date" required value="{{ old('entry_date', $entry?->entry_date?->format('Y-m-d')) }}"
                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                {{ __('admin.finance.form.type_label') }}
            </label>
            <select name="type" required
                    class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                @foreach (['income', 'expense'] as $t)
                    <option value="{{ $t }}" @selected(old('type', $entry->type ?? '') === $t)>{{ __('admin.finance.type.'.$t) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.finance.form.category_label') }}
        </label>
        <select name="category" x-model="category" required
                class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            @foreach ($categories as $c)
                <option value="{{ $c }}">{{ __('admin.finance.category.'.$c) }}</option>
            @endforeach
        </select>
        @error('category')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div x-show="category === 'Other'" x-cloak>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.finance.form.custom_category_label') }}
        </label>
        <input type="text" name="custom_category" maxlength="50"
               value="{{ old('custom_category', in_array($entry->category ?? '', $categories) ? '' : ($entry->category ?? '')) }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('custom_category')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.finance.form.label_label') }}
        </label>
        <input type="text" name="label" required minlength="2" maxlength="100" value="{{ old('label', $entry->label ?? '') }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('label')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.finance.form.description_label') }}
        </label>
        <textarea name="description" rows="2" maxlength="1000"
                  class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('description', $entry->description ?? '') }}</textarea>
    </div>

    @if ($entry)
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.finance.form.amount_eur_label') }}
                </label>
                <input type="number" name="amount_eur" step="0.01" min="0.01" required value="{{ old('amount_eur', $entry->amount_eur) }}"
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                @error('amount_eur')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.finance.form.amount_usd_label') }}
                </label>
                <input type="number" name="amount_usd" step="0.01" min="0.01" required value="{{ old('amount_usd', $entry->amount_usd) }}"
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                @error('amount_usd')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.finance.form.amount_label') }}
                </label>
                <input type="number" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}"
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                @error('amount')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.finance.form.currency_label') }}
                </label>
                <select name="currency" required
                        class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @foreach ($currencies as $c)
                        <option value="{{ $c }}" @selected(old('currency', 'EUR') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('admin.finance.form.source_url_label') }}
        </label>
        <input type="url" name="source_url" maxlength="255" value="{{ old('source_url', $entry->source_url ?? '') }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('source_url')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit"
            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
        {{ $entry ? __('admin.finance.edit_modal.submit') : __('admin.finance.create.submit') }}
    </button>
</form>
