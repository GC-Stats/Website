<?php

/**
 * GC-Stats — Moderate forum message job
 *
 * Runs off the request cycle (see ForumService::postMessage()/
 * createGeneralThread(), the only dispatchers) so a post is never delayed
 * by moderation — the message is already visible by the time this runs.
 * Checks the text against OpenAI's moderation endpoint
 * (App\Services\OpenAiModerationService) and, if flagged, hides the
 * message and logs a App\Models\ModerationSuspect row for the admin queue.
 *
 * Every 3rd flag against the same user (all-time, any status — see
 * MUTE_EVERY_N_FLAGS below) auto-mutes them for MUTE_HOURS via
 * App\Services\SanctionService::issueSystemMute(), so a spammer can't keep
 * posting indefinitely while staff works through the queue. Modulo rather
 * than "exactly 3" so a repeat offender gets re-muted every 3rd flag, not
 * just once ever.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Jobs;

use App\Models\ForumMessage;
use App\Models\ModerationSuspect;
use App\Services\OpenAiModerationService;
use App\Services\SanctionService;
use DateInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModerateForumMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MUTE_EVERY_N_FLAGS = 3;

    private const MUTE_HOURS = 6;

    /**
     * $checkText is passed separately from the message rather than read
     * off it — a general thread's opening post is checked against
     * title+body combined (see ForumService::createGeneralThread()), which
     * isn't reconstructable from the message row alone.
     */
    public function __construct(
        public readonly int $messageId,
        public readonly string $checkText,
    ) {}

    public function handle(OpenAiModerationService $openAi, SanctionService $sanctions): void
    {
        $message = ForumMessage::find($this->messageId);

        if (! $message) {
            return;
        }

        $result = $openAi->check($this->checkText);

        if (! $result['flagged']) {
            return;
        }

        $message->update(['hidden_at' => now()]);

        $suspect = ModerationSuspect::create([
            'subject_type' => $message->getMorphClass(),
            'subject_id' => $message->id,
            'thread_id' => $message->thread_id,
            'user_id' => $message->user_id,
            'matched_term' => implode(', ', $result['categories']),
            'body_snapshot' => $this->checkText,
        ]);

        activity('moderation')
            ->performedOn($suspect)
            ->causedBy($message->user)
            ->withProperties(['matched_term' => $suspect->matched_term])
            ->log('moderation.suspect_flagged');

        if (! $message->user) {
            return;
        }

        $flagCount = ModerationSuspect::where('user_id', $message->user_id)->count();

        if ($flagCount % self::MUTE_EVERY_N_FLAGS === 0) {
            $sanctions->issueSystemMute(
                $message->user,
                __('admin.moderation.automod_mute_reason', ['count' => $flagCount]),
                new DateInterval('PT'.self::MUTE_HOURS.'H'),
            );
        }
    }
}
