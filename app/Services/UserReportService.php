<?php

/**
 * GC-Stats — User report service
 *
 * Lets any authenticated user flag another account as suspicious
 * ("déclarer un utilisateur suspect"), queued for moderation. Volume
 * rate-limiting is handled declaratively by the `throttle:` middleware on
 * the `users.report` route (see routes/auth.php), matching this app's
 * existing throttle convention rather than a bespoke RateLimiter here —
 * this service only rejects the one thing a route-level throttle can't:
 * a user reporting themselves.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\CannotReportUserException;
use App\Models\Emote;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Model;

class UserReportService
{
    /**
     * @param  array{category: string, reason: string, team_id?: ?int}  $data
     *
     * @throws CannotReportUserException
     */
    public function submit(User $reporter, User $reportedUser, array $data): UserReport
    {
        if ($reporter->id === $reportedUser->id) {
            throw new CannotReportUserException;
        }

        $report = UserReport::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reportedUser->id,
            'team_id' => $data['team_id'] ?? null,
            'category' => $data['category'],
            'reason' => $data['reason'],
        ]);

        activity('moderation')
            ->performedOn($report)
            ->causedBy($reporter)
            ->withProperties(['reported_user_id' => $reportedUser->id, 'category' => $data['category']])
            ->log('report.submitted');

        return $report;
    }

    /**
     * Flag an emote reaction as inappropriate. Unlike submit(), this never
     * targets one user — it concerns every current reactor of that emote —
     * so repeated reports of the same still-open reaction reuse the
     * existing row instead of piling up duplicates (see
     * UserReport::reactingUsers(), computed live rather than snapshotted).
     *
     * @param  array{category: string, reason: string}  $data
     */
    public function submitForReaction(User $reporter, Model $reactable, Emote $emote, array $data): UserReport
    {
        $existing = UserReport::query()
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->where('emote_id', $emote->id)
            ->whereIn('status', [UserReport::STATUS_PENDING, UserReport::STATUS_REVIEWING])
            ->first();

        if ($existing) {
            return $existing;
        }

        $report = UserReport::create([
            'reporter_id' => $reporter->id,
            'reactable_type' => $reactable->getMorphClass(),
            'reactable_id' => $reactable->getKey(),
            'emote_id' => $emote->id,
            'category' => $data['category'],
            'reason' => $data['reason'],
        ]);

        activity('moderation')
            ->performedOn($report)
            ->causedBy($reporter)
            ->withProperties(['emote_id' => $emote->id, 'category' => $data['category']])
            ->log('report.submitted');

        return $report;
    }

    public function resolve(UserReport $report, User $moderator, string $status, ?string $note = null): void
    {
        $report->update([
            'status' => $status,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
            'resolution_note' => $note,
        ]);

        activity('moderation')
            ->performedOn($report)
            ->causedBy($moderator)
            ->withProperties(['status' => $status])
            ->log('report.resolved');
    }
}
