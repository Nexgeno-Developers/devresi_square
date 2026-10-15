<?php

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;

class AuditTrail
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function record(?int $accountId, ?int $actorUserId, string $action, Model $subject, array $before, array $after): void
    {
        try {
            $subjectType = $subject->getMorphClass();
        } catch (ClassMorphViolationException) {
            $subjectType = $subject::class;
        }

        AuditEvent::query()->create([
            'account_id' => $accountId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subject->getKey(),
            'before' => $before,
            'after' => $after,
        ]);
    }
}
