{{--
    GC-Stats — Finance ledger month partial

    Renders a single month's worth of finance ledger entries as a table.
    Expects $month (Y-m string), $monthEntries (collection) and the
    $formatAmount closure from the parent view.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="space-y-3">
    <div class="border-b border-border-subtle pb-2">
        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-white/90">
            {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
        </h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[9px] font-black uppercase tracking-widest text-gray-500 border-b border-border-subtle">
                    <th class="py-2 pr-4">{{ __('finance.table.date') }}</th>
                    <th class="py-2 pr-4">{{ __('finance.table.category') }}</th>
                    <th class="py-2 pr-4">{{ __('finance.table.label') }}</th>
                    <th class="py-2 pr-4 text-right">{{ __('finance.table.amount') }}</th>
                    <th class="py-2 pr-4 text-right">{{ __('finance.table.source') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthEntries as $entry)
                    @php
                        $isIncome = $entry->type === 'income';
                    @endphp
                    <tr class="border-b border-border-subtle/50 text-gray-300 align-top">
                        <td class="py-2 pr-4 whitespace-nowrap text-gray-500">
                            {{ $entry->entry_date->format('d/m/Y') }}
                        </td>
                        <td class="py-2 pr-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-sm text-[9px] font-black uppercase tracking-widest bg-white/5 text-gray-300">
                                {{ $entry->category }}
                            </span>
                        </td>
                        <td class="py-2 pr-4">
                            <p>{{ $entry->label }}</p>
                            @if($entry->description)
                                <p class="text-[11px] text-gray-500 leading-snug mt-1">{!! nl2br(e($entry->description)) !!}</p>
                            @endif
                        </td>
                        <td class="py-2 pr-4 text-right font-bold whitespace-nowrap {{ $isIncome ? 'text-green-400' : 'text-red-400' }}"
                            x-text="currency === 'EUR'
                                ? @js(($isIncome ? '+' : '-') . $formatAmount((float) $entry->amount_eur, 'EUR'))
                                : @js(($isIncome ? '+' : '-') . $formatAmount((float) $entry->amount_usd, 'USD'))"></td>
                        <td class="py-2 pr-4 text-right whitespace-nowrap">
                            @if($entry->source_url)
                                <a href="{{ $entry->source_url }}" target="_blank" rel="noopener noreferrer"
                                   class="text-gc-yellow hover:underline text-[10px] font-bold uppercase">
                                    {{ __('finance.table.view_source') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
