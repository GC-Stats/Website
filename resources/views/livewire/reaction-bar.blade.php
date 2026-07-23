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
 * @link      https://github.com/GC-Stats/Website
 */

use App\Models\Emote;
use App\Models\News;
use App\Services\ReactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Lazy] class extends Component {
    // Reactions are polymorphic (reactable_type/reactable_id) so this list
    // is the only thing to extend when reactions reach other content types
    // (forum posts, matches, ...) — see App\Models\Concerns\HasReactions.
    private const ALLOWED_TYPES = [News::class];

    public string $reactableType;

    public int $reactableId;

    public string $barId;

    public bool $pickerOpen = false;

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

    public function with(): array
    {
        return [
            'summary' => $this->reactable()->reactionSummary(Auth::id()),
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

        $emote = Emote::where('is_active', true)->findOrFail($emoteId);

        app(ReactionService::class)->toggle($this->reactable(), Auth::user(), $emote);

        $this->pickerOpen = false;
    }

    public function toggleReaction(int $emoteId): void
    {
        if (! Auth::check()) {
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
    }
}; ?>

<div class="flex flex-wrap items-center gap-2" x-data @click.away="$wire.pickerOpen = false">
    @foreach ($summary as $row)
        <button type="button" wire:click="toggleReaction({{ $row['emote']->id }})"
                @if (! auth()->check()) disabled @endif
                title="{{ $row['emote']->name }}"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full border transition text-xs font-bold
                {{ $row['reacted'] ? 'bg-gc-yellow/10 border-gc-yellow text-gc-yellow' : 'bg-white/5 border-white/10 text-gray-300 hover:border-white/20' }}
                {{ ! auth()->check() ? 'cursor-not-allowed' : '' }}">
            <img src="{{ $row['emote']->image_url }}" alt="{{ $row['emote']->name }}" class="w-4 h-4 object-contain">
            <span>{{ $row['count'] }}</span>
        </button>
    @endforeach

    @auth
        <div class="relative">
            <button type="button" wire:click="togglePicker"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition">
                @svg('fas-face-smile', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
            </button>

            <div x-show="$wire.pickerOpen" x-cloak x-transition class="absolute z-20 mt-2 left-0" @click.stop>
                <livewire:emote-picker :event-name="'emote-selected-'.$barId" :key="'emote-picker-'.$barId" />
            </div>
        </div>
    @else
        <a href="{{ route('login') }}"
           class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition"
           title="{{ __('reactions.login_required') }}">
            @svg('fas-face-smile', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
        </a>
    @endauth
</div>
