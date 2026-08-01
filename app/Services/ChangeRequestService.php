<?php

/**
 * GC-Stats — Change request service
 *
 * Creates and resolves ChangeRequest proposals against any polymorphic
 * subject (team, player, tournament, ...). Each ChangeRequestItem (one
 * field) is accepted or rejected independently; the parent ChangeRequest's
 * status is always derived from its items rather than set directly, so it
 * can never drift out of sync (see refreshStatus()). Accepting an item
 * immediately tries to apply it via ChangeRequestApplierRegistry — a failed
 * apply does not roll back the accept decision, it's recorded on the item
 * (apply_error) for an admin to retry or resolve manually.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestMessage;
use App\Models\User;
use App\Services\ChangeRequests\ChangeRequestApplierRegistry;
use App\Services\ChangeRequests\RejectableFieldApplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class ChangeRequestService
{
    public function __construct(private readonly ChangeRequestApplierRegistry $appliers) {}

    /**
     * @param  array<int, array{field: string, old_value?: mixed, new_value: mixed}>  $items
     */
    public function create(Model $subject, ?User $requestedBy, ?string $reason, array $items): ChangeRequest
    {
        return DB::transaction(function () use ($subject, $requestedBy, $reason, $items) {
            $request = ChangeRequest::create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'requested_by' => $requestedBy?->id,
                'reason' => $reason,
                'status' => ChangeRequest::STATUS_PENDING,
            ]);

            foreach ($items as $item) {
                $request->items()->create([
                    'field' => $item['field'],
                    'old_value' => $item['old_value'] ?? null,
                    'new_value' => $item['new_value'],
                ]);
            }

            activity('change_request')
                ->performedOn($request)
                ->causedBy($requestedBy)
                ->withProperties([
                    'subject_type' => $request->subject_type,
                    'subject_id' => $request->subject_id,
                    'fields' => array_column($items, 'field'),
                    'system_generated' => $requestedBy === null,
                ])
                ->log('change_request.submitted');

            return $request;
        });
    }

    public function acceptItem(ChangeRequestItem $item, User $moderator, ?string $note = null): void
    {
        $this->resolveItem($item, ChangeRequestItem::STATUS_ACCEPTED, $moderator, $note, 'change_request.item_accepted');
        $this->applyItem($item);

        $this->addSystemMessage($item->changeRequest, "Item #{$item->id} ({$item->field}) accepted by {$moderator->name}".($note ? ": {$note}" : '.'));
        $this->refreshStatus($item->changeRequest->fresh('items'), $moderator);
    }

    public function rejectItem(ChangeRequestItem $item, User $moderator, ?string $note = null): void
    {
        $this->resolveItem($item, ChangeRequestItem::STATUS_REJECTED, $moderator, $note, 'change_request.item_rejected');
        $this->cleanupRejectedItem($item);

        $this->addSystemMessage($item->changeRequest, "Item #{$item->id} ({$item->field}) rejected by {$moderator->name}".($note ? ": {$note}" : '.'));
        $this->refreshStatus($item->changeRequest->fresh('items'), $moderator);
    }

    /**
     * Best-effort: give a RejectableFieldApplier a chance to undo whatever
     * side effect its field's proposal already caused (e.g. delete an
     * uploaded photo's files). A missing subject/applier, or the cleanup
     * itself failing, must never block the rejection from going through.
     */
    private function cleanupRejectedItem(ChangeRequestItem $item): void
    {
        $subject = $item->changeRequest->subject;

        if (! $subject) {
            return;
        }

        try {
            $applier = $this->appliers->resolve(get_class($subject), $item->field);

            if ($applier instanceof RejectableFieldApplier) {
                $applier->onReject($item->new_value);
            }
        } catch (Throwable) {
            // No applier registered, or cleanup failed — nothing to do here.
        }
    }

    public function withdraw(ChangeRequest $request, ?User $actor, ?string $note = null): void
    {
        $request->update([
            'status' => ChangeRequest::STATUS_WITHDRAWN,
            'closed_by' => $actor?->id,
            'closed_at' => now(),
        ]);

        $request->items()->where('status', ChangeRequestItem::STATUS_PENDING)->get()->each(function (ChangeRequestItem $item) use ($actor, $note) {
            $item->update([
                'status' => ChangeRequestItem::STATUS_REJECTED,
                'resolved_by' => $actor?->id,
                'resolved_at' => now(),
                'resolution_note' => $note ?? 'Withdrawn.',
            ]);
        });

        activity('change_request')
            ->performedOn($request)
            ->causedBy($actor)
            ->log('change_request.withdrawn');
    }

    public function addMessage(ChangeRequest $request, ?User $author, string $body, string $type = ChangeRequestMessage::TYPE_COMMENT): ChangeRequestMessage
    {
        return $request->messages()->create([
            'user_id' => $author?->id,
            'type' => $type,
            'body' => $body,
        ]);
    }

    private function addSystemMessage(ChangeRequest $request, string $body): void
    {
        $this->addMessage($request, null, $body, ChangeRequestMessage::TYPE_SYSTEM);
    }

    private function resolveItem(ChangeRequestItem $item, string $status, User $moderator, ?string $note, string $activityEvent): void
    {
        $item->update([
            'status' => $status,
            'resolved_by' => $moderator->id,
            'resolved_at' => now(),
            'resolution_note' => $note,
        ]);

        activity('change_request')
            ->performedOn($item->changeRequest)
            ->causedBy($moderator)
            ->withProperties(['item_id' => $item->id, 'field' => $item->field, 'status' => $status])
            ->log($activityEvent);
    }

    private function applyItem(ChangeRequestItem $item): void
    {
        $request = $item->changeRequest;
        $subject = $request->subject;

        if (! $subject) {
            $item->update(['apply_error' => 'Subject no longer exists.']);

            return;
        }

        try {
            $applier = $this->appliers->resolve(get_class($subject), $item->field);
            $applier->apply($subject, $item->new_value, $item);
            $item->update(['applied_at' => now(), 'apply_error' => null]);
        } catch (Throwable $e) {
            $item->update(['apply_error' => $e->getMessage()]);
        }
    }

    /**
     * Derive the parent request's status from its items' statuses. Called
     * after every item resolution so change_requests.status can never drift
     * from what its items actually say.
     */
    public function refreshStatus(ChangeRequest $request, ?User $actor = null): void
    {
        $items = $request->items;

        if ($items->isEmpty()) {
            return;
        }

        $allAccepted = $items->every(fn (ChangeRequestItem $i) => $i->status === ChangeRequestItem::STATUS_ACCEPTED);
        $allRejected = $items->every(fn (ChangeRequestItem $i) => $i->status === ChangeRequestItem::STATUS_REJECTED);
        $anyPending = $items->contains(fn (ChangeRequestItem $i) => $i->status === ChangeRequestItem::STATUS_PENDING);

        $status = match (true) {
            $allAccepted => ChangeRequest::STATUS_ACCEPTED,
            $allRejected => ChangeRequest::STATUS_REJECTED,
            $anyPending => ChangeRequest::STATUS_PENDING,
            default => ChangeRequest::STATUS_PARTIALLY_ACCEPTED,
        };

        $isClosed = in_array($status, [ChangeRequest::STATUS_ACCEPTED, ChangeRequest::STATUS_REJECTED], true);

        $request->update([
            'status' => $status,
            'closed_by' => $isClosed ? $actor?->id : null,
            'closed_at' => $isClosed ? now() : null,
        ]);
    }
}
