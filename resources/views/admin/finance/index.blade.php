{{--
    GC-Stats — Admin: finance

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.finance.title'))

@section('content')
    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" class="flex gap-2 flex-1 min-w-[200px] max-w-lg">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.finance.search_placeholder') }}"
                   class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <select name="type" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.finance.all_types') }}</option>
                @foreach (['income', 'expense'] as $t)
                    <option value="{{ $t }}" @selected($type === $t)>{{ __('admin.finance.type.'.$t) }}</option>
                @endforeach
            </select>
        </form>

        @can('finance.manage')
            <x-modal :title="__('admin.finance.create.title')">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.finance.create.title') }}
                    </button>
                </x-slot:trigger>
                @include('admin.finance._form', ['entry' => null])
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['date', 'admin.finance.date'], ['label', 'admin.finance.label'], ['category', 'admin.finance.category_column'], ['amount', 'admin.finance.amount']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                    <th class="px-4 py-3">{{ __('admin.finance.source') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="border-b border-b-white/10 last:border-b-0 border-l-2 {{ $entry->type === 'income' ? 'border-l-green-500/60' : 'border-l-red-500/60' }}">
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $entry->entry_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-white font-semibold">{{ $entry->label }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $entry->category }}</td>
                        <td class="px-4 py-3 {{ $entry->type === 'income' ? 'text-green-400' : 'text-red-400' }}">
                            {{ number_format($entry->amount_eur, 2) }} €
                            <span class="text-gray-500 text-xs">/ {{ number_format($entry->amount_usd, 2) }} $</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($entry->source_url)
                                <a href="{{ $entry->source_url }}" target="_blank" rel="noopener" class="text-gc-yellow hover:underline text-xs">
                                    @svg('fas-arrow-up-right-from-square', 'w-3 h-3', ['aria-hidden' => 'true'])
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('finance.manage')
                                <div class="flex justify-end gap-2">
                                    <x-modal :title="__('admin.finance.edit_modal.title')" max-width="max-w-md">
                                        <x-slot:trigger>
                                            <button type="button"
                                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                                {{ __('admin.finance.edit') }}
                                            </button>
                                        </x-slot:trigger>
                                        @include('admin.finance._form', ['entry' => $entry])
                                    </x-modal>

                                    <form method="POST" action="{{ route('admin.finance.destroy', $entry) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-confirm-modal
                                            :title="__('admin.finance.delete')"
                                            :body="__('admin.finance.delete_confirm')"
                                            :trigger-label="__('admin.finance.delete')"
                                            :submit-label="__('admin.finance.delete')"
                                            trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                        />
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.finance.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entries->links() }}
@endsection
