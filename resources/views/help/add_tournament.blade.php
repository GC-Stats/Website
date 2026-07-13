{{--
    GC-Stats — "Add a tournament" help page

    Static help page explaining how users can request a tournament to be
    added to GC-Stats.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('help/add_tournament.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('help/add_tournament.title') }}
                </h1>
            </div>

            <p class="text-sm text-gray-400 leading-relaxed italic text-center px-4">
                {{ __('help/add_tournament.intro') }}
            </p>

            <div class="space-y-6 mt-8">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative overflow-hidden">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('help/add_tournament.ticket.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300">
                            {!! __('help/add_tournament.ticket.body') !!}
                        </p>
                        <div class="bg-black/30 border-l-2 border-[#5865F2] p-3 text-xs text-gray-400">
                            {{ __('help/add_tournament.ticket.hint') }}
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('help/add_tournament.info.title') }}
                    </h2>

                    <ul class="space-y-3">
                        @foreach(__('help/add_tournament.info.list') as $item)
                            <li class="flex items-start gap-3 text-sm text-gray-300">
                                <span class="text-gc-yellow mt-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg></span>
                                <span>{!! $item !!}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-bg-main border border-border-subtle p-4 rounded-sm">
                    <p class="text-[10px] text-gray-500 uppercase font-bold text-center">
                        {{ __('help/add_tournament.footer_note') }}
                    </p>
                </div>
            </div>
        </section>
    </div>
@endsection
