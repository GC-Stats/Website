{{--
    GC-Stats — Admin: winner/loser qualification rules for a bracket match

    Only meaningful for a match whose phase format is "bracket" — a bracket's
    rank isn't a continuous range like swiss/round_robin, so its
    qualification rules are attached to specific matches (winner/loser)
    instead of a rank range. See admin/tournaments/_phase-qualifications.blade.php
    for the swiss/round_robin equivalent.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@if ($match->tournamentPhase?->format === 'bracket')
    <div class="lg:col-span-12 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-3">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.matches.qualifications.title') }}</h2>

        @forelse ($match->qualifications as $rule)
            <div class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                <span class="text-xs text-gray-300">
                    {{ __('admin.matches.qualifications.outcome.'.$rule->outcome) }}
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
                                $rule->formattedCashPrize(),
                            ])->filter()->implode(' · ') }})
                        </span>
                    @endif
                </span>
                @can('matches.edit')
                    <form method="POST" action="{{ route('admin.tournaments.qualifications.destroy', [$tournament, $rule]) }}"
                          onsubmit="return confirm('{{ __('admin.matches.qualifications.delete_confirm') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs">&times;</button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="text-[11px] text-gray-500 italic">{{ __('admin.matches.qualifications.empty') }}</p>
        @endforelse

        @can('matches.edit')
            <div x-data="{ destType: 'phase', open: false }">
                <button type="button" @click="open = !open" class="text-[10px] font-bold uppercase tracking-widest text-gc-yellow hover:underline">
                    + {{ __('admin.matches.qualifications.add') }}
                </button>

                <form x-show="open" x-cloak method="POST" action="{{ route('admin.matches.qualifications.store', [$tournament, $match]) }}"
                      class="mt-2 space-y-2 bg-black/20 border border-white/10 rounded-lg p-3 max-w-md">
                    @csrf

                    <select name="outcome" required
                            class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                        <option value="winner">{{ __('admin.matches.qualifications.outcome.winner') }}</option>
                        <option value="loser">{{ __('admin.matches.qualifications.outcome.loser') }}</option>
                    </select>

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
                        {{ __('admin.matches.qualifications.add') }}
                    </button>
                </form>
            </div>
        @endcan
    </div>
@endif
