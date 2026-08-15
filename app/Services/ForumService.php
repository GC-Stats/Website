<?php

/**
 * GC-Stats — Forum service
 *
 * Creates/finds forum threads and posts messages into them. Threads
 * attached to a subject (Tournament, Matchs, News) are created lazily, on
 * first visit/post, rather than pre-generated — one per subject, enforced
 * by the unique index on forum_threads.subject_type/subject_id.
 *
 * Posting is never delayed or blocked by auto-moderation — every message
 * (and a general thread's title+opening post) is created immediately, then
 * checked off the request cycle by App\Jobs\ModerateForumMessage, which
 * hides it if flagged. See that job for the actual moderation logic.
 *
 * The moderation job is dispatched via dispatchAfterResponse() rather than
 * dispatch() specifically so this holds even if QUEUE_CONNECTION is `sync`
 * (as it is by default locally) or a worker isn't running — dispatch()
 * alone would run the job (including the OpenAI HTTP call, up to 5s) in
 * the same request/response cycle in that case, making every post feel
 * slow. dispatchAfterResponse() runs it after the HTTP response is already
 * on the wire, independent of queue configuration.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Jobs\ModerateForumMessage;
use App\Models\ForumMessage;
use App\Models\ForumThread;
use App\Models\Matchs;
use App\Models\News;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ForumService
{
    public function findOrCreateThreadFor(Model $subject): ForumThread
    {
        $category = $this->categoryFor($subject);

        if ($category === null) {
            throw new InvalidArgumentException('Model ['.$subject::class.'] cannot have a forum thread.');
        }

        return ForumThread::firstOrCreate(
            ['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()],
            ['category' => $category],
        );
    }

    /**
     * instanceof rather than a getMorphClass()-keyed lookup table — Tournament
     * (unlike Matchs/News) is registered in the app's morph map
     * (AppServiceProvider::boot()), so its getMorphClass() returns the short
     * alias 'tournament' instead of the FQCN. A literal-FQCN-keyed array
     * missed that silently; instanceof doesn't care either way.
     */
    private function categoryFor(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Tournament => ForumThread::CATEGORY_TOURNAMENT,
            $subject instanceof Matchs => ForumThread::CATEGORY_MATCH,
            $subject instanceof News => ForumThread::CATEGORY_NEWS,
            default => null,
        };
    }

    public function createGeneralThread(User $user, string $title, string $body): ForumThread
    {
        $thread = ForumThread::create([
            'category' => ForumThread::CATEGORY_GENERAL,
            'title' => $title,
            'created_by' => $user->id,
        ]);

        $message = $this->createMessage($thread, $user, $body, null);

        ModerateForumMessage::dispatchAfterResponse($message->id, $title.' — '.$body);

        return $thread;
    }

    public function postMessage(ForumThread $thread, User $user, string $body, ?int $parentId = null): ForumMessage
    {
        $message = $this->createMessage($thread, $user, $body, $parentId);

        ModerateForumMessage::dispatchAfterResponse($message->id, $body);

        return $message;
    }

    public function deleteMessage(ForumMessage $message): void
    {
        $message->delete();
    }

    private function createMessage(ForumThread $thread, User $user, string $body, ?int $parentId): ForumMessage
    {
        $message = $thread->messages()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        $thread->update(['last_message_at' => $message->created_at]);

        return $message;
    }
}
