{{--
    Shared result display for both the AI query screen and the query
    builder — relies on the enclosing x-data scope exposing `response`,
    `resultColumns`, `resultRows`, `rawJson`, `copied` and `copyResult()`
    (see window.dataExplorer / window.dataExplorerBuilder in resources/js/app.js).
    $slot (optional) renders left of the copy button — e.g. a "provider
    used" or "N rows" badge specific to the caller.
--}}
<template x-if="response">
    <div class="space-y-3" x-cloak>
        <div class="flex items-center justify-between gap-4">
            <div>{{ $slot ?? '' }}</div>

            <button type="button" @click="copyResult()"
                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                <span x-show="!copied">{{ __('data_explorer.index.copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('data_explorer.index.copied') }}</span>
            </button>
        </div>

        {{-- Result: highlighted table — this is the answer to the question.
             If Cube returned zero rows, say so explicitly rather than showing
             an empty box, but the raw JSON (incl. the query that produced
             nothing) stays one click away below either way. --}}
        <div class="bg-[#050505] border border-gc-yellow/30 rounded-sm overflow-x-auto">
            <table class="w-full text-sm text-left" x-show="resultColumns.length > 0">
                <thead>
                    <tr class="border-b border-b-border-subtle text-[10px] font-black uppercase tracking-widest text-gray-500">
                        <template x-for="col in resultColumns" :key="col">
                            <th class="px-4 py-2.5" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, i) in resultRows" :key="i">
                        <tr class="border-b border-b-border-subtle last:border-0">
                            <template x-for="col in resultColumns" :key="col">
                                <td class="px-4 py-2.5 text-gc-yellow font-semibold" x-text="row[col]"></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p x-show="resultColumns.length === 0" class="text-xs text-gray-500 p-4">{{ __('data_explorer.index.no_rows') }}</p>
        </div>

        {{-- Raw response: context, kept visually secondary but always available --}}
        <details class="text-xs">
            <summary class="text-gray-500 hover:text-white transition cursor-pointer select-none">{{ __('data_explorer.index.show_raw_json') }}</summary>
            <pre class="mt-2 bg-[#050505] border border-border-subtle rounded-sm p-4 text-gray-400 whitespace-pre-wrap overflow-x-auto" x-text="rawJson"></pre>
        </details>
    </div>
</template>
