{{--
    GC-Stats — Admin: user detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $user->name)

@section('content')
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-500 hover:text-white transition mb-6">
        @svg('fas-arrow-left', 'w-3 h-3', ['aria-hidden' => 'true'])
        {{ __('admin.users.back_to_list') }}
    </a>

    <div class="flex items-center gap-4 mb-8">
        <x-user-avatar :user="$user" class="w-14 h-14 rounded-lg bg-white/5 border border-white/10 text-sm" />
        <div class="min-w-0">
            <h2 class="text-lg font-black text-white truncate">{{ $user->name }}</h2>
            @if ($user->username)
                <p class="text-sm text-gray-500 truncate">{{ '@'.$user->username }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.account_title') }}</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('admin.users.email') }}</dt>
                    <dd class="text-white truncate">{{ $user->email }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('admin.users.joined') }}</dt>
                    <dd class="text-white">{{ $user->created_at?->format('Y-m-d') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.login_methods_title') }}</p>
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400 flex items-center gap-2">
                        @svg('fas-key', 'w-3 h-3 text-gray-600', ['aria-hidden' => 'true'])
                        {{ __('admin.users.password') }}
                    </span>
                    <span class="text-xs {{ $user->password ? 'text-green-400' : 'text-gray-600' }}">
                        {{ $user->password ? __('admin.users.enabled') : __('admin.users.disabled') }}
                    </span>
                </div>

                @foreach ($user->socialAccounts as $account)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-400 flex items-center gap-2">
                            @svg('fab-'.$account->provider, 'w-3 h-3 text-gray-600', ['aria-hidden' => 'true'])
                            {{ ucfirst($account->provider) }}
                        </span>
                        <span class="text-xs text-gray-500 truncate">{{ $account->nickname }}</span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400 flex items-center gap-2">
                        @svg('fas-fingerprint', 'w-3 h-3 text-gray-600', ['aria-hidden' => 'true'])
                        {{ __('admin.users.passkeys') }}
                    </span>
                    <span class="text-xs {{ $user->passkeys->isNotEmpty() ? 'text-green-400' : 'text-gray-600' }}">
                        {{ $user->passkeys->count() }}
                    </span>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400 flex items-center gap-2">
                        @svg('fas-shield-halved', 'w-3 h-3 text-gray-600', ['aria-hidden' => 'true'])
                        {{ __('admin.users.two_factor') }}
                    </span>
                    <span class="text-xs {{ $user->two_factor_confirmed_at ? 'text-green-400' : 'text-gray-600' }}">
                        {{ $user->two_factor_confirmed_at ? __('admin.users.enabled') : __('admin.users.disabled') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.global_roles_title') }}</p>
            @forelse ($user->roles as $role)
                <a href="{{ route('admin.roles.show', $role) }}"
                   class="inline-block px-2 py-1 mr-1 mb-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-white/20 transition">
                    {{ $role->name }}
                </a>
            @empty
                <p class="text-xs text-gray-600">{{ __('admin.users.no_global_roles') }}</p>
            @endforelse
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.team_roles_title') }}</p>
            @forelse ($teamRoles as $team)
                <div class="flex items-center justify-between gap-3 py-1.5 border-b border-b-white/10 last:border-b-0">
                    <a href="{{ route('admin.teams.show', $team['id']) }}" class="text-sm text-white hover:underline truncate">{{ $team['name'] }}</a>
                    <div class="shrink-0">
                        @foreach ($team['roles'] as $roleName)
                            <span class="inline-block px-2 py-0.5 ml-1 text-[9px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-400">{{ $roleName }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-600">{{ __('admin.users.no_team_roles') }}</p>
            @endforelse
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.publisher_roles_title') }}</p>
            @forelse ($publisherRoles as $publisher)
                <div class="flex items-center justify-between gap-3 py-1.5 border-b border-b-white/10 last:border-b-0">
                    <a href="{{ route('admin.news.publishers.show', $publisher['id']) }}" class="text-sm text-white hover:underline truncate">{{ $publisher['name'] }}</a>
                    <div class="shrink-0">
                        @foreach ($publisher['roles'] as $roleName)
                            <span class="inline-block px-2 py-0.5 ml-1 text-[9px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-400">{{ $roleName }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-600">{{ __('admin.users.no_publisher_roles') }}</p>
            @endforelse
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.player_title') }}</p>
            @if ($player)
                <div class="flex items-center justify-between gap-3 mb-3">
                    <span class="text-sm text-white">{{ $player->handle }}</span>
                    <a href="{{ route('admin.players.show', $player) }}"
                       class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.users.view_player') }}
                    </a>
                </div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.users.current_teams') }}</p>
                @forelse ($player->teams as $team)
                    <a href="{{ route('admin.teams.show', $team) }}"
                       class="inline-block px-2 py-1 mr-1 mb-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-white/20 transition">
                        {{ $team->name }}
                    </a>
                @empty
                    <p class="text-xs text-gray-600">{{ __('admin.users.no_current_team') }}</p>
                @endforelse
            @else
                <p class="text-xs text-gray-600">{{ __('admin.users.no_player') }}</p>
            @endif
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4 lg:col-span-2">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.users.sanctions_title') }}</p>
            @forelse ($sanctions as $sanction)
                <div class="flex items-center justify-between gap-4 py-2 border-b border-b-white/10 last:border-b-0 text-sm">
                    <div class="min-w-0">
                        <span class="text-white font-semibold">{{ __('admin.sanctions.type.'.$sanction->type) }}</span>
                        <span class="text-gray-500 ml-2 truncate">{{ $sanction->reason }}</span>
                    </div>
                    <div class="shrink-0 text-xs text-gray-500 flex items-center gap-3">
                        @if ($sanction->team)
                            <span>{{ $sanction->team->name }}</span>
                        @endif
                        <span>{{ $sanction->ends_at?->format('Y-m-d') ?? __('admin.sanctions.permanent') }}</span>
                        @if (! $sanction->isActive())
                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-widest rounded-lg bg-gray-500/10 text-gray-500 border border-gray-500/30">
                                {{ __('admin.api_keys.inactive') }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-600">{{ __('admin.users.no_sanctions') }}</p>
            @endforelse
        </div>
    </div>
@endsection
