{{--
    GC-Stats — Homepage

    Displays live, upcoming and recently finished matches alongside
    featured tournaments and a news sidebar.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('index.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <aside class="col-span-12 lg:col-span-3 space-y-2" aria-label="{{ __('index.news') }}">
            @include('news._sidebar', ['news' => $newsItems, 'newsFeatured' => $newsFeatured])
        </aside>

        <section class="col-span-12 lg:col-span-6 space-y-6" aria-label="{{ __('index.matches') }}">
            <div class="space-y-4">
                @foreach($matches as $match)
                    <div class="{{ $loop->iteration > 4 ? 'hidden lg:block' : '' }}">
                        <x-match :match="$match" />
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="col-span-12 lg:col-span-3 space-y-8" aria-label="{{ __('index.tournaments.sidebar') }}">
            @php $tournamentCount = 0; @endphp
            @foreach($tournaments as $group)
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60">{{ __('index.tournaments.'.$group['label']) ?? $group['label'] }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                    </div>

                    <div class="flex flex-col gap-2">
                        @foreach($group['items'] as $tournament)
                            @php $color = config('regions.colors.' . $tournament['region'], '#666666'); $tournamentCount++; @endphp

                            <a href="{{ route('tournaments.show', [$tournament['id'], str($tournament['name'] ?? '')->slug()]) }}" class="group block {{ $tournamentCount > 6 ? 'hidden lg:block' : '' }}">
                                <div class="relative bg-white/[0.02] rounded-lg overflow-hidden transition-all duration-300 hover:bg-[#111111]">

                                    <div class="absolute left-0 top-0 bottom-0 w-[2px] rounded-l-lg" style="background: {{ $color }}"></div>

                                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                                         style="background: linear-gradient(90deg, {{ $color }}08 0%, transparent 60%)"></div>

                                    <div class="relative flex items-center gap-3 pl-4 pr-3 py-3.5">
                                        <div class="shrink-0 w-9 h-9 flex items-center justify-center">
                                            <img class="w-8 h-8 object-contain opacity-80 group-hover:opacity-100 transition-opacity"
                                                 src="{{ $tournament['logo'] }}" alt="">
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-[11px] font-black uppercase tracking-tight text-white/80 group-hover:text-white transition-colors truncate leading-tight mb-1">
                                                {{ $tournament['name'] }}
                                            </p>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: {{ $color }}"></span>
                                                <span class="text-[8px] font-bold uppercase tracking-widest truncate" style="color: {{ $color }}">
                                            {{ $tournament['region'] }}
                                        </span>
                                                <span class="text-white/10">·</span>
                                                <span class="text-[8px] font-bold text-gray-600 uppercase truncate">
                                            {{ \Carbon\Carbon::parse($tournament['start_date'])->format('d M') }} - {{ \Carbon\Carbon::parse($tournament['end_date'])->format('d M Y') }}
                                        </span>
                                            </div>
                                        </div>

                                        @if(isset($tournament['status']) && $tournament['status'] === 'live')
                                            <div class="shrink-0" role="status" aria-label="{{ __('index.live') }}">
                                                <span class="relative flex h-1.5 w-1.5" aria-hidden="true">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </aside>
    </div>
@endsection
