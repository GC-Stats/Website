{{--
    GC-Stats — Admin: dashboard "recent modifications" widget (teams/players)

    Renders a minimal, paginated list of activity-log entries scoped to a
    single subject type (team or player) — the two dashboard widgets only
    differ in which model/route/lang strings they point at, so this partial
    takes those as parameters instead of duplicating the markup twice.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $isTeam = $type === 'team';
    $langPrefix = 'admin.dashboard.'.$type.'_modifications_widget';
@endphp
<div class="bg-bg-card border border-white/5 rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b border-white/5">
        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __($langPrefix.'.title') }}</h2>
    </div>

    @forelse ($activities as $activity)
        @php
            $subject = $activity->subject;
            $subjectName = $subject ? ($isTeam ? $subject->name : $subject->handle) : __($langPrefix.'.deleted');
            $subjectPhoto = $subject ? ($isTeam ? $subject->logo : $subject->profile_photo) : null;
            $subjectUrl = $subject ? route($isTeam ? 'admin.teams.show' : 'admin.players.show', $subject) : null;
        @endphp
        <a href="{{ auth()->user()->can('matches.view')
            ? ($subject ? route($isTeam ? 'admin.teams.show' : 'admin.players.show', $subject) : null)
            : ($subject ? route($isTeam ? 'teams.show' : 'players.show', $subject) : null) }}"
           class="block px-4 py-3 border-b border-white/5 last:border-0 transition {{ $subjectUrl ? 'hover:bg-white/5' : 'cursor-default' }}">
            <div class="flex items-center justify-between gap-2 min-h-5">
                <div class="flex items-center gap-1.5 min-w-0">
                    @if ($isTeam)
                        <img src="{{ $subjectPhoto ?? asset('storage/images/default-team.webp') }}"
                             alt="" class="w-5 h-5 object-contain shrink-0">
                    @elseif ($subjectPhoto)
                        <img src="{{ $subjectPhoto }}" alt="" class="w-5 h-5 object-cover rounded shrink-0">
                    @else
                        <span class="w-5 h-5 flex items-center justify-center rounded bg-[var(--brand-yellow)]/10 text-[var(--brand-yellow)] text-[9px] font-black shrink-0">
                            {{ strtoupper(substr($subjectName, 0, 1)) }}
                        </span>
                    @endif
                    <span class="text-xs font-bold text-white truncate">{{ $subjectName }}</span>
                </div>
                <span class="text-[10px] text-gray-500 shrink-0">{{ $activity->created_at->format('Y-m-d H:i') }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 mt-2">
                <span class="inline-block px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md bg-[var(--brand-yellow)]/10 text-gc-yellow truncate">
                    {{ \App\Support\ActivityDisplay::label($activity->description) }}
                </span>
                <span class="text-[10px] text-gray-500 shrink-0">{{ $activity->causer->name ?? __('admin.activity.system') }}</span>
            </div>
        </a>
    @empty
        <p class="px-4 py-6 text-center text-gray-500 text-xs">{{ __($langPrefix.'.empty') }}</p>
    @endforelse

    @include('admin.partials.mini-pagination', ['paginator' => $activities])
</div>
