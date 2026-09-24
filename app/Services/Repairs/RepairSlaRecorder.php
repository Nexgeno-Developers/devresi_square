<?php

namespace App\Services\Repairs;

use App\Models\RepairHistory;
use App\Models\RepairIssue;
use App\Models\RepairSlaEvent;
use Illuminate\Support\Facades\DB;

class RepairSlaRecorder
{
    /**
     * @param  array<string, mixed>  $classification
     */
    public function reported(RepairIssue $repair, array $classification, ?int $actorId): RepairSlaEvent
    {
        return $repair->slaEvents()->create([
            'event' => 'reported',
            'occurred_at' => $classification['reported_at'],
            'actor_id' => $actorId,
            'payload' => $classification['snapshot'],
        ]);
    }

    /**
     * First write of a milestone sticks. A later post does not replace it.
     */
    public function milestone(RepairIssue $repair, string $event, ?int $actorId, ?string $note = null): ?RepairSlaEvent
    {
        $column = match ($event) {
            'dispatched' => 'dispatched_at',
            'made_safe' => 'make_safe_at',
            'resolved' => 'resolved_at',
            default => null,
        };

        if ($column === null || $repair->{$column} !== null) {
            return null;
        }

        return DB::transaction(function () use ($repair, $event, $actorId, $note, $column) {
            $previousStatus = (string) $repair->status;
            $now = now();
            $actorColumn = match ($event) {
                'dispatched' => 'dispatched_by',
                'made_safe' => 'make_safe_by',
                'resolved' => 'resolved_by',
            };

            $repair->{$column} = $now;
            $repair->{$actorColumn} = $actorId;

            if ($event === 'dispatched' && strcasecmp($previousStatus, 'Pending') === 0) {
                $repair->status = 'Under Process';
                $repair->sub_status = 'Under Process';
            }

            if ($event === 'resolved') {
                $repair->status = 'Completed';
                $repair->sub_status = 'Completed';
            }

            $repair->save();

            RepairHistory::create([
                'repair_issue_id' => $repair->id,
                'action' => match ($event) {
                    'dispatched' => 'Trade dispatched',
                    'made_safe' => 'Property made safe',
                    'resolved' => 'Repair resolved',
                },
                'previous_status' => $previousStatus,
                'new_status' => (string) $repair->status,
                'note' => $note,
            ]);

            return $repair->slaEvents()->create([
                'event' => $event,
                'occurred_at' => $now,
                'actor_id' => $actorId,
                'note' => $note,
                'payload' => $repair->classification_snapshot,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $upgrade
     */
    public function heatingUpgraded(RepairIssue $repair, array $upgrade): RepairSlaEvent
    {
        $previous = $repair->classification_snapshot;

        $repair->forceFill([
            'classification_snapshot' => $upgrade['snapshot'],
            'sla_due_at' => $upgrade['sla_due_at'],
            'make_safe_due_at' => $upgrade['make_safe_due_at'],
            'emergency_access' => $upgrade['emergency_access'],
            'priority' => 'critical',
        ])->save();

        return $repair->slaEvents()->create([
            'event' => 'heating_upgraded',
            'occurred_at' => now(),
            'payload' => [
                'from' => $previous,
                'to' => $upgrade['snapshot'],
            ],
        ]);
    }
}
