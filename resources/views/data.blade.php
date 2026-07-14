{{--
    GC-Stats — Data sources page

    Static page explaining where GC-Stats sources its data from.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('data.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">
            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {!! __('data.styled_title') !!}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.2em]">
                    {{ __("data.subtitle") }}
                </p>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative">
                <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="text-gc-yellow">#</span> {{ __('data.opendata.title') }}
                </h2>
                <p class="text-sm text-gray-300 leading-relaxed mb-4">
                    {{ __('data.opendata.body') }}
                </p>

                <a href="https://data.gc-stats.app" target="_blank" class="inline-flex items-center gap-2 bg-white hover:bg-gray-200 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                    <x-fas-database class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> {{ __('data.opendata.btn') }}
                </a>
            </div>

            <div class="border-b border-border-subtle pb-8 text-center mt-12">
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.4em]">
                    {{ __("data.titles.player_team") }}
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">01.</span> Players
                    </h2>

                    <ul class="space-y-4 mt-4">
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.players.titles.main") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["id", "handle"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.players.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.players.titles.additional") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["first_name", "last_name", "country_code", "bio", "socials", "vlr_id", "liquipedia_link"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.players.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.players.titles.confidential") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["discord_id", "val_id"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.players.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-bg-main border border-gc-yellow/30 rounded-sm p-6 shadow-xl flex flex-col justify-center relative">
                    <div class="text-center space-y-4">
                        <h2 class="text-[10px] font-black text-white uppercase tracking-[0.3em] mb-4">Link</h2>

                        <div class="flex items-center justify-between px-4">
                            <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-gc-yellow"></div>
                            <div class="px-3 py-1 border border-gc-yellow text-gc-yellow text-[10px] font-black uppercase">
                                Player_Team
                            </div>
                            <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-gc-yellow"></div>
                        </div>

                        <ul class="space-y-4 mt-6 items-center">
                            <li class="flex flex-col gap-1">
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach(["player_id", "team_id", "role", "joined_at", "left_at"] as $name)
                                        <div class="group relative w-max">
                                            <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                                {{ $name }}
                                            </div>

                                            <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                                <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                    {{ __("data.player_team.".$name) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">02.</span> Teams
                    </h2>

                    <ul class="space-y-4 mt-4">
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.teams.titles.main") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["id", "name"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.teams.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.teams.titles.additional") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["country_code", "website", "socials", "bio", "vlr_id", "liquipedia_link"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.teams.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-bg-card border-l-2 border-gc-yellow p-6 flex items-center gap-6 shadow-xl">
                <div class="text-gc-yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[12px] text-gray-300 leading-relaxed ">
                        {!! __("data.descriptions.player_team") !!}
                    </p>
                </div>
            </div>

            <div class="border-b border-border-subtle pb-8 text-center mt-12">
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.4em]">
                    {{ __("data.titles.tournament") }}
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">03.</span> Tournaments
                    </h2>

                    <ul class="space-y-4 mt-4">
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.tournaments.titles.main") }}</span>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["id", "name", "region", "category", "status"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.tournaments.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.tournaments.titles.additional") }}</span>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["prize_pool", "location", "start_date", "end_date", "vlr_id", "liquipedia_link"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.tournaments.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">04.</span> Tournament_phases
                    </h2>

                    <ul class="space-y-4 mt-4">
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.tournament_phases.titles.structure") }}</span>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["name", "format", "order", "parent_id"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>
                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.tournament_phases.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-bg-main border border-gc-yellow/30 rounded-sm p-6 shadow-xl flex flex-col justify-center relative text-center">
                    <h2 class="text-[10px] font-black text-white uppercase tracking-[0.3em] mb-4 italic">Participants</h2>

                    <div class="flex items-center justify-center gap-2">
                        <div class="group relative w-max">
                            <div class="px-3 py-2 border border-gc-yellow text-gc-yellow text-[10px] font-black uppercase">
                                Tournament_Teams
                            </div>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-20 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                    {{ __("data.tournament_teams.link") }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p class="text-[9px] text-gray-500 uppercase mt-4 tracking-tighter">
                        {{ __("data.tournament_teams.team_list") }}
                    </p>
                </div>
            </div>
            <div class="bg-bg-card border-l-2 border-gc-yellow p-6 flex items-center gap-6 shadow-xl">
                <div class="text-gc-yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[12px] text-gray-300 leading-relaxed ">
                        {!! __("data.descriptions.tournament") !!}
                    </p>
                </div>
            </div>

            <div class="border-b border-border-subtle pb-8 text-center mt-12">
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.4em]">
                    {{ __("data.titles.matches") }}
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-12">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">05.</span> Matches
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "tournament_id", "phase_id", "round_number", "round_name", "match_order", "team_a_id", "team_b_id", "scheduled_at", "status", "team_a_score", "team_b_score", "best_of", "patch"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.matches.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">06.</span> Match_Vetos
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "match_id", "team_id", "map_name", "type", "order"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.match_vetos.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">07.</span> Game_Maps
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "api_match_id", "match_id", "map_name", "team_a_score", "team_b_score", "order", "is_completed"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.game_maps.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative md:col-span-2">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">08.</span> Game_Player_Stats
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "match_id", "game_map_id", "player_id", "team_id", "agent_name", "kills", "deaths", "assists", "acs", "adr", "kast_percentage", "first_kills", "first_deaths", "headshot_percentage"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.game_player_stats.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative md:col-span-2 lg:col-span-3">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">09.</span> Game_Player_Advanced_Stats
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "game_map_id", "player_id", "agent_name", "clutch_1v1_won", "clutch_1v1_total", "clutch_1v2_won", "clutch_1v2_total", "clutch_1v3_won", "clutch_1v3_total", "clutch_1v4_won", "clutch_1v4_total", "clutch_1v5_won", "clutch_1v5_total", "multikill_2k", "multikill_3k", "multikill_4k", "multikill_5k", "trade_kills", "traded_deaths", "plants", "defuses", "pistol_won", "pistol_played", "eco_won", "eco_played", "force_won", "force_played", "full_buy_won", "full_buy_played", "post_plant_won", "post_plant_played", "atk_rounds", "atk_rounds_won", "atk_kills", "atk_kast_percentage", "def_rounds", "def_rounds_won", "def_kills", "def_kast_percentage"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.game_player_advanced_stats.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">10.</span> Game_Map_Rounds
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "game_map_id", "round_number", "winning_team", "win_type"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.game_map_rounds.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative md:col-span-2 lg:col-span-3">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2 mb-4">
                        <span class="text-gc-yellow">11.</span> Game_Map_Round_Player_Stats
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(["id", "game_map_round_id", "player_id", "kills", "assists", "score", "economy_spent", "economy_remaining", "weapon_id", "armor"] as $name)
                            <div class="group relative w-max">
                                <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                    {{ $name }}
                                </div>
                                <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-50 w-48 bg-black border border-border-subtle p-2 shadow-2xl">
                                    <p class="text-[9px] text-white uppercase font-bold">{{ __("data.game_map_round_player_stats.".$name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="bg-bg-card border-l-2 border-gc-yellow p-6 flex items-center gap-6 shadow-xl">
                <div class="text-gc-yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[12px] text-gray-300 leading-relaxed ">
                        {!! __("data.descriptions.matches") !!}
                    </p>
                </div>
            </div>

            <div class="border-b border-border-subtle pb-8 text-center mt-12">
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.4em]">
                    {{ __("data.titles.news") }}
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">12.</span> News
                    </h2>

                    <ul class="space-y-4 mt-4">
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.news.titles.main") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["id", "author", "title", "content"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.news.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.news.titles.additional") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["status", "is_featured", "published_at"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.news.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-bg-main border border-gc-yellow/30 rounded-sm p-6 shadow-xl flex flex-col justify-center relative">
                    <div class="text-center space-y-4">
                        <h2 class="text-[10px] font-black text-white uppercase tracking-[0.3em] mb-4">Link</h2>

                        <div class="flex items-center justify-between px-4">
                            <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-gc-yellow"></div>
                            <div class="px-3 py-1 border border-gc-yellow text-gc-yellow text-[10px] font-black uppercase">
                                news_relations
                            </div>
                            <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-gc-yellow"></div>
                        </div>

                        <ul class="space-y-4 mt-6 items-center">
                            <li class="flex flex-col gap-1">
                                <div class="flex flex-wrap gap-2 mt-2">
                                    {{ __("data.news.relation") }}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="bg-bg-card border-l-2 border-gc-yellow p-6 flex items-center gap-6 shadow-xl">
                <div class="text-gc-yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[12px] text-gray-300 leading-relaxed ">
                        {!! __("data.descriptions.news") !!}
                    </p>
                </div>
            </div>


            <div class="border-b border-border-subtle pb-8 text-center mt-12">
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.4em]">
                    {{ __("data.titles.others") }}
                </p>
            </div>
            <div class="grid grid-cols-1 gap-6">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-2xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">13.</span> Page_views
                    </h2>

                    <ul class="space-y-4 mt-4">
                        <li class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase italic">{{ __("data.others.titles.main") }}</span>

                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach(["uri", "viewed_at", "count"] as $name)
                                    <div class="group relative w-max">
                                        <div class="px-2 py-1 border border-gray-700 text-gray-500 group-hover:border-gc-yellow group-hover:text-white text-[9px] font-black uppercase transition-all cursor-help">
                                            {{ $name }}
                                        </div>

                                        <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-20 w-40 bg-black border border-border-subtle p-2 shadow-2xl">
                                            <p class="text-[9px] leading-tight text-gray-400 uppercase font-bold">
                                                {{ __("data.others.".$name) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-bg-card border-l-2 border-gc-yellow p-6 flex items-center gap-6 shadow-xl">
                <div class="text-gc-yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[12px] text-gray-300 leading-relaxed ">
                        {!! __("data.descriptions.others") !!}
                    </p>
                </div>
            </div>
        </section>
    </div>
@endsection
