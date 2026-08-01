{{--
    GC-Stats — Change request item value renderer

    Renders one ChangeRequestItem's old_value/new_value pair — never as a raw
    JSON dump: a before/after roster table for roster fields ('roster',
    'roster_history', 'roster_add'), a thumbnail comparison for 'photo'/'logo',
    a before/after table for 'socials' (one column per platform, old/new as
    rows — same row-per-value-set orientation as the roster table, for
    visual consistency), a plain before/after table for any other array
    shape, and plain text for a scalar. Shared between the admin review page
    (admin/change-requests/show.blade.php) and the requester's own tracking
    page (auth/change-requests/show.blade.php) so both sides read the same
    proposal the same way.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['item'])

@if (in_array($item->field, ['roster', 'roster_history', 'roster_add']))
    @php
        $roleLabels = __('team.roster.roles');
        $isPlayerEntity = isset($item->old_value['player_handle']) || isset($item->new_value['player_handle']);
        $entityLabel = $isPlayerEntity ? __('admin.change_requests.roster_player') : __('admin.change_requests.roster_team');
        $entityKey = $isPlayerEntity ? 'player_handle' : 'team_name';
    @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="text-left text-gray-500 uppercase tracking-widest text-[10px]">
                    <th class="py-1 pr-4"></th>
                    <th class="py-1 pr-4">{{ $entityLabel }}</th>
                    <th class="py-1 pr-4">{{ __('team.roster.role') }}</th>
                    <th class="py-1 pr-4">{{ __('team.roster.joined_at') }}</th>
                    <th class="py-1">{{ __('team.roster.left_at') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-white/10 text-gray-400">
                    <td class="py-1.5 pr-4 font-bold uppercase tracking-widest text-[10px]">{{ __('admin.change_requests.old_value') }}</td>
                    <td class="py-1.5 pr-4">{{ $item->old_value[$entityKey] ?? '—' }}</td>
                    <td class="py-1.5 pr-4">{{ isset($item->old_value['role']) ? ($roleLabels[$item->old_value['role']] ?? $item->old_value['role']) : '—' }}</td>
                    <td class="py-1.5 pr-4">{{ $item->old_value['joined_at'] ?? '—' }}</td>
                    <td class="py-1.5">{{ $item->old_value['left_at'] ?? '—' }}</td>
                </tr>
                <tr class="border-t border-white/10 text-gc-yellow">
                    <td class="py-1.5 pr-4 font-bold uppercase tracking-widest text-[10px]">{{ __('admin.change_requests.new_value') }}</td>
                    <td class="py-1.5 pr-4">{{ $item->new_value[$entityKey] ?? '—' }}</td>
                    <td class="py-1.5 pr-4">{{ isset($item->new_value['role']) ? ($roleLabels[$item->new_value['role']] ?? $item->new_value['role']) : '—' }}</td>
                    <td class="py-1.5 pr-4">{{ $item->new_value['joined_at'] ?? '—' }}</td>
                    <td class="py-1.5">{{ $item->new_value['left_at'] ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@elseif (in_array($item->field, ['photo', 'logo']) && ($item->new_value['logo_id'] ?? null))
    @php
        $folder = $item->field === 'logo' ? 'teams' : 'players';
        $currentKey = $item->field === 'logo' ? 'current_logo' : 'current_photo';
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div>
            <p class="font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.old_value') }}</p>
            @if ($item->old_value[$currentKey] ?? null)
                <img src="{{ $item->old_value[$currentKey] }}" alt="" class="w-24 h-24 object-contain border border-white/10 rounded-sm bg-black/40">
            @else
                <p class="text-gray-400">—</p>
            @endif
        </div>
        <div>
            <p class="font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.new_value') }}</p>
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($folder.'/'.$item->new_value['logo_id'].'/200x200.webp') }}"
                 alt="" class="w-24 h-24 object-contain border border-gc-yellow/40 rounded-sm bg-black/40">
        </div>
    </div>
@elseif ($item->field === 'socials' && (is_array($item->old_value) || is_array($item->new_value)))
    @php $platforms = collect(array_keys((array) $item->old_value))->merge(array_keys((array) $item->new_value))->unique()->sort()->values(); @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="text-left text-gray-500 uppercase tracking-widest text-[10px]">
                    <th class="py-1 pr-4"></th>
                    @forelse ($platforms as $platform)
                        <th class="py-1 pr-4">{{ ucfirst($platform) }}</th>
                    @empty
                        <th class="py-1 pr-4">—</th>
                    @endforelse
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-white/10 text-gray-400">
                    <td class="py-1.5 pr-4 font-bold uppercase tracking-widest text-[10px]">{{ __('admin.change_requests.old_value') }}</td>
                    @forelse ($platforms as $platform)
                        <td class="py-1.5 pr-4">{{ $item->old_value[$platform] ?? '—' }}</td>
                    @empty
                        <td class="py-1.5 pr-4">—</td>
                    @endforelse
                </tr>
                <tr class="border-t border-white/10 text-gc-yellow">
                    <td class="py-1.5 pr-4 font-bold uppercase tracking-widest text-[10px]">{{ __('admin.change_requests.new_value') }}</td>
                    @forelse ($platforms as $platform)
                        <td class="py-1.5 pr-4">{{ $item->new_value[$platform] ?? '—' }}</td>
                    @empty
                        <td class="py-1.5 pr-4">—</td>
                    @endforelse
                </tr>
            </tbody>
        </table>
    </div>
@elseif (is_array($item->old_value) || is_array($item->new_value))
    @php $keys = collect(array_keys((array) $item->old_value))->merge(array_keys((array) $item->new_value))->unique()->values(); @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="text-left text-gray-500 uppercase tracking-widest text-[10px]">
                    <th class="py-1 pr-4">{{ __('admin.change_requests.field') }}</th>
                    <th class="py-1 pr-4">{{ __('admin.change_requests.old_value') }}</th>
                    <th class="py-1">{{ __('admin.change_requests.new_value') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($keys as $key)
                    <tr class="border-t border-white/10">
                        <td class="py-1.5 pr-4 font-bold text-gray-300">{{ Str::headline($key) }}</td>
                        <td class="py-1.5 pr-4 text-gray-400">{{ (($item->old_value[$key] ?? null) === null || $item->old_value[$key] === '') ? '—' : $item->old_value[$key] }}</td>
                        <td class="py-1.5 text-gc-yellow">{{ (($item->new_value[$key] ?? null) === null || $item->new_value[$key] === '') ? '—' : $item->new_value[$key] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-1.5 text-gray-500">—</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div>
            <p class="font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.old_value') }}</p>
            <p class="text-gray-400 break-words">{{ ($item->old_value === null || $item->old_value === '') ? '—' : $item->old_value }}</p>
        </div>
        <div>
            <p class="font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.new_value') }}</p>
            <p class="text-gc-yellow break-words">{{ ($item->new_value === null || $item->new_value === '') ? '—' : $item->new_value }}</p>
        </div>
    </div>
@endif
