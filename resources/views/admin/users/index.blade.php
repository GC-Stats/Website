{{--
    GC-Stats — Admin: user directory

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.users.title'))

@section('content')
    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.users.search_placeholder') }}"
               class="flex-1 min-w-[200px] max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

        <select name="role" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="">{{ __('admin.users.all_roles') }}</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected($roleFilter === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>

        <select name="publisher" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="">{{ __('admin.users.all_publishers') }}</option>
            @foreach ($publishers as $publisher)
                <option value="{{ $publisher->id }}" @selected((string) $publisherFilter === (string) $publisher->id)>{{ $publisher->name }}</option>
            @endforeach
        </select>

        @if ($search || $roleFilter || $publisherFilter)
            <a href="{{ route('admin.users.index') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-gray-400 hover:text-white">
                {{ __('admin.users.clear_filters') }}
            </a>
        @endif
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto"
         x-data="GCS.sortableTable()">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-b-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['user', 'admin.users.user'], ['sanctions', 'admin.users.sanctions'], ['joined', 'admin.users.joined']] as [$col, $label])
                        <th class="px-4 py-3" @click="sortBy('{{ $col }}')">
                            <span class="group inline-flex items-center gap-1 hover:text-white transition cursor-pointer select-none">
                                {{ __($label) }}
                                @include('admin.partials.sort-arrows', ['col' => $col])
                            </span>
                        </th>
                        @if ($col === 'user')
                            <th class="px-4 py-3">{{ __('admin.users.roles') }}</th>
                            <th class="px-4 py-3">{{ __('admin.users.publishers') }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody x-ref="tbody">
                @forelse ($users as $user)
                    <tr data-row data-user="{{ $user->name }}" data-sanctions="{{ $user->active_sanctions_count }}" data-joined="{{ $user->created_at?->timestamp ?? 0 }}"
                        class="border-b border-b-white/10 last:border-b-0 hover:bg-white/[0.02] transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="flex items-center gap-3">
                                <div class="w-8 h-8 shrink-0 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-[10px] font-black uppercase text-white">
                                    {{ $user->initials() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-white font-semibold truncate hover:underline">{{ $user->name }}</p>
                                    @if ($user->username)
                                        <p class="text-xs text-gray-500 truncate">{{ '@'.$user->username }}</p>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @forelse ($user->roles as $role)
                                <span class="inline-block px-2 py-1 mr-1 mb-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-300">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-600">{{ __('admin.users.no_roles') }}</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3">
                            @forelse ($publisherNamesByUserId[$user->id] ?? [] as $publisherName)
                                <span class="inline-block px-2 py-1 mr-1 mb-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-white/10 text-gray-300">
                                    {{ $publisherName }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-600">—</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->active_sanctions_count > 0)
                                <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-red-500/10 text-red-400 border border-red-500/30">
                                    {{ $user->active_sanctions_count }}
                                </span>
                            @else
                                <span class="text-xs text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $user->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.users.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
