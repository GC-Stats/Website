{{--
    GC-Stats — Admin: match streams panel

    Included from admin/matches/show.blade.php only, gated by $canLinkStreams
    (streams.matches.link or publisher.streams.link — see
    Admin\MatchController::show()). Expects $tournament, $match (with
    'streams.publisher' eager-loaded).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="lg:col-span-12 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-4">{{ __('admin.streams.match.title') }}</h2>

    <div class="space-y-2 mb-4">
        @forelse ($match->streams as $stream)
            <div class="flex items-center justify-between gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5">
                <a href="{{ $stream->url }}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center gap-2 text-sm text-white hover:text-gc-yellow transition min-w-0">
                    @svg($stream->icon(), 'w-3.5 h-3.5 flex-shrink-0', ['aria-hidden' => 'true'])
                    <span class="fi fi-{{ $stream->language_code === \App\Support\Countries::INTERNATIONAL ? 'un' : $stream->language_code }} flex-shrink-0"></span>
                    <span class="truncate">{{ $stream->name }}</span>
                </a>
                <form method="POST" action="{{ route('admin.matches.streams.destroy', [$tournament, $match, $stream]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-300 transition flex-shrink-0">
                        {{ __('admin.streams.match.unlink') }}
                    </button>
                </form>
            </div>
        @empty
            <p class="text-xs text-gray-500">{{ __('admin.streams.match.empty') }}</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.matches.streams.store', [$tournament, $match]) }}" class="flex items-end gap-3 pt-4 border-t border-white/10">
        @csrf
        <div class="flex-1">
            <x-relation-picker
                name="stream_channel_id"
                type="streams"
                :label="__('admin.streams.match.picker_label')"
                :search-url="route('admin.matches.streams.search', ['match_id' => $match->id])"
                :selected="[]"
            />
        </div>
        <button type="submit"
                class="font-bold uppercase text-[10px] tracking-widest px-4 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105">
            {{ __('admin.streams.match.add') }}
        </button>
    </form>
</div>
