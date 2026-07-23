<?php

/**
 * GC-Stats — Reaction bar Livewire component
 *
 * Shows the emote reaction summary for a reactable model (News for now —
 * see App\Models\Concerns\HasReactions) and lets a signed-in user toggle
 * reactions, either on an existing pill or by picking a new emote from the
 * reusable resources/views/livewire/emote-picker.blade.php component.
 *
 * The picker dispatches a per-instance event name ("emote-selected-{barId}")
 * rather than a fixed one, so multiple reaction bars can coexist on the
 * same page (e.g. a future news list) without cross-triggering each other.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Models\Emote;
use App\Models\News;
use App\Models\Reaction;
use App\Models\UserReport;
use App\Services\ReactionService;
use App\Services\UserReportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Lazy] class extends Component
{
    // Reactions are polymorphic (reactable_type/reactable_id) so this list
    // is the only thing to extend when reactions reach other content types
    // (forum posts, matches, ...) — see App\Models\Concerns\HasReactions.
    private const ALLOWED_TYPES = [News::class];

    public string $reactableType;

    public int $reactableId;

    public string $barId;

    public bool $pickerOpen = false;

    public ?int $openGroupEmoteId = null;

    public string $reportEmoteId = '';

    public string $reportCategory = '';

    public string $reportReason = '';

    public bool $reportSubmitted = false;

    public function mount(string $reactableType, int $reactableId): void
    {
        abort_unless(in_array($reactableType, self::ALLOWED_TYPES, true), 404);

        $this->reactableType = $reactableType;
        $this->reactableId = $reactableId;
        $this->barId = Str::slug(class_basename($reactableType)).'-'.$reactableId;
    }

    private function reactable(): Model
    {
        return $this->reactableType::findOrFail($this->reactableId);
    }

    /**
     * The very first add and the very first remove of a given emote go
     * through instantly; rapid re-toggling after that is throttled to avoid
     * add/remove spam. Scoped per emote so switching between different
     * emotes isn't penalized by unrelated toggling.
     */
    private function reactionToggleLimiterKey(int $emoteId): string
    {
        return "reaction-toggle:{$this->reactableType}:{$this->reactableId}:{$emoteId}:".Auth::id();
    }

    private function tooManyReactionToggles(int $emoteId): bool
    {
        return RateLimiter::tooManyAttempts($this->reactionToggleLimiterKey($emoteId), 2);
    }

    private function hitReactionToggleLimiter(int $emoteId): void
    {
        RateLimiter::hit($this->reactionToggleLimiterKey($emoteId), 10);
    }

    public function with(): array
    {
        return [
            'summary' => $this->reactable()->reactionSummary(Auth::id()),
            'reactors' => $this->openGroupEmoteId !== null
                ? $this->reactable()->reactionsForEmote($this->openGroupEmoteId)
                : null,
            'blockingSanction' => Auth::user()?->activeGlobalBlockingSanction(),
        ];
    }

    public function togglePicker(): void
    {
        $this->pickerOpen = ! $this->pickerOpen;
    }

    #[On('emote-selected-{barId}')]
    public function react(int $emoteId): void
    {
        if (! Auth::check()) {
            return;
        }

        abort_if(Auth::user()->activeGlobalBlockingSanction(), 403);

        if ($this->tooManyReactionToggles($emoteId)) {
            $this->pickerOpen = false;

            return;
        }

        $emote = Emote::where('is_active', true)->findOrFail($emoteId);

        app(ReactionService::class)->toggle($this->reactable(), Auth::user(), $emote);
        $this->hitReactionToggleLimiter($emoteId);

        $this->pickerOpen = false;
    }

    public function toggleReaction(int $emoteId): void
    {
        if (! Auth::check()) {
            return;
        }

        abort_if(Auth::user()->activeGlobalBlockingSanction(), 403);

        if ($this->tooManyReactionToggles($emoteId)) {
            return;
        }

        $reactable = $this->reactable();
        $alreadyReacted = $reactable->reactions()->where('user_id', Auth::id())->where('emote_id', $emoteId)->exists();

        // Removing an existing reaction is always allowed, even if the
        // emote was deactivated since — only *adding* a new reaction
        // requires it still be active (matches react(), which never lets a
        // user pick an inactive emote in the first place).
        $emote = $alreadyReacted
            ? Emote::findOrFail($emoteId)
            : Emote::where('is_active', true)->findOrFail($emoteId);

        app(ReactionService::class)->toggle($reactable, Auth::user(), $emote);
        $this->hitReactionToggleLimiter($emoteId);
    }

    public function toggleGroup(int $emoteId): void
    {
        abort_unless(Auth::user()?->can('reaction.view'), 403);

        $this->openGroupEmoteId = $this->openGroupEmoteId === $emoteId ? null : $emoteId;
    }

    public function deleteReaction(int $reactionId): void
    {
        abort_unless(Auth::user()?->can('reaction.delete'), 403);

        app(ReactionService::class)->remove(Reaction::findOrFail($reactionId));
    }

    public function deleteAllForEmote(int $emoteId): void
    {
        abort_unless(Auth::user()?->can('reaction.delete'), 403);

        app(ReactionService::class)->removeAllForEmote($this->reactable(), Emote::findOrFail($emoteId));

        if ($this->openGroupEmoteId === $emoteId) {
            $this->openGroupEmoteId = null;
        }
    }

    /**
     * Flag an emote's use on this reactable as inappropriate — concerns
     * every current reactor of that emote, not one individual (see
     * UserReportService::submitForReaction()). One button covers every
     * emote on this reactable; which one is being reported is picked in the
     * form itself rather than needing a trigger per pill. No dedicated
     * route exists for this (it's Livewire-only), so it's throttled here to
     * the same 15-per-hour budget as the users.report route.
     */
    public function submitReactionReport(): void
    {
        abort_unless(Auth::check(), 403);
        abort_if(Auth::user()->activeGlobalBlockingSanction(), 403);

        $limiterKey = 'reaction-report:'.Auth::id();

        if (RateLimiter::tooManyAttempts($limiterKey, 15)) {
            $this->addError('reportReason', __('reactions.report.too_many_attempts'));

            return;
        }

        $this->validate([
            'reportEmoteId' => ['required', 'integer'],
            'reportCategory' => ['required', 'string', Rule::in(UserReport::CATEGORIES)],
            'reportReason' => ['required', 'string', 'max:2000'],
        ]);

        RateLimiter::hit($limiterKey, 3600);

        $emote = Emote::findOrFail($this->reportEmoteId);

        app(UserReportService::class)->submitForReaction(Auth::user(), $this->reactable(), $emote, [
            'category' => $this->reportCategory,
            'reason' => $this->reportReason,
        ]);

        $this->reset(['reportEmoteId', 'reportCategory', 'reportReason']);
        $this->reportSubmitted = true;
    }
}; ?>

<div class="flex flex-wrap items-center gap-2" x-data @click.away="$wire.pickerOpen = false">
    @foreach ($summary as $row)
        <button type="button" wire:click="toggleReaction({{ $row['emote']->id }})"
                @if (! auth()->check() || $blockingSanction) disabled @endif
                title="{{ $blockingSanction ? __('account.errors.sanctioned_global', ['reason' => $blockingSanction->reason]) : $row['emote']->name }}"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full border transition text-xs font-bold
                {{ $row['reacted'] ? 'bg-gc-yellow/10 border-gc-yellow text-gc-yellow' : 'bg-white/5 border-white/10 text-gray-300 hover:border-white/20' }}
                {{ ! auth()->check() || $blockingSanction ? 'cursor-not-allowed' : '' }}">
            <img src="{{ $row['emote']->image_url }}" alt="{{ $row['emote']->name }}" class="w-4 h-4 object-contain">
            <span>{{ $row['count'] }}</span>
        </button>
    @endforeach

    @auth
        @if ($summary->isNotEmpty() && ! $blockingSanction)
            <x-modal :title="__('reactions.report.title')" max-width="max-w-md">
                <x-slot:trigger>
                    <button type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-red-400 hover:border-white/20 transition"
                            title="{{ __('reactions.report.trigger') }}">
                        @svg('fas-flag', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                    </button>
                </x-slot:trigger>

                @if ($reportSubmitted)
                    <p class="text-sm text-green-400">{{ __('reactions.report.thanks') }}</p>
                @else
                    <form wire:submit="submitReactionReport" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('reactions.report.emote_label') }}</label>
                            <select wire:model="reportEmoteId" required
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                <option value=""></option>
                                @foreach ($summary as $row)
                                    <option value="{{ $row['emote']->id }}">{{ $row['emote']->name }}</option>
                                @endforeach
                            </select>
                            @error('reportEmoteId')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('reactions.report.category_label') }}</label>
                            <select wire:model="reportCategory" required
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                <option value=""></option>
                                @foreach (\App\Models\UserReport::CATEGORIES as $category)
                                    <option value="{{ $category }}">{{ __('admin.reports.category.'.$category) }}</option>
                                @endforeach
                            </select>
                            @error('reportCategory')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('reactions.report.reason_label') }}</label>
                            <textarea wire:model="reportReason" rows="3" required
                                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
                            @error('reportReason')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                            {{ __('reactions.report.submit') }}
                        </button>
                    </form>
                @endif
            </x-modal>
        @endif
    @endauth

    @can('reaction.view')
        @if ($summary->isNotEmpty())
            <x-modal :title="__('reactions.admin.modal_title')" max-width="max-w-2xl">
                <x-slot:trigger>
                    <button type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition"
                            title="{{ __('reactions.admin.view_reactors') }}">
                        @svg('fas-list-ul', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                    </button>
                </x-slot:trigger>

                <div class="space-y-1 -mx-2">
                    @foreach ($summary as $row)
                        <div class="rounded-sm border border-transparent {{ $openGroupEmoteId === $row['emote']->id ? 'border-border-subtle bg-white/5' : '' }}">
                            <div class="flex items-center gap-3 px-2 py-2">
                                <button type="button" wire:click="toggleGroup({{ $row['emote']->id }})"
                                        class="flex items-center gap-2 flex-1 min-w-0 text-left">
                                    @svg('fas-chevron-down', 'w-2.5 h-2.5 shrink-0 text-gray-500 transition-transform duration-200 '.($openGroupEmoteId === $row['emote']->id ? '' : '-rotate-90'), ['aria-hidden' => 'true'])
                                    <img src="{{ $row['emote']->image_url }}" alt="{{ $row['emote']->name }}" class="w-4 h-4 object-contain shrink-0">
                                    <span class="text-xs text-gray-300 truncate">{{ $row['emote']->name }}</span>
                                    <span class="text-[10px] font-bold text-gray-500">{{ $row['count'] }}</span>
                                </button>

                                @can('reaction.delete')
                                    <button type="button" wire:click="deleteAllForEmote({{ $row['emote']->id }})"
                                            wire:confirm="{{ __('reactions.admin.confirm_delete_all', ['count' => $row['count']]) }}"
                                            class="shrink-0 text-[10px] font-bold uppercase tracking-widest text-red-400/70 hover:text-red-400 transition">
                                        {{ __('reactions.admin.delete_all') }}
                                    </button>
                                @endcan
                            </div>

                            @if ($openGroupEmoteId === $row['emote']->id)
                                <div class="pl-9 pr-2 pb-2 space-y-0.5 max-h-64 overflow-y-auto">
                                    @foreach ($reactors as $reaction)
                                        <div class="flex items-center justify-between gap-2 px-2 py-1 rounded hover:bg-white/5">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <img src="{{ $reaction->user->gravatarUrl(32) }}" alt=""
                                                     class="w-5 h-5 rounded-full object-cover shrink-0" onerror="this.style.visibility='hidden'">
                                                <span class="truncate text-xs text-gray-300">{{ $reaction->user->name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                @can('sanctions.create')
                                                    <x-sanction-modal :user="$reaction->user">
                                                        <button type="button" class="text-gray-500 hover:text-gc-yellow transition" title="{{ __('admin.reports.issue_sanction') }}">
                                                            @svg('fas-gavel', 'w-3 h-3', ['aria-hidden' => 'true'])
                                                        </button>
                                                    </x-sanction-modal>
                                                @endcan
                                                @can('reaction.delete')
                                                    <button type="button" wire:click="deleteReaction({{ $reaction->id }})"
                                                            wire:confirm="{{ __('reactions.admin.confirm_delete') }}"
                                                            class="text-gray-500 hover:text-red-400 transition" title="{{ __('reactions.admin.delete') }}">
                                                        @svg('fas-xmark', 'w-3 h-3', ['aria-hidden' => 'true'])
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-modal>
        @endif
    @endcan

    @auth
        @if ($blockingSanction)
            <span class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-600 cursor-not-allowed"
                  title="{{ __('account.errors.sanctioned_global', ['reason' => $blockingSanction->reason]) }}">
                @svg('fas-face-smile', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
            </span>
        @else
            <div class="relative">
                <button type="button" wire:click="togglePicker"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition">
                    @svg('fas-face-smile', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                </button>

                <div x-show="$wire.pickerOpen" x-cloak x-transition class="absolute z-20 mt-2 left-0" @click.stop>
                    <livewire:emote-picker :event-name="'emote-selected-'.$barId" :key="'emote-picker-'.$barId" />
                </div>
            </div>
        @endif
    @else
        <a href="{{ route('login') }}"
           class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition"
           title="{{ __('reactions.login_required') }}">
            @svg('fas-face-smile', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
        </a>
    @endauth
</div>
