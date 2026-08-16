<?php

/**
 * GC-Stats — Forum thread Livewire component
 *
 * Renders a forum thread and its messages (paginated, oldest first), and
 * lets a signed-in user post new ones — including emotes from the same
 * catalog as the reaction picker (see App\Models\Emote), either picked from
 * the icon or typed inline as `:name:`, which is replaced by the actual
 * emote image live as you type (see the composer's Alpine logic below) —
 * ForumMessage::parseBody() does the same substitution server-side for
 * stored messages, plus renders `{{type:id}}` embeds (player/team/match —
 * the "link a match instead of a screenshot" feature, inserted via the
 * composer's second picker button) as cards, see
 * resources/views/components/forum/embed-card.blade.php. Always embedded
 * in place (subject pages or the thread page) — never links out to a
 * separate "full thread" view.
 *
 * Two ways to mount it:
 *  - `subject-type`/`subject-id` (Tournament, Matchs, News): the thread is
 *    found-or-created lazily via App\Services\ForumService — embed this on
 *    a subject's show page (see resources/views/public/news/show.blade.php).
 *  - `thread-id` directly: used by the "general" thread page
 *    (resources/views/public/forum/threads/show.blade.php), which has no
 *    subject to resolve from.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Exceptions\CannotReportUserException;
use App\Models\ForumMessage;
use App\Models\ForumThread;
use App\Models\Matchs;
use App\Models\News;
use App\Models\Tournament;
use App\Models\UserReport;
use App\Services\ForumService;
use App\Services\UserReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    private const ALLOWED_SUBJECTS = [Tournament::class, Matchs::class, News::class];

    public int $threadId;

    public string $barId;

    public string $body = '';

    /** @var array<int, bool> */
    public array $reportSubmittedFor = [];

    public bool $emotePickerLoaded = false;

    public bool $embedPickerLoaded = false;

    public bool $gifPickerLoaded = false;

    public function mount(?string $subjectType = null, int|string|null $subjectId = null, ?int $threadId = null): void
    {
        if ($threadId !== null) {
            $thread = ForumThread::findOrFail($threadId);
        } else {
            abort_unless($subjectType !== null && $subjectId !== null, 404);
            abort_unless(in_array($subjectType, self::ALLOWED_SUBJECTS, true), 404);

            $subject = $subjectType::findOrFail($subjectId);
            $thread = app(ForumService::class)->findOrCreateThreadFor($subject);
        }

        $this->threadId = $thread->id;
        $this->barId = 'forum-thread-'.$thread->id;
    }

    private function threadCacheKey(): string
    {
        return "forum:thread:{$this->threadId}";
    }

    /**
     * with() re-runs on every Livewire action on this component — not just
     * posting/paginating, but opening the emote/embed/gif picker too (each
     * flips a plain boolean via its own action, which still triggers a full
     * re-render). That was re-fetching the thread row fresh every time,
     * right on top of the identical findOrFail() mount() just did. The
     * thread rarely changes (only last_message_at, on a new post), so a
     * short cache absorbs all of that — forgotten immediately after
     * postMessage() so a new message's own render sees it fresh.
     */
    private function cachedThread(): ForumThread
    {
        $thread = Cache::remember($this->threadCacheKey(), now()->addSeconds(30), fn () => ForumThread::findOrFail($this->threadId));

        // Same guard as App\Models\Emote's cached accessors: a cache entry
        // written before a deploy can unserialize as __PHP_Incomplete_Class
        // if the model definition shifted underneath it since.
        if (! $thread instanceof ForumThread) {
            Cache::forget($this->threadCacheKey());

            return ForumThread::findOrFail($this->threadId);
        }

        return $thread;
    }

    private function postLimiterKey(): string
    {
        return "forum-post:{$this->threadId}:".Auth::id();
    }

    private function tooManyPosts(): bool
    {
        return RateLimiter::tooManyAttempts($this->postLimiterKey(), 5);
    }

    public function loadEmotePicker(): void
    {
        $this->emotePickerLoaded = true;
    }

    public function loadEmbedPicker(): void
    {
        $this->embedPickerLoaded = true;
    }

    public function loadGifPicker(): void
    {
        $this->gifPickerLoaded = true;
    }

    public function acceptRules(): void
    {
        abort_unless(Auth::check(), 403);

        Auth::user()->acceptForumRules();
    }

    public function with(): array
    {
        $thread = $this->cachedThread();

        return [
            'thread' => $thread,
            'messages' => $thread->messages()->visible()->with(['user.team'])->oldest()->paginate(20, pageName: 'forum-page-'.$this->threadId),
            'blockingSanction' => Auth::user()?->activeGlobalBlockingSanction(),
            'muteSanction' => Auth::user()?->activeGlobalMuteSanction(),
            'rulesAccepted' => Auth::user()?->hasAcceptedForumRules() ?? true,
            // NOTE: the composer's `:name:` shortcode catalog (name/id keyed
            // maps of every active emote) used to be built and embedded here
            // on every render. With ~4,000 emotes imported from Twemoji, that
            // was several hundred KB of JSON re-sent by Livewire on every
            // single action on this component (posting, paginating, even
            // just opening a picker) — regardless of the thread having any
            // messages at all. It's now fetched once client-side from a
            // static JSON route instead — see the composer's x-init below
            // and App\Http\Controllers\Public\EmoteCatalogController.
        ];
    }

    public function postMessage(): void
    {
        abort_unless(Auth::check(), 403);
        abort_if(Auth::user()->activeGlobalBlockingSanction(), 403);
        abort_if(Auth::user()->activeGlobalMuteSanction(), 403);
        abort_unless(Auth::user()->hasAcceptedForumRules(), 403);

        if ($this->tooManyPosts()) {
            $this->addError('body', __('forum.errors.too_many_messages'));

            return;
        }

        $this->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        RateLimiter::hit($this->postLimiterKey(), 30);

        $thread = ForumThread::findOrFail($this->threadId);

        app(ForumService::class)->postMessage($thread, Auth::user(), $this->body);

        Cache::forget($this->threadCacheKey());

        $this->reset('body');

        // Jump to the page the new message landed on, so posting always
        // shows it immediately instead of leaving the user on an earlier page.
        $lastPage = max((int) ceil($thread->messages()->visible()->count() / 20), 1);
        $this->setPage($lastPage, pageName: 'forum-page-'.$this->threadId);
    }

    /**
     * Flag a single message as inappropriate — unlike reaction-bar's report
     * (which concerns every reactor of a flagged emote), this targets one
     * specific message/author. Same 15-per-hour budget as the reaction
     * report and the users.report route, keyed per-user across both message
     * and reaction reports would be ideal but isn't worth a shared limiter
     * yet — kept separate for now.
     *
     * category/reason arrive as action parameters (read from Alpine state
     * scoped to that one message's report form, see the blade markup below)
     * rather than through wire:model-bound public properties — with one
     * report form rendered per message on the page, every one of them would
     * otherwise share the same reportCategory/reportReason property names,
     * and Livewire has no way to know which of the many identically-bound
     * DOM inputs is "the" current value when the request is sent (in
     * practice: it can end up reading an empty, unrelated, closed form's
     * input instead of the one actually filled in). Passing the values
     * explicitly sidesteps that entirely.
     */
    public function submitMessageReport(int $messageId, string $category, string $reason): void
    {
        abort_unless(Auth::check(), 403);
        abort_if(Auth::user()->activeGlobalBlockingSanction(), 403);

        $limiterKey = 'message-report:'.Auth::id();

        if (RateLimiter::tooManyAttempts($limiterKey, 15)) {
            $this->addError('reportReason', __('forum.report.too_many_attempts'));

            return;
        }

        $validated = validator(
            ['reportCategory' => $category, 'reportReason' => $reason],
            [
                'reportCategory' => ['required', 'string', Rule::in(UserReport::CATEGORIES)],
                'reportReason' => ['required', 'string', 'max:2000'],
            ],
        )->validate();

        RateLimiter::hit($limiterKey, 3600);

        $message = ForumMessage::findOrFail($messageId);

        try {
            app(UserReportService::class)->submitForMessage(Auth::user(), $message, [
                'category' => $validated['reportCategory'],
                'reason' => $validated['reportReason'],
            ]);
        } catch (CannotReportUserException $e) {
            $this->addError('reportReason', $e->getMessage());

            return;
        }

        $this->reportSubmittedFor[$messageId] = true;
    }
}; ?>

<div class="space-y-4">
    <div class="space-y-3">
        @forelse ($messages as $message)
            <div class="relative bg-white/[0.03] border border-white/[0.06] rounded-lg p-3">
                @auth
                    @if ($message->user_id !== auth()->id())
                        <div class="absolute top-2 right-2" x-data="{ reportCategory: '', reportReason: '' }">
                            <x-modal :title="__('forum.report.title')" max-width="max-w-md">
                                <x-slot:trigger>
                                    <button type="button"
                                            class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-widest text-red-400 hover:text-red-300 transition">
                                        @svg('fas-flag', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])
                                        {{ __('forum.report.trigger') }}
                                    </button>
                                </x-slot:trigger>

                                @if ($reportSubmittedFor[$message->id] ?? false)
                                    <p class="text-sm text-green-400">{{ __('forum.report.thanks') }}</p>
                                @else
                                    {{-- Every message on the page renders its own copy of this form, so
                                         the fields are bound to Alpine state scoped to this one modal
                                         (x-model) instead of Livewire properties (wire:model) — see
                                         submitMessageReport()'s docblock for why sharing property names
                                         across many identical forms silently breaks. Values are passed
                                         straight to the action call instead. --}}
                                    <form @submit.prevent="$wire.submitMessageReport({{ $message->id }}, reportCategory, reportReason)" class="space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('forum.report.category_label') }}</label>
                                            <x-styled-select x-model="reportCategory"
                                                :options="collect(['' => ''])->merge(collect(\App\Models\UserReport::CATEGORIES)->mapWithKeys(fn ($category) => [$category => __('admin.reports.category.'.$category)]))" />
                                            @error('reportCategory')
                                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('forum.report.reason_label') }}</label>
                                            <textarea x-model="reportReason" rows="3" required
                                                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
                                            @error('reportReason')
                                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <button type="submit"
                                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                                            {{ __('forum.report.submit') }}
                                        </button>
                                    </form>
                                @endif
                            </x-modal>
                        </div>
                    @endif
                @endauth

                <div class="flex items-start gap-3">
                <img src="{{ $message->user?->gravatarUrl(32) }}" alt=""
                     class="w-8 h-8 rounded-full object-cover shrink-0" onerror="this.style.visibility='hidden'">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if ($message->user)
                            <a href="{{ route('users.show', $message->user->username) }}" class="text-xs font-bold text-white hover:text-gc-yellow transition truncate">
                                {{ $message->user->name }}
                            </a>
                            @if ($message->user->team)
                                <a href="{{ route('teams.show', [$message->user->team->id, $message->user->team->routeSlug()]) }}"
                                   class="inline-flex items-center gap-1.5 bg-white/5 border border-white/10 rounded-sm px-2 py-0.5 hover:border-gc-yellow/50 transition">
                                    <img src="{{ $message->user->team->logo }}" alt="{{ $message->user->team->name }}" class="w-3 h-3 object-contain">
                                    @if ($message->user->team_tag)
                                        <span class="text-[9px] font-black uppercase tracking-widest text-gc-yellow">
                                            {{ $message->user->team_tag }}
                                        </span>
                                    @else
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-gray-300">
                                            {{ __('user.profile.fan_of') }} {{ $message->user->team->name }}
                                        </span>
                                    @endif
                                </a>
                            @endif
                        @else
                            <span class="text-xs font-bold text-gray-500">{{ __('forum.message.deleted_user') }}</span>
                        @endif
                        <span class="text-[10px] text-gray-500">{{ $message->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="text-sm text-gray-300 whitespace-pre-line break-words space-y-1">
                        @foreach ($message->parseBody() as $segment)
                            @if ($segment['type'] === 'text')
                                <span>{!! $segment['html'] !!}</span>
                            @elseif ($segment['type'] === 'embed')
                                <x-forum.safe-embed-card :type="$segment['entity_type']" :model="$segment['model']" :variant="$segment['variant']" :stats="$segment['stats']" :filters="$segment['filters']" :match-data="$segment['match_data']" />
                            @elseif ($segment['type'] === 'gif')
                                <img src="{{ $segment['url'] }}" alt="GIF" loading="lazy" class="not-prose block max-w-[16rem] max-h-[16rem] rounded-lg my-1 object-contain">
                            @else
                                <span class="text-xs text-gray-600 italic">{{ __('forum.embed.missing') }}</span>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-2">
                        <livewire:reaction-bar lazy :reactable-type="\App\Models\ForumMessage::class" :reactable-id="$message->id" :key="'forum-reaction-'.$message->id" />
                    </div>
                </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 italic">{{ __('forum.thread.empty') }}</p>
        @endforelse
    </div>

    <div>
        {{ $messages->links() }}
    </div>

    @auth
        @if ($blockingSanction)
            <p class="text-xs text-gray-500">{{ __('account.errors.sanctioned_global', ['reason' => $blockingSanction->reason]) }}</p>
        @elseif ($muteSanction)
            <p class="text-xs text-gray-500">{{ __('forum.errors.muted', ['until' => $muteSanction->ends_at?->format('Y-m-d H:i')]) }}</p>
        @else
            {{-- submit() awaits $wire.postMessage()'s promise and only clears
                 the composer once $wire.body comes back empty (i.e. the post
                 actually succeeded server-side) — simpler and more reliable
                 than the previously-dispatched 'message-posted' event, which
                 never consistently reached this window listener.

                 Careful with comments inside the x-data string below: it's a
                 double-quoted HTML attribute, so a literal " anywhere in a //
                 comment (even mid-sentence) truncates the whole attribute and
                 silently breaks every button in this composer — happened once
                 already. Put any explanation up here instead. --}}
            <div
                x-data="{
                    // Fetched from a static, browser-cached JSON route (see
                    // App\Http\Controllers\Public\EmoteCatalogController)
                    // instead of being embedded here by Livewire on every
                    // render — Livewire re-sends a component's full state on
                    // every action (posting, paginating, opening any
                    // picker), not just the initial mount, so anything
                    // embedded in this markup was being resent constantly
                    // regardless of the thread having any messages at all.
                    // Not fetched in init() either — only once the composer
                    // is actually used (first focus, or opening the emote
                    // picker), same as the picker/embed/gif popovers below
                    // only mount once opened.
                    emotes: {},
                    emoteById: {},
                    catalogLoaded: false,
                    loadCatalog() {
                        if (this.catalogLoaded) return;
                        this.catalogLoaded = true;

                        fetch('{{ route('forum.emotes-catalog') }}')
                            .then(response => response.json())
                            .then(catalog => {
                                this.emotes = catalog.byName;
                                this.emoteById = catalog.byId;
                            })
                            .catch(() => { this.catalogLoaded = false; });
                    },
                    pickerOpen: false,
                    embedPickerOpen: false,
                    gifPickerOpen: false,
                    // Whether this user has accepted the forum rules yet
                    // (see App\Models\User::hasAcceptedForumRules()) — while
                    // false, the composer itself stays hidden behind a prompt
                    // that opens the popup (see the x-if above), so there's
                    // no message to resume once accepted — acceptRules() just
                    // reveals the composer.
                    rulesAccepted: {{ $rulesAccepted ? 'true' : 'false' }},
                    rulesPopupOpen: false,
                    rulesAcceptFailed: false,
                    // Goes through the Livewire component's own acceptRules()
                    // action rather than a raw fetch() to the forum.rules.accept
                    // route — a hand-rolled fetch needs its own CSRF token
                    // (from a Blade-rendered csrf_token(), captured once at
                    // this component's last render) which can go stale after
                    // this component re-renders for any other reason (opening
                    // a picker, an earlier post), causing accept to silently
                    // fail. $wire calls always carry Livewire's own,
                    // currently-valid token.
                    acceptRules() {
                        this.rulesAcceptFailed = false;
                        this.$wire.acceptRules().then(() => {
                            this.rulesAccepted = true;
                            this.rulesPopupOpen = false;
                        }).catch(() => {
                            this.rulesAcceptFailed = true;
                        });
                    },
                    // Last known caret position inside the composer, kept
                    // up to date while it has focus (see saveCaret()) —
                    // clicking a toolbar button/popover moves focus away
                    // from the composer, which loses the browser's own
                    // selection, so insertNode() falls back to this
                    // instead of always appending at the end.
                    savedRange: null,
                    onInput() {
                        this.replaceShortcode();
                        this.sync();
                        this.saveCaret();
                    },
                    saveCaret() {
                        const sel = window.getSelection();
                        if (sel.rangeCount && this.$refs.composer.contains(sel.anchorNode)) {
                            this.savedRange = sel.getRangeAt(0).cloneRange();
                        }
                    },
                    // Inserts a node at the last known caret position
                    // (falling back to the end if there isn't one, or it's
                    // no longer inside the composer — e.g. the composer
                    // was cleared since) rather than always at the end.
                    // Leaves the caret right after it — inside the node
                    // itself when it's a text node (so continued typing
                    // lands in a real text node instead of a parent/
                    // child-index boundary next to an atomic sibling) or
                    // immediately after it otherwise (chips/images are
                    // atomic, there's no landing inside them).
                    insertNode(node) {
                        this.$refs.composer.focus();
                        const sel = window.getSelection();
                        let range = this.savedRange;

                        if (! range || ! this.$refs.composer.contains(range.startContainer)) {
                            range = document.createRange();
                            range.selectNodeContents(this.$refs.composer);
                            range.collapse(false);
                        }

                        sel.removeAllRanges();
                        sel.addRange(range);
                        range.deleteContents();
                        range.insertNode(node);

                        if (node.nodeType === Node.TEXT_NODE) {
                            range.setStart(node, node.length);
                        } else {
                            range.setStartAfter(node);
                        }
                        range.collapse(true);
                        sel.removeAllRanges();
                        sel.addRange(range);
                        this.savedRange = range.cloneRange();
                    },
                    replaceShortcode() {
                        const sel = window.getSelection();
                        if (! sel.rangeCount) return;
                        const range = sel.getRangeAt(0);
                        const node = range.startContainer;
                        if (node.nodeType !== Node.TEXT_NODE) return;

                        const text = node.textContent;
                        const caret = range.startOffset;
                        const before = text.slice(0, caret);
                        const match = before.match(/:([a-z0-9_\-]+):$/i);
                        if (! match) return;

                        const url = this.emotes[match[1].toLowerCase()];
                        if (! url) return;

                        const start = caret - match[0].length;
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = match[0];
                        img.dataset.emoteName = match[1].toLowerCase();
                        img.contentEditable = 'false';
                        img.className = 'inline-block w-5 h-5 align-text-bottom object-contain';

                        const beforeText = document.createTextNode(text.slice(0, start));
                        const afterText = document.createTextNode(text.slice(caret) + '​');
                        const parent = node.parentNode;
                        parent.insertBefore(beforeText, node);
                        parent.insertBefore(img, node);
                        parent.insertBefore(afterText, node);
                        parent.removeChild(node);

                        const newRange = document.createRange();
                        newRange.setStart(afterText, 1);
                        newRange.collapse(true);
                        sel.removeAllRanges();
                        sel.addRange(newRange);
                    },
                    serialize(node) {
                        let out = '';
                        node.childNodes.forEach(n => {
                            if (n.nodeType === Node.TEXT_NODE) out += n.textContent;
                            else if (n.nodeName === 'IMG' && n.dataset.gifUrl) out += '{' + '{' + 'gif:' + n.dataset.gifUrl + '}' + '}';
                            else if (n.nodeName === 'IMG') out += ':' + n.dataset.emoteName + ':';
                            else if (n.dataset && n.dataset.embedType) out += '{' + '{' + n.dataset.embedType + ':' + n.dataset.embedId + (n.dataset.embedVariant ? ':' + n.dataset.embedVariant : '') + (n.dataset.embedQuery ? '?' + n.dataset.embedQuery : '') + '}' + '}';
                            else if (n.nodeName === 'BR') out += '\n';
                            else if (n.nodeName === 'DIV' || n.nodeName === 'P') out += (out.endsWith('\n') || out === '' ? '' : '\n') + this.serialize(n);
                            else out += this.serialize(n);
                        });
                        return out;
                    },
                    sync() {
                        this.$refs.hidden.value = this.serialize(this.$refs.composer).replace(/​/g, '');
                        this.$refs.hidden.dispatchEvent(new Event('input'));
                    },
                    insertEmote(name, url) {
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = ':' + name + ':';
                        img.dataset.emoteName = name;
                        img.contentEditable = 'false';
                        img.className = 'inline-block w-5 h-5 align-text-bottom object-contain';
                        this.insertNode(img);
                        this.insertNode(document.createTextNode(' '));
                        this.sync();
                        this.pickerOpen = false;
                    },
                    insertEmbed(type, id, label, variant, query) {
                        const chip = document.createElement('span');
                        chip.dataset.embedType = type;
                        chip.dataset.embedId = id;
                        if (variant) chip.dataset.embedVariant = variant;
                        if (query) chip.dataset.embedQuery = query;
                        chip.contentEditable = 'false';
                        chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gc-yellow/10 border border-gc-yellow/30 text-gc-yellow text-xs font-bold align-text-bottom';
                        chip.textContent = label;
                        this.insertNode(chip);
                        this.insertNode(document.createTextNode(' '));
                        this.sync();
                        this.embedPickerOpen = false;
                    },
                    insertGif(url) {
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = 'GIF';
                        img.dataset.gifUrl = url;
                        img.contentEditable = 'false';
                        img.className = 'inline-block max-w-[8rem] max-h-[8rem] align-text-bottom rounded object-contain';
                        this.insertNode(img);
                        this.insertNode(document.createTextNode(' '));
                        this.sync();
                        this.gifPickerOpen = false;
                    },
                    async submit() {
                        if (! this.rulesAccepted) {
                            this.rulesPopupOpen = true;
                            return;
                        }
                        this.sync();
                        await this.$wire.postMessage();
                        if (this.$wire.body === '') this.clear();
                    },
                    clear() {
                        this.$refs.composer.innerHTML = '';
                        this.sync();
                    },
                }"
                @emote-selected-{{ $barId }}.window="const picked = emoteById[$event.detail.emoteId]; if (picked) insertEmote(picked.name, picked.url)"
                @embed-selected-{{ $barId }}.window="insertEmbed($event.detail.type, $event.detail.id, $event.detail.label, $event.detail.variant, $event.detail.query)"
                @gif-selected-{{ $barId }}.window="insertGif($event.detail.url)"
                @click.away="pickerOpen = false; embedPickerOpen = false; gifPickerOpen = false"
                class="space-y-2"
            >
                {{-- Before the rules are accepted, the actual composer (input,
                     toolbar, submit) stays hidden entirely — swapped for a
                     single prompt that opens the popup directly, rather than
                     letting someone type a message and only finding out they
                     need to accept the rules after clicking post. --}}
                <template x-if="! rulesAccepted">
                    <button type="button" @click="rulesPopupOpen = true"
                            class="w-full text-left bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-gray-400 hover:text-white hover:border-gc-yellow/40 transition">
                        {{ __('forum.rules.prompt') }}
                    </button>
                </template>

                <template x-if="rulesAccepted">
                    <div>
                        <textarea x-ref="hidden" wire:model="body" class="hidden"></textarea>

                        <div x-ref="composer" contenteditable="true" @input="onInput" @mouseup="saveCaret" @keyup="saveCaret"
                             @focus="loadCatalog()"
                             @keydown.enter.ctrl.prevent="submit()"
                             @keydown.enter.meta.prevent="submit()"
                             data-placeholder="{{ __('forum.message.placeholder') }}"
                             class="forum-composer w-full min-h-[4.5rem] bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></div>
                        @error('body')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="text-[10px] text-gray-600">{{ __('forum.message.submit_hint') }}</p>

                        {{-- Full-viewport backdrop, shown whenever any popover is open — a more
                             robust "click outside closes it" than relying solely on @click.away,
                             which can miss clicks that land on another Livewire component's DOM
                             after it re-renders (the gif/embed pickers re-render on every
                             keystroke/step, unlike the plain emote picker). Sits below the
                             trigger buttons/popovers (both z-20) so it never blocks them. --}}
                        <div x-show="pickerOpen || embedPickerOpen || gifPickerOpen" x-cloak class="fixed inset-0 z-10"
                             @click="pickerOpen = false; embedPickerOpen = false; gifPickerOpen = false"></div>

                        <div class="flex items-center gap-2">
                            <div class="relative z-20">
                                <button type="button" @click="pickerOpen = ! pickerOpen; if (pickerOpen) { $wire.loadEmotePicker(); loadCatalog(); }"
                                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition"
                                        title="{{ __('forum.message.emote_picker') }}">
                                    @svg('fas-face-smile', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                                </button>

                                <div x-show="pickerOpen" x-cloak x-transition class="absolute z-20 mt-2 left-0" @click.stop>
                                    @if ($emotePickerLoaded)
                                        <livewire:emote-picker :event-name="'emote-selected-'.$barId" :key="'emote-picker-'.$barId" />
                                    @endif
                                </div>
                            </div>

                            <div class="relative z-20">
                                <button type="button" @click="embedPickerOpen = ! embedPickerOpen; if (embedPickerOpen) $wire.loadEmbedPicker()"
                                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition"
                                        title="{{ __('forum.embed.trigger') }}">
                                    @svg('fas-link', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                                </button>

                                <div x-show="embedPickerOpen" x-cloak x-transition class="absolute z-20 mt-2 left-0" @click.stop>
                                    @if ($embedPickerLoaded)
                                        <livewire:forum-embed-picker :event-name="'embed-selected-'.$barId" :key="'embed-picker-'.$barId" />
                                    @endif
                                </div>
                            </div>

                            <div class="relative z-20">
                                <button type="button" @click="gifPickerOpen = ! gifPickerOpen; if (gifPickerOpen) $wire.loadGifPicker()"
                                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition"
                                        title="{{ __('forum.gif.trigger') }}">
                                    @svg('fas-photo-film', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                                </button>

                                <div x-show="gifPickerOpen" x-cloak x-transition class="absolute z-20 mt-2 left-0" @click.stop>
                                    @if ($gifPickerLoaded)
                                        <livewire:forum-gif-picker :event-name="'gif-selected-'.$barId" :key="'gif-picker-'.$barId" />
                                    @endif
                                </div>
                            </div>

                            <button type="button" @click="submit"
                                    class="font-bold uppercase text-xs tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-gc-yellow/10 border border-gc-yellow/40 text-gc-yellow hover:bg-gc-yellow/20">
                                {{ __('forum.message.submit') }}
                            </button>
                        </div>
                    </div>
                </template>

                <x-forum.rules-popup open="rulesPopupOpen" onAccept="acceptRules()" error="rulesAcceptFailed" />
            </div>

            <style>
                .forum-composer:empty:before {
                    content: attr(data-placeholder);
                    color: rgb(107 114 128);
                }
            </style>
        @endif
    @else
        <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-white transition">
            {{ __('forum.login_required') }}
        </a>
    @endauth
</div>
