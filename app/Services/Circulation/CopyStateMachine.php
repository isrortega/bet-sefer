<?php

namespace App\Services\Circulation;

use App\Enums\CopyStatus;
use App\Exceptions\InvalidCopyTransition;
use App\Models\Copy;
use App\Models\CopyStatusTransition;
use App\Models\User;

/**
 * The single place that moves a copy between statuses. Controllers and actions
 * never call $copy->update(['status' => ...]) directly.
 *
 * The shelver's partial `copies.transition` permission is enforced here, not in
 * the UI: a shelver may only run at_reception -> in_transit -> available.
 */
final class CopyStateMachine
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'available' => ['on_loan'],
        'on_loan' => ['at_reception'],
        'at_reception' => ['in_transit', 'available'],
        'in_transit' => ['available'],
        'in_repair' => ['available'],
        'lost' => ['available'],
        'reserved' => [],
    ];

    /**
     * @param  array{
     *     loan_id?: int|null,
     *     to_location_id?: int|null,
     *     note?: string|null,
     * }  $context
     */
    public function transition(Copy $copy, CopyStatus $to, ?User $actor = null, array $context = []): Copy
    {
        $from = $copy->status instanceof CopyStatus ? $copy->status : CopyStatus::from((string) $copy->status);

        if ($from === $to) {
            throw new InvalidCopyTransition("Copy is already {$to->value}.");
        }

        $this->assertActorAllowed($copy, $from, $to, $actor);

        $fromLocation = $copy->location_id;
        $toLocation = $context['to_location_id'] ?? $copy->location_id;

        $copy->forceFill([
            'status' => $to,
            'location_id' => $toLocation,
            'status_changed_at' => now(),
        ])->save();

        CopyStatusTransition::create([
            'copy_id' => $copy->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'user_id' => $actor?->id,
            'loan_id' => $context['loan_id'] ?? null,
            'from_location_id' => $fromLocation,
            'to_location_id' => $toLocation,
            'note' => $context['note'] ?? null,
            'created_at' => now(),
        ]);

        $copy->refresh();

        return $copy;
    }

    public function can(Copy $copy, CopyStatus $to, ?User $actor = null): bool
    {
        try {
            $from = $copy->status instanceof CopyStatus ? $copy->status : CopyStatus::from((string) $copy->status);
            $this->assertActorAllowed($copy, $from, $to, $actor);

            return true;
        } catch (InvalidCopyTransition) {
            return false;
        }
    }

    private function assertActorAllowed(Copy $copy, CopyStatus $from, CopyStatus $to, ?User $actor): void
    {
        if ($actor === null) {
            return; // system context (seeders, internals) may move copies freely
        }

        if (! $actor->can('copies.transition')) {
            throw new InvalidCopyTransition('This role is not allowed to move copies.');
        }

        if ($actor->hasRole('shelver')) {
            $allowed = ($from === CopyStatus::AtReception && $to === CopyStatus::InTransit)
                || ($from === CopyStatus::InTransit && $to === CopyStatus::Available);

            if (! $allowed) {
                throw new InvalidCopyTransition('A shelver may only advance copies from reception to the shelf.');
            }

            return;
        }

        $targets = self::TRANSITIONS[$from->value];

        if ($to === CopyStatus::Lost && $from !== CopyStatus::Lost) {
            return; // librarian/admin may mark any copy as lost
        }

        if ($to === CopyStatus::InRepair && $from !== CopyStatus::InRepair) {
            return; // librarian/admin may send any copy to repair
        }

        if ($from === CopyStatus::Lost && $to === CopyStatus::Available) {
            if (! $actor->hasRole('administrator')) {
                throw new InvalidCopyTransition('Only an administrator may recover a lost copy.');
            }

            return;
        }

        if (in_array($to->value, $targets, true)) {
            return;
        }

        throw new InvalidCopyTransition("Cannot move a copy from {$from->value} to {$to->value}.");
    }
}
