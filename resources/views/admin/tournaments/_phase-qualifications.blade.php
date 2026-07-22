{{--
    GC-Stats — Admin: rank-based qualification rules for a swiss/round_robin phase

    Bracket phases don't get this block — their qualification rules are
    attached per match instead (see admin/matches/show.blade.php), since a
    bracket's rank isn't a continuous range the way swiss/round_robin's is.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@if (in_array($phase->format, \App\Models\TournamentPhase::RANK_BASED_FORMATS, true))
    <div class="mt-2 space-y-2">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('admin.tournaments.qualifications.title') }}</p>

        @forelse ($phase->qualifications as $rule)
            <div class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                <span class="text-xs text-gray-300">
                    {{ __('admin.tournaments.qualifications.rank_range', ['from' => $rule->rank_from, 'to' => $rule->rank_to]) }}
                    &rarr;
                    <span class="text-white font-semibold">
                        {{ $rule->destination_type === 'phase'
                            ? ($rule->destinationPhase->tournament->name.' — '.$rule->destinationPhase->name)
                            : ($rule->placement_label ?: '#'.$rule->placement) }}
                    </span>
                    @if ($rule->points || $rule->cash_prize_amount)
                        <span class="text-gray-500">
                            ({{ collect([
                                $rule->points ? $rule->points.' pts' : null,
                                $rule->cash_prize_amount ? number_format($rule->cash_prize_amount, 2).' '.$rule->cash_prize_currency : null,
                            ])->filter()->implode(' · ') }})
                        </span>
                    @endif
                </span>
                @can('tournaments.edit')
                    <form method="POST" action="{{ route('admin.tournaments.qualifications.destroy', [$tournament, $rule]) }}"
                          onsubmit="return confirm('{{ __('admin.tournaments.qualifications.delete_confirm') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs">&times;</button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="text-[11px] text-gray-500 italic">{{ __('admin.tournaments.qualifications.empty') }}</p>
        @endforelse

        @can('tournaments.edit')
            <div x-data="{ destType: 'phase', open: false }">
                <button type="button" @click="open = !open" class="text-[10px] font-bold uppercase tracking-widest text-gc-yellow hover:underline">
                    + {{ __('admin.tournaments.qualifications.add') }}
                </button>

                <form x-show="open" x-cloak method="POST" action="{{ route('admin.tournaments.phases.qualifications.store', [$tournament, $phase]) }}"
                      class="mt-2 space-y-2 bg-black/20 border border-white/10 rounded-lg p-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="rank_from" min="1" placeholder="{{ __('admin.tournaments.qualifications.rank_from') }}" required
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <input type="number" name="rank_to" min="1" placeholder="{{ __('admin.tournaments.qualifications.rank_to') }}" required
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="destType = 'phase'"
                                :class="destType === 'phase' ? 'bg-gc-yellow text-black' : 'bg-white/5 border border-white/10 text-white hover:bg-white/10'"
                                class="flex-1 font-bold uppercase text-[10px] tracking-widest px-2 py-1.5 rounded-lg transition">
                            {{ __('admin.tournaments.qualifications.destination_phase') }}
                        </button>
                        <button type="button" @click="destType = 'placement'"
                                :class="destType === 'placement' ? 'bg-gc-yellow text-black' : 'bg-white/5 border border-white/10 text-white hover:bg-white/10'"
                                class="flex-1 font-bold uppercase text-[10px] tracking-widest px-2 py-1.5 rounded-lg transition">
                            {{ __('admin.tournaments.qualifications.destination_placement') }}
                        </button>
                    </div>
                    <input type="hidden" name="destination_type" :value="destType">

                    <div x-show="destType === 'phase'">
                        <x-phase-picker name="destination_phase_id" :label="__('admin.tournaments.qualifications.destination_phase')" :search-url="route('admin.tournaments.phases.search')" />
                    </div>

                    <div x-show="destType === 'placement'" class="grid grid-cols-2 gap-2">
                        <input type="number" name="placement" min="1" placeholder="{{ __('admin.tournaments.qualifications.placement_sort') }}"
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <input type="text" name="placement_label" maxlength="50" placeholder="{{ __('admin.tournaments.qualifications.placement_label') }}"
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>

                    {{-- Points/cash prize only apply to a final placement, not a phase-advancement rule. --}}
                    <div x-show="destType === 'placement'" class="grid grid-cols-3 gap-2">
                        <input type="number" name="points" min="0" placeholder="{{ __('admin.tournaments.qualifications.points') }}"
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <input type="number" name="cash_prize_amount" min="0" step="0.01" placeholder="{{ __('admin.tournaments.qualifications.cash_prize_amount') }}"
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <input type="text" name="cash_prize_currency" maxlength="3" placeholder="{{ __('admin.tournaments.qualifications.cash_prize_currency') }}"
                               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white uppercase focus:outline-none focus:border-gc-yellow transition">
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105">
                        {{ __('admin.tournaments.qualifications.add') }}
                    </button>
                </form>
            </div>
        @endcan
    </div>
@elseif ($phase->format === 'bracket')
    <p class="text-[11px] text-gray-500 italic mt-2">{{ __('admin.tournaments.qualifications.bracket_hint') }}</p>
@endif
