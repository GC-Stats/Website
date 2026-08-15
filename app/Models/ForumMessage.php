<?php

/**
 * GC-Stats — Forum message model
 *
 * A single post within a ForumThread. Soft-deleted (not hard-deleted) so
 * moderation can remove a message from view while keeping the row for the
 * audit trail — see App\Services\ForumService::deleteMessage(). Reactable
 * via the same trait/table News uses (see App\Models\Concerns\HasReactions)
 * rather than a duplicated reaction system.
 *
 * Posting is never blocked by auto-moderation — a message that trips the
 * OpenAI moderation check (see App\Jobs\ModerateForumMessage) is created
 * normally, then immediately hidden (hidden_at set) until a moderator
 * reviews the resulting App\Models\ModerationSuspect row and either
 * approves it (unhides) or confirms it (stays hidden).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Models\Concerns\HasReactions;
use App\Services\MatchStatsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForumMessage extends Model
{
    use HasReactions, SoftDeletes;

    private const EMBED_TYPES = ['player', 'team', 'match'];

    /**
     * Player/team embeds offer 'header'/'stats' (see resolveEmbed()'s
     * docblock). The other four are match-only — 'player' is one roster
     * member's stat line for that specific match (not the career-average
     * 'stats' variant player/team use), 'scoreboard'/'performance'/
     * 'economy' are match-total (all maps) summaries — see
     * resolveMatchEmbedData() and App\Services\MatchStatsService.
     */
    private const EMBED_VARIANTS = ['header', 'stats', 'player', 'scoreboard', 'performance', 'economy'];

    protected $fillable = [
        'thread_id',
        'user_id',
        'parent_id',
        'body',
        'hidden_at',
    ];

    protected $casts = [
        'hidden_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

    /**
     * The message body split into renderable segments: plain-text chunks
     * (HTML-escaped, with `:emote_name:` shortcodes swapped for inline
     * `<img>` tags — same catalog as the reaction picker, see
     * App\Models\Emote::active()) and `{{type:id}}` embed references —
     * Discord-style typed emotes plus the "instead of a screenshot, link
     * the match/player/team" embeds, see resources/views/components/forum/
     * embed-card.blade.php for how each segment renders. Embeds are only
     * ever inserted via the composer's picker (see
     * resources/views/livewire/forum-thread.blade.php), never hand-typed,
     * so a reference to a since-deleted entity is the only way one goes
     * missing — that renders as a small "no longer available" note instead
     * of silently vanishing.
     *
     * A "stats" embed can carry an optional `?agent=..&period=30d` /
     * `?from=..&to=..` / `?tournament=123` query suffix (any combination —
     * see resolveEmbedStats()) narrowing the summary, e.g.
     * `{{player:42:stats?agent=Jett&tournament=7}}`. Only ever produced by
     * the composer's picker (never hand-typed), so values are already
     * validated/urlencoded there.
     *
     * A `{{gif:https://media*.giphy.com/...}}` token (see
     * resources/views/livewire/forum-gif-picker.blade.php, the only
     * producer) renders as a plain image — the raw CDN URL is stored
     * as-is (no numeric id, unlike the entity embeds), gated to Giphy's
     * media hosts at render time (isAllowedGifHost()) so a hand-edited
     * request can't turn this into an open image-hotlink/XSS vector.
     *
     * @return list<array{type: 'text', html: string}|array{type: 'embed', entity_type: string, variant: string, model: Model, stats: ?array, filters: ?array, match_data: ?array}|array{type: 'gif', url: string}|array{type: 'missing'}>
     */
    public function parseBody(): array
    {
        $pattern = '/\{\{(?:('.implode('|', self::EMBED_TYPES).'):(\d+)(?::('.implode('|', self::EMBED_VARIANTS).'))?(?:\?([^}]*))?|gif:(https:\/\/[^}\s]+))\}\}/';

        // preg_split (the more obvious tool here) can't be used: with an
        // optional capture group like the variant one, PHP simply omits
        // that slot from the split result whenever it didn't participate in
        // a given match, instead of a stable placeholder — so the array
        // doesn't split into fixed-size chunks per match, only "3 or 4
        // depending". Walking preg_match_all's offsets instead sidesteps
        // that entirely.
        preg_match_all($pattern, $this->body, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $segments = [];
        $cursor = 0;

        foreach ($matches as $match) {
            [$fullMatch, $matchOffset] = $match[0];

            if ($matchOffset > $cursor) {
                $segments[] = ['type' => 'text', 'html' => $this->renderTextSegment(substr($this->body, $cursor, $matchOffset - $cursor))];
            }

            // A single bad token (a query failure resolving one embed's
            // stats, say) must never take down every other message/segment
            // in the thread with it — degrade that one token to 'missing'
            // and move on. Render-time failures (a bug in embed-card.blade.php
            // itself) are a separate concern, guarded at the render call
            // site instead — see resources/views/components/forum/
            // safe-embed-card.blade.php.
            try {
                $segments[] = $this->resolveSegment($match);
            } catch (\Throwable $e) {
                report($e);
                $segments[] = ['type' => 'missing'];
            }

            $cursor = $matchOffset + strlen($fullMatch);
        }

        if ($cursor < strlen($this->body)) {
            $segments[] = ['type' => 'text', 'html' => $this->renderTextSegment(substr($this->body, $cursor))];
        }

        return $segments;
    }

    /**
     * Resolves one `{{...}}` regex match (see parseBody(), the only
     * caller) into its segment — a 'gif' segment, a 'missing' marker (host
     * not allowlisted / referenced model gone / match-data variant
     * couldn't resolve), or a full 'embed' segment. Split out from
     * parseBody() purely so that loop can wrap this single call in a
     * try/catch per token instead of the whole match-handling block.
     *
     * @return array{type: 'gif', url: string}|array{type: 'missing'}|array{type: 'embed', entity_type: string, variant: string, model: Model, stats: ?array, filters: ?array, match_data: ?array}
     */
    private function resolveSegment(array $match): array
    {
        $gifUrl = $match[5][0] ?? '';

        if ($gifUrl !== '') {
            return self::isAllowedGifHost($gifUrl) ? ['type' => 'gif', 'url' => $gifUrl] : ['type' => 'missing'];
        }

        $entityType = $match[1][0];
        $id = (int) $match[2][0];
        $variant = ($match[3][0] ?? '') ?: 'header';

        $model = $this->resolveEmbed($entityType, $id);

        if (! $model) {
            return ['type' => 'missing'];
        }

        $isMatchDataVariant = $entityType === 'match' && $variant !== 'header';

        $filters = [];
        if (($variant === 'stats' || $isMatchDataVariant) && ($match[4][0] ?? '') !== '') {
            parse_str($match[4][0], $filters);
        }

        $matchData = $isMatchDataVariant ? $this->resolveMatchEmbedData($variant, $model, $filters) : null;

        if ($isMatchDataVariant && $matchData === null) {
            return ['type' => 'missing'];
        }

        return [
            'type' => 'embed',
            'entity_type' => $entityType,
            'variant' => $variant,
            'model' => $model,
            'stats' => $variant === 'stats' ? $this->resolveEmbedStats($entityType, $id, $filters) : null,
            'filters' => $variant === 'stats' ? $this->describeEmbedFilters($filters) : null,
            'match_data' => $matchData,
        ];
    }

    /**
     * Data for a match embed's non-'header' variants — "link the scoreboard/
     * perf/eco summary instead of a screenshot". Null means "render as
     * missing" (parseBody() treats it the same as a since-deleted subject):
     * for 'player', that's an invalid/foreign player id (never hand-typed,
     * see class docblock, but a since-transferred player is a legitimate
     * way to hit this); 'scoreboard'/'performance'/'economy' can't fail
     * this way — App\Services\MatchStatsService::aggregateFor() always
     * returns an array, possibly empty if the match has no stats yet.
     *
     * @return ?array{player?: array, stats_a?: list<array>, stats_b?: list<array>, performance?: array, eco_summary?: array}
     */
    private function resolveMatchEmbedData(string $variant, Matchs $match, array $filters): ?array
    {
        $stats = app(MatchStatsService::class);

        if ($variant === 'player') {
            $playerId = (int) ($filters['player'] ?? 0);

            if ($playerId <= 0) {
                return null;
            }

            $line = $stats->playerStatsFor($match, $playerId);

            return $line ? ['player' => $line] : null;
        }

        $aggregate = $stats->aggregateFor($match);

        return match ($variant) {
            'scoreboard' => ['stats_a' => $aggregate['stats_a'], 'stats_b' => $aggregate['stats_b']],
            'performance' => ['performance' => $aggregate['performance'], 'stats_a' => $aggregate['stats_a'], 'stats_b' => $aggregate['stats_b']],
            'economy' => ['eco_summary' => $aggregate['eco_summary']],
            default => null,
        };
    }

    /**
     * Host allowlist for `{{gif:...}}` tokens — only Giphy's own media CDN
     * (media0-4.giphy.com and bare media.giphy.com), never an arbitrary
     * URL. The composer (forum-gif-picker) only ever inserts a URL it got
     * straight from Giphy's API response, so this only matters against a
     * hand-edited request.
     */
    private static function isAllowedGifHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && (bool) preg_match('/^media\d*\.giphy\.com$/', $host);
    }

    private function resolveEmbed(string $type, int $id): ?Model
    {
        return match ($type) {
            'player' => Player::with('teams')->find($id),
            'team' => Team::find($id),
            'match' => Matchs::with(['teamA', 'teamB', 'tournament', 'tournamentPhase'])->find($id),
            default => null,
        };
    }

    /**
     * Compact stats for the "stats" embed variant — a coarser summary than
     * the full per-agent/per-map tables the player/team stats pages show
     * (GamePlayerStat has no pre-aggregated "career totals" row of its own,
     * see PlayerController::stats()), computed fresh here since nothing
     * else in the app needs this specific shape. $filters narrows it:
     * 'agent' (player only — game_player_stats.agent_name), 'tournament'
     * (an id — both tables carry tournament_id directly, no join needed),
     * and a date range, either a 'period' preset ('7d'/'30d'/'90d') or an
     * explicit 'from'/'to' — see resolveDateRange(). All independent and
     * combinable; an empty/missing filter is simply not applied.
     */
    private function resolveEmbedStats(string $type, int $id, array $filters): ?array
    {
        [$from, $to] = $this->resolveDateRange($filters);

        if ($type === 'player') {
            $row = (array) DB::table('game_player_stats')
                ->join('matches', 'matches.id', '=', 'game_player_stats.match_id')
                ->where('game_player_stats.player_id', $id)
                ->when(! empty($filters['agent']), fn ($q) => $q->where('game_player_stats.agent_name', $filters['agent']))
                ->when(! empty($filters['tournament']), fn ($q) => $q->where('game_player_stats.tournament_id', (int) $filters['tournament']))
                ->when($from, fn ($q) => $q->whereDate('matches.scheduled_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('matches.scheduled_at', '<=', $to))
                ->selectRaw('COUNT(*) as games_played, ROUND(AVG(acs),1) as avg_acs, ROUND(AVG(kills),1) as avg_kills,
                    ROUND(AVG(deaths),1) as avg_deaths, ROUND(AVG(assists),1) as avg_assists, ROUND(AVG(adr),1) as avg_adr,
                    ROUND(AVG(kast_percentage),1) as avg_kast, ROUND(AVG(headshot_percentage),1) as avg_hs')
                ->first();

            return $row;
        }

        if ($type === 'team') {
            $matches = Matchs::where(fn ($q) => $q->where('team_a_id', $id)->orWhere('team_b_id', $id))
                ->where('status', 'finished')
                ->when(! empty($filters['tournament']), fn ($q) => $q->where('tournament_id', (int) $filters['tournament']))
                ->when($from, fn ($q) => $q->whereDate('scheduled_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('scheduled_at', '<=', $to))
                ->get();

            $wins = $matches->filter(fn (Matchs $match) => $match->getResultForTeam($id) === 'win')->count();
            $total = $matches->count();

            return [
                'matches_played' => $total,
                'wins' => $wins,
                'losses' => $total - $wins,
                'win_rate' => $total > 0 ? round($wins / $total * 100, 1) : null,
            ];
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string} [from, to] as Y-m-d strings, or
     *                                       [null, null] if unfiltered
     */
    private function resolveDateRange(array $filters): array
    {
        if (! empty($filters['from']) || ! empty($filters['to'])) {
            return [$filters['from'] ?? null, $filters['to'] ?? null];
        }

        $days = match ($filters['period'] ?? null) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => null,
        };

        return $days ? [now()->subDays($days)->toDateString(), null] : [null, null];
    }

    /**
     * Human-readable summary of the active filters for the embed card's
     * subtitle line (e.g. "Jett · Last 30 days · VCT Game Changers") — a
     * tournament filter needs a name lookup since only its id is stored in
     * the shortcode.
     *
     * @return array{agent: ?string, period: ?string, tournament: ?string}
     */
    private function describeEmbedFilters(array $filters): array
    {
        [$from, $to] = $this->resolveDateRange($filters);

        $period = null;
        if ($from && $to) {
            $period = "{$from} – {$to}";
        } elseif ($from) {
            $period = __('forum.embed.period.since', ['date' => $from]);
        } elseif (! empty($filters['period'])) {
            $period = __('forum.embed.period.'.$filters['period']);
        }

        return [
            'agent' => $filters['agent'] ?? null,
            'period' => $period,
            'tournament' => ! empty($filters['tournament']) ? Tournament::find((int) $filters['tournament'])?->name : null,
        ];
    }

    private function renderTextSegment(string $text): string
    {
        $escaped = e($text);

        $emotesByName = Emote::active()->keyBy(fn (Emote $emote) => Str::lower($emote->name));

        return preg_replace_callback('/:([a-z0-9_\-]+):/i', function (array $matches) use ($emotesByName) {
            $emote = $emotesByName->get(Str::lower($matches[1]));

            if (! $emote) {
                return $matches[0];
            }

            return '<img src="'.e($emote->image_url).'" alt="'.e($emote->name).'" title="'.e($emote->name).'" class="inline-block w-5 h-5 align-text-bottom object-contain">';
        }, $escaped);
    }
}
