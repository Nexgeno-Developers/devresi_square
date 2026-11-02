<?php

namespace App\Services\Events;

use App\Enums\CrmNotificationEvent;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventStageService
{
    /**
     * Keep one reply row per invited person and set the visit status from those replies.
     *
     * @return list<int> user ids invited for the first time
     */
    public function settle(Event $event, bool $timeChanged = false): array
    {
        $event->loadMissing('users');
        $invitedIds = $event->users->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $newIds = [];

        foreach ($invitedIds as $userId) {
            $row = EventResponse::query()->firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $userId],
                ['response' => 'pending']
            );
            if ($row->wasRecentlyCreated) {
                $newIds[] = $userId;
            }
        }

        EventResponse::query()
            ->where('event_id', $event->id)
            ->whereNotIn('user_id', $invitedIds->all() ?: [0])
            ->delete();

        if ($timeChanged && $invitedIds->isNotEmpty()) {
            EventResponse::query()
                ->where('event_id', $event->id)
                ->update([
                    'response' => 'pending',
                    'note' => null,
                    'responded_at' => null,
                    'updated_at' => now(),
                ]);
        }

        $this->applyStatus($event->fresh(['users', 'responses']), $timeChanged);

        if ($newIds !== [] && $event->parent_id === null) {
            $ids = $newIds;
            DB::afterCommit(function () use ($event, $ids): void {
                try {
                    $fresh = Event::query()->with('account')->find($event->id);
                    if (! $fresh) {
                        return;
                    }
                    $recipients = User::query()->whereIn('id', $ids)->get();
                    if ($recipients->isEmpty()) {
                        return;
                    }
                    app(CrmNotificationService::class)->dispatch(
                        CrmNotificationEvent::AppointmentInvited,
                        $fresh,
                        [
                            'account_id' => $fresh->account_id,
                            'recipients' => $recipients,
                            'channels' => ['email', 'system'],
                            'appointment_title' => $fresh->title,
                            'appointment_at' => $fresh->start_datetime?->timezone($fresh->account?->timezone ?: 'Europe/London')->format('d M Y, H:i'),
                            'action_url' => route('backend.events.calendar'),
                            'portal_action_url' => route('tenant.calendar'),
                            'portal_action_tenants_only' => true,
                            'milestone' => 'invited-'.$fresh->id,
                        ]
                    );
                } catch (\Throwable $exception) {
                    Log::error('Unable to send appointment invitations.', [
                        'event_id' => $event->id,
                        'exception' => $exception,
                    ]);
                }
            });
        }

        return $newIds;
    }

    public function record(Event $event, User $user, string $response, ?string $note): Event
    {
        $invited = $event->users()->where('users.id', $user->id)->exists();
        abort_unless($invited, 403);

        EventResponse::query()->updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'response' => $response,
                'note' => $note,
                'responded_at' => now(),
            ]
        );

        $this->applyStatus($event->fresh(['users', 'responses']), false);

        return $event->fresh();
    }

    private function applyStatus(Event $event, bool $timeChanged): void
    {
        $states = $event->users->map(function (User $user) use ($event) {
            return $event->responses->firstWhere('user_id', $user->id)?->response ?? 'pending';
        });

        if ($states->isEmpty()) {
            $status = 'Scheduled';
        } elseif ($states->every(fn (string $state) => $state === 'rejected')) {
            $status = 'Cancelled';
        } elseif ($states->contains('pending')) {
            $status = ($timeChanged || $event->status === 'Rescheduled') ? 'Rescheduled' : 'Pending';
        } else {
            $status = 'Confirmed';
        }

        $instanceStatus = $event->instance_status;
        if ($status === 'Cancelled') {
            $instanceStatus = 'Cancelled';
        } elseif (
            $event->end_datetime
            && $event->end_datetime->isPast()
            && in_array($status, ['Confirmed', 'Scheduled', 'Rescheduled'], true)
        ) {
            $instanceStatus = 'Completed';
        }

        if ($event->status !== $status || $event->instance_status !== $instanceStatus) {
            $event->forceFill([
                'status' => $status,
                'instance_status' => $instanceStatus,
            ])->saveQuietly();
        }
    }
}
