<?php

namespace App\Http\Controllers\Backend;

use Carbon\Carbon;
use App\Models\Event;
use App\Models\EventInstance;
use App\Models\EventInstanceChange;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RRule\RRule;

class EventController
{

    public function view(){
        return view('backend.events.calendar');
    }

    public function index(Request $request)
    {
        $start = Carbon::parse($request->query('start'));
        $end = Carbon::parse($request->query('end'));

        // Fetch events directly
        $events = Event::whereBetween('start_datetime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->with('reminders', 'properties', 'repairIssues', 'users')
            ->get();

        $data = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_datetime->format('Y-m-d H:i:s'),
                'end' => $event->end_datetime->format('Y-m-d H:i:s'),
                'type_id' => $event->type_id,
                'sub_type_id' => $event->sub_type_id,
                'backgroundColor' => $event->color, // assuming getColorAttribute() exists in model
                'status' => $event->status,
                'remindersCount' => $event->reminders->count(),
                'reminders' => $event->reminders->map(fn($r) => [
                    'minutes_before' => $r->minutes_before,
                    'channel' => $r->channel,
                ]),

                'extendedProps' => [
                    // 'start' => $event->start_datetime->toIso8601String(),
                    // 'end' => $event->end_datetime->toIso8601String(),
                    'start' => $event->start_datetime->format('Y-m-d H:i:s'),
                    'end' => $event->end_datetime->format('Y-m-d H:i:s'),
                    'event_id' => $event->id, // use parent_id if exists, else self
                    // 'parent_id' => $event->parent_id ?? $event->id,
                    'master_id' => $event->parent_id ?? $event->id, // if no parent, use self
                    'event_status' => $event->status,
                    'office' => $event->office,
                    // 'diary_owner' => $event->diary_owner,
                    // 'on_behalf_of' => $event->on_behalf_of,
                    'diary_owner' => optional($event->diaryOwner)->id,
                    'on_behalf_of' => optional($event->onBehalfOf)->id,
                    'users' => collect([$event->diaryOwner, $event->onBehalfOf])
                        ->filter()
                        ->map(fn($u) => ['id' => $u->id, 'text' => $u->display_label]),

                    'location' => $event->location,
                    'description' => $event->description,
                    'repeat_until_date' => $event->repeat_until_date,
                    'rrule' => $event->rrule,
                    'exdates' => $event->exdates,
                    'type_id' => $event->type_id,
                    'sub_type_id' => $event->sub_type_id,
                    'type_label' => $event->type,           // assuming string fallback or relationship
                    'sub_type_label' => $event->sub_type,
                    'reminders' => $event->reminders->map(fn($r) => [
                        'minutes_before' => $r->minutes_before,
                        'channel' => $r->channel,
                    ]),
                    'property_ids' => $event->properties->pluck('id')->toArray(),
                    'properties' => $event->properties->map(fn($p) => [
                        'id' => $p->id,
                        'text' => $p->display_label,
                    ]),
                    'repair_ids' => $event->repairIssues->pluck('id')->toArray(),
                    'repairs' => $event->repairIssues->map(fn($r) => [
                        'id' => $r->id,
                        'text' => $r->display_label,
                    ]),
                    // 'user_ids' => $event->users->pluck('id')->toArray(),
                    // 'users' => $event->users->map(fn($u) => [
                    //     'id' => $u->id,
                    //     'text' => $u->display_name,
                    // ]),

                ],
            ];
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type_id' => 'required|exists:event_types,id',
            'sub_type_id' => 'required|exists:event_sub_types,id',
            'office' => 'nullable|string|max:100',
            'status' => 'nullable|in:Confirmed,Pending,Cancelled,Rescheduled,Scheduled',
            'diary_owner' => 'nullable|string|max:255',
            'on_behalf_of' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'rrule' => 'nullable|string',
            'exdates' => 'nullable|string',
            'reminders' => 'nullable|array',
            'reminders.*.minutes_before' => 'nullable|integer|min:0',
            'reminders.*.channel' => 'nullable|in:email,in_app,sms,push',

            'property_ids' => 'nullable|array',
            'property_ids.*' => 'exists:properties,id',
            'repair_ids' => 'nullable|array',
            'repair_ids.*' => 'exists:repair_issues,id',
            // 'user_ids' => 'nullable|array',
            // 'user_ids.*' => 'exists:users,id',
        ]);

        \DB::beginTransaction();
        try {
            $start = Carbon::parse($validated['start_datetime']);
            $end = Carbon::parse($validated['end_datetime']);
            $duration = abs($end->diffInSeconds($start));

            // 1. Create master event
            $master = Event::create([
                'title' => $validated['title'],
                'type_id' => $validated['type_id'],
                'sub_type_id' => $validated['sub_type_id'],
                'office' => $validated['office'] ?? null,
                'status' => $validated['status'] ?? 'Pending',
                'diary_owner' => $validated['diary_owner'] ?? null,
                'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                'location' => $validated['location'] ?? null,
                'description' => $validated['description'] ?? null,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'rrule' => $validated['rrule'] ?? null,
                'exdates' => $validated['exdates'] ?? null,
            ]);

            // 2. Attach reminders to master
            if (!empty($validated['reminders'])) {
                foreach ($validated['reminders'] as $r) {
                    if (
                        (!isset($r['minutes_before']) || $r['minutes_before'] === '' || $r['minutes_before'] === null)
                        && (empty($r['channel']))
                    ) {
                        continue;
                    }
                    $master->reminders()->create([
                        'minutes_before' => $r['minutes_before'] ?? 0,
                        'channel' => $r['channel'] ?? 'email',
                    ]);
                }
            }

            // attach polymorphic relations:
            $this->syncMorphRelations($master, $validated);

            // 3. If rrule exists, generate child events with parent_id
            if (!empty($validated['rrule'])) {
                $rruleString = preg_replace('/^RRULE:/i', '', trim($validated['rrule']));
                $rule = new RRule($rruleString, $start);
                $exdates = json_decode($validated['exdates'] ?? '[]', true);
                $endLimit = now()->addYear();
                $reminders = $master->reminders()->get();

                foreach ($rule as $occurrence) {
                    $occ = Carbon::instance($occurrence);
                    if ($occ->equalTo($start) || $occ->greaterThan($endLimit))
                        continue;

                    $dateOnly = $occ->toDateString();
                    if (in_array($dateOnly, $exdates, true))
                        continue;

                    $child = Event::create([
                        'parent_id' => $master->id,
                        'title' => $master->title,
                        'type_id' => $master->type_id,
                        'sub_type_id' => $master->sub_type_id,
                        'office' => $master->office,
                        'status' => $master->status,
                        'diary_owner' => $master->diary_owner,
                        'on_behalf_of' => $master->on_behalf_of,
                        'location' => $master->location,
                        'description' => $master->description,
                        'reminder' => $master->reminder,
                        'start_datetime' => $occ,
                        'end_datetime' => $occ->copy()->addSeconds($duration),
                        'rrule' => $validated['rrule'] ?? null,
                        'exdates' => $validated['exdates'] ?? null,
                    ]);

                    // Copy reminders to each child event
                    foreach ($reminders as $r) {
                        $child->reminders()->create([
                            'minutes_before' => $r->minutes_before,
                            'channel' => $r->channel,
                        ]);
                    }

                    // attach polymorphic relations:
                    $this->syncMorphRelations($child, $validated);
                }
            }

            \DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            \DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while saving.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function updateInstance(Request $request, $id)
    {
        $event = Event::findOrFail($id); // now $event won't be null

        $data = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'form_action' => 'required|string|in:updateInstance'
        ]);

        $userId = auth()->id();

        $oldStart = optional($event->start_datetime)->format('Y-m-d H:i:s');
        $oldEnd = optional($event->end_datetime)->format('Y-m-d H:i:s');
        $oldStatus = $event->status;

        $event->update([
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $data['end_datetime'],
            'status' => 'Rescheduled',
            'instance_status' => 'Rescheduled',
            'is_exception' => true,
        ]);

        if ($oldStart !== $data['start_datetime']) {
            DB::table('event_instance_changes')->insert([
                'event_id' => $event->id,
                'changed_field' => 'start_datetime',
                'old_value' => $oldStart,
                'new_value' => $data['start_datetime'],
                'changed_by' => $userId,
                'changed_at' => now(),
                'comment' => 'Start time changed',
            ]);
        }

        if ($oldEnd !== $data['end_datetime']) {
            DB::table('event_instance_changes')->insert([
                'event_id' => $event->id,
                'changed_field' => 'end_datetime',
                'old_value' => $oldEnd,
                'new_value' => $data['end_datetime'],
                'changed_by' => $userId,
                'changed_at' => now(),
                'comment' => 'End time changed',
            ]);
        }

        if ($oldStatus !== 'Rescheduled') {
            DB::table('event_instance_changes')->insert([
                'event_id' => $event->id,
                'changed_field' => 'status',
                'old_value' => $oldStatus,
                'new_value' => 'Rescheduled',
                'changed_by' => $userId,
                'changed_at' => now(),
                'comment' => 'Status changed to Rescheduled',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateMaster(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type_id' => 'required|exists:event_types,id',
            'sub_type_id' => 'required|exists:event_sub_types,id',
            'office' => 'nullable|string|max:100',
            'status' => 'nullable|in:Confirmed,Pending,Cancelled,Rescheduled,Scheduled',
            'diary_owner' => 'nullable|string|max:255',
            'on_behalf_of' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'reminder' => ['nullable', 'string', 'regex:/^\d+(\s?(minutes|hours|days))?$/'],
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'rrule' => 'nullable|string',
            'exdates' => 'nullable|string',
            'reminders' => 'nullable|array',
            'reminders.*.minutes_before' => 'nullable|integer|min:0',
            'reminders.*.channel' => 'nullable|in:email,in_app,sms,push',
            'form_action' => 'required|string|in:updateMaster',
            'choice_action' => 'required|in:single,series,future',

            'property_ids' => 'nullable|array',
            'property_ids.*' => 'exists:properties,id',
            'repair_ids' => 'nullable|array',
            'repair_ids.*' => 'exists:repair_issues,id',
            // 'user_ids' => 'nullable|array',
            // 'user_ids.*' => 'exists:users,id',
        ]);

        \DB::beginTransaction();

        try {

            $choice = $request->input('choice_action', 'single'); // default to updateInstance

            $instanceId = $request->input('instance_id');
            $masterId = $request->input('master_id');
            $instance = Event::findOrFail($instanceId); // if instance_id is not provided, use the master event
            $newRrule = $validated['rrule'] ?? null;
            $rruleChanged = $instance->rrule !== $newRrule;

            switch ($choice) {
                case 'single':
                    // Update single instance

                    if ($rruleChanged && $newRrule) {
                        // Convert this instance into a new master
                        $instance->update([
                            'title' => $validated['title'],
                            'type_id' => $validated['type_id'],
                            'sub_type_id' => $validated['sub_type_id'],
                            'office' => $validated['office'] ?? null,
                            'status' => $validated['status'] ?? 'Confirmed',
                            'diary_owner' => $validated['diary_owner'] ?? null,
                            'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                            'location' => $validated['location'] ?? null,
                            'description' => $validated['description'] ?? null,
                            'reminder' => $validated['reminder'] ?? null,
                            'start_datetime' => $validated['start_datetime'],
                            'end_datetime' => $validated['end_datetime'],
                            'rrule' => $newRrule,
                            'exdates' => $validated['exdates'] ?? '[]',
                            'parent_id' => null,
                            'is_exception' => false,
                            'instance_status' => 'Scheduled',
                        ]);

                        $instance->reminders()->delete();
                        if ($request->filled('reminders')) {
                            foreach ($validated['reminders'] as $r) {
                                if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                    $instance->reminders()->create($r);
                                }
                            }
                        }

                        $this->syncMorphRelations($instance, $validated); // or $event

                        // Generate children from this new master
                        $this->generateChildInstances($instance, $validated);

                        \DB::commit();
                        return response()->json(['success' => true, 'message' => 'New recurring event created from single instance.']);
                    }

                    // Else, just update single instance without recurrence
                    $instance->update([
                        'title' => $validated['title'],
                        'type_id' => $validated['type_id'],
                        'sub_type_id' => $validated['sub_type_id'],
                        'office' => $validated['office'] ?? null,
                        'status' => $validated['status'] ?? 'Confirmed',
                        'diary_owner' => $validated['diary_owner'] ?? null,
                        'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                        'location' => $validated['location'] ?? null,
                        'description' => $validated['description'] ?? null,
                        'reminder' => $validated['reminder'] ?? null,
                        'start_datetime' => $validated['start_datetime'],
                        'end_datetime' => $validated['end_datetime'],
                        'rrule' => $validated['rrule'] ?? null,
                        'exdates' => $validated['exdates'] ?? null,
                        'is_exception' => false,
                        'instance_status' => 'Scheduled',
                        'parent_id' => $instance->parent_id, // keep it if it had one
                    ]);

                    $instance->reminders()->delete();
                    if ($request->filled('reminders')) {
                        foreach ($validated['reminders'] as $r) {
                            if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                $instance->reminders()->create($r);
                            }
                        }
                    }

                    // resync relations:
                    $this->syncMorphRelations($instance, $validated);

                    \DB::commit();
                    return response()->json(['success' => true, 'message' => 'Single occurrence updated.']);

                /*case 'series': {
                    $oldStart = Carbon::parse($event->start_datetime);
                    $oldEnd = Carbon::parse($event->end_datetime);

                    $newStart = Carbon::parse($validated['start_datetime']);
                    $newEnd = Carbon::parse($validated['end_datetime']);

                    $timeDiffInSeconds = $oldStart->diffInSeconds($newStart, false);
                    $timeChanged = $oldStart->ne($newStart) || $oldEnd->ne($newEnd);

                    // Always update master
                    $event->update([
                        'title' => $validated['title'],
                        'type_id' => $validated['type_id'],
                        'sub_type_id' => $validated['sub_type_id'],
                        'office' => $validated['office'] ?? null,
                        'status' => $validated['status'] ?? 'Confirmed',
                        'diary_owner' => $validated['diary_owner'] ?? null,
                        'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                        'location' => $validated['location'] ?? null,
                        'description' => $validated['description'] ?? null,
                        'reminder' => $validated['reminder'] ?? null,
                        'start_datetime' => $validated['start_datetime'],
                        'end_datetime' => $validated['end_datetime'],
                        'rrule' => $validated['rrule'],
                        'exdates' => $validated['exdates'] ?? '[]',
                        'is_exception' => false,
                        'instance_status' => 'Scheduled',
                    ]);

                    // Update master reminders
                    $event->reminders()->delete();
                    if ($request->filled('reminders')) {
                        foreach ($validated['reminders'] as $r) {
                            if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                $event->reminders()->create($r);
                            }
                        }
                    }

                    $this->syncMorphRelations($event, $validated);

                    if ($rruleChanged) {
                        // If recurrence rule changed, delete and regenerate all children
                        $event->children()->delete();
                        $this->generateChildInstances($event, $validated);
                    } else {
                        // If only time or other data changed, update each child
                        foreach ($event->children as $child) {
                            $adjustedStart = $child->start_datetime;
                            $adjustedEnd = $child->end_datetime;

                            if ($timeChanged) {
                                $adjustedStart = $child->start_datetime->copy()->addSeconds($timeDiffInSeconds);
                                $adjustedEnd = $child->end_datetime->copy()->addSeconds($timeDiffInSeconds);
                            }

                            $child->update([
                                'title' => $validated['title'],
                                'type_id' => $validated['type_id'],
                                'sub_type_id' => $validated['sub_type_id'],
                                'office' => $validated['office'] ?? null,
                                'status' => $validated['status'] ?? 'Confirmed',
                                'diary_owner' => $validated['diary_owner'] ?? null,
                                'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                                'location' => $validated['location'] ?? null,
                                'description' => $validated['description'] ?? null,
                                'reminder' => $validated['reminder'] ?? null,
                                'start_datetime' => $adjustedStart,
                                'end_datetime' => $adjustedEnd,
                                'rrule' => $validated['rrule'],
                                'exdates' => $validated['exdates'] ?? '[]',
                                'is_exception' => false,
                                'instance_status' => 'Scheduled',
                            ]);

                            $this->syncMorphRelations($child, $validated);
                        }
                    }

                    DB::commit();
                    return response()->json(['success' => true, 'message' => 'Series updated successfully.']);
                }*/

                case 'series': {

                    // Determine new master datetimes from child edit
                    if ($instance->parent_id != null) {
                        $master = $instance->parent;

                        $originalChildStart = Carbon::parse($instance->start_datetime);   // e.g. 2025-07-03 10:00
                        $newChildStart = Carbon::parse($validated['start_datetime']);     // e.g. 2025-07-03 08:00
                        $newChildEnd = Carbon::parse($validated['end_datetime']);         // e.g. 2025-07-03 11:00

                        // Step 1: Get the time shift (difference) between old and new start
                        $startShift = abs($newChildStart->diffInSeconds($originalChildStart, false)); // -7200 seconds (if moved from 10:00 to 08:00)

                        // Step 2: Apply this shift to master start/end time
                        $masterStart = Carbon::parse($master->start_datetime)->addSeconds($startShift);
                        $duration = abs($newChildEnd->diffInSeconds($newChildStart));
                        $masterEnd = $masterStart->copy()->addSeconds($duration);

                        // Step 3: Update master with adjusted datetime and validated data
                        $master->update([
                            'start_datetime' => $masterStart,
                            'end_datetime' => $masterEnd,
                            'title' => $validated['title'],
                            'type_id' => $validated['type_id'],
                            'sub_type_id' => $validated['sub_type_id'],
                            'office' => $validated['office'] ?? null,
                            'status' => $validated['status'] ?? 'Confirmed',
                            'diary_owner' => $validated['diary_owner'] ?? null,
                            'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                            'location' => $validated['location'] ?? null,
                            'description' => $validated['description'] ?? null,
                            'reminder' => $validated['reminder'] ?? null,
                            'rrule' => $validated['rrule'],
                            'exdates' => $validated['exdates'] ?? '[]',
                            'is_exception' => false,
                            'instance_status' => 'Scheduled',
                        ]);

                        // Step 4: Update reminders
                        $master->reminders()->delete();
                        if ($request->filled('reminders')) {
                            foreach ($validated['reminders'] as $r) {
                                if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                    $master->reminders()->create($r);
                                }
                            }
                        }

                        // Step 5: Sync relations
                        $this->syncMorphRelations($master, $validated);

                        // Step 6: Delete and regenerate children with updated time logic
                        $master->children()->delete();

                        // Important: use shifted master start time for recurrence
                        $this->generateChildInstances($master, [
                            ...$validated,
                            'start_datetime' => $masterStart->toDateTimeString(),
                            'end_datetime' => $masterEnd->toDateTimeString(),
                        ]);
                    } else {
                        $master = $instance;

                        // Always update non-time fields on master
                        $master->update([
                            'title' => $validated['title'],
                            'type_id' => $validated['type_id'],
                            'sub_type_id' => $validated['sub_type_id'],
                            'office' => $validated['office'] ?? null,
                            'status' => $validated['status'] ?? 'Confirmed',
                            'diary_owner' => $validated['diary_owner'] ?? null,
                            'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                            'start_datetime' => $validated['start_datetime'],
                            'end_datetime' => $validated['end_datetime'],
                            'location' => $validated['location'] ?? null,
                            'description' => $validated['description'] ?? null,
                            'reminder' => $validated['reminder'] ?? null,
                            'rrule' => $validated['rrule'],
                            'exdates' => $validated['exdates'] ?? '[]',
                            'is_exception' => false,
                            'instance_status' => 'Scheduled',
                        ]);

                        // Update master reminders
                        $master->reminders()->delete();
                        if ($request->filled('reminders')) {
                            foreach ($validated['reminders'] as $r) {
                                if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                    $master->reminders()->create($r);
                                }
                            }
                        }

                        // Sync relations
                        $this->syncMorphRelations($master, $validated);

                        // If recurrence rule changed: delete and regenerate children
                        $master->children()->delete();
                        $this->generateChildInstances($master, $validated);

                    }

                    DB::commit();
                    return response()->json(['success' => true, 'message' => 'Series updated successfully.']);
                }


                case 'future':
                    // Update all future instances

                    if ($rruleChanged) {
                        $oldMaster = $instance->parent ?? $instance;

                        // Step 1: Add current instance's start_datetime to old master exdates
                        $existingExdates = json_decode($oldMaster->exdates ?? '[]', true);
                        $existingExdates[] = $instance->start_datetime->format('Y-m-d\TH:i:s');
                        $oldMaster->update([
                            'exdates' => json_encode(array_unique($existingExdates)),
                        ]);

                        // Step 2: Make current instance a new master with updated data
                        $instance->update([
                            'title' => $validated['title'],
                            'type_id' => $validated['type_id'],
                            'sub_type_id' => $validated['sub_type_id'],
                            'office' => $validated['office'] ?? null,
                            'status' => $validated['status'] ?? 'Confirmed',
                            'diary_owner' => $validated['diary_owner'] ?? null,
                            'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                            'location' => $validated['location'] ?? null,
                            'description' => $validated['description'] ?? null,
                            'reminder' => $validated['reminder'] ?? null,
                            'start_datetime' => $validated['start_datetime'],
                            'end_datetime' => $validated['end_datetime'],
                            'rrule' => $newRrule,
                            'exdates' => $validated['exdates'] ?? '[]',
                            'is_exception' => false,
                            'instance_status' => 'Scheduled',
                            'parent_id' => null, // Make it a new master
                        ]);

                        $this->syncMorphRelations($instance, $validated);
                        // Step 3: Generate children from this new master
                        $this->generateChildInstances($instance, $validated);


                        \DB::commit();
                        return response()->json(['success' => true, 'message' => 'Future instances updated with new series.']);
                    }

                    // 1) Find the “master” of this series (if this instance has no parent, it is the master)
                    $master = $instance->parent ?: $instance;

                    // 2) Update this clicked instance first
                    $instance->update([
                        'title' => $validated['title'],
                        'type_id' => $validated['type_id'],
                        'sub_type_id' => $validated['sub_type_id'],
                        'office' => $validated['office'] ?? null,
                        'status' => $validated['status'] ?? 'Confirmed',
                        'diary_owner' => $validated['diary_owner'] ?? null,
                        'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                        'location' => $validated['location'] ?? null,
                        'description' => $validated['description'] ?? null,
                        'reminder' => $validated['reminder'] ?? null,
                        'start_datetime' => $validated['start_datetime'],
                        'end_datetime' => $validated['end_datetime'],
                        'is_exception' => false,
                        'instance_status' => 'Scheduled',
                    ]);

                    // 3) Sync its reminders
                    $instance->reminders()->delete();
                    if ($request->filled('reminders')) {
                        foreach ($validated['reminders'] as $r) {
                            if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                $instance->reminders()->create($r);
                            }
                        }
                    }

                    $this->syncMorphRelations($instance, $validated);

                    // 4) Grab _future_ children via the relation, then loop to update each
                    $futureChildren = $master
                        ->children()
                        ->where('start_datetime', '>', $instance->start_datetime)
                        ->get();

                    foreach ($futureChildren as $child) {
                        $child->update([
                            'title' => $validated['title'],
                            'type_id' => $validated['type_id'],
                            'sub_type_id' => $validated['sub_type_id'],
                            'office' => $validated['office'] ?? null,
                            'status' => $validated['status'] ?? 'Confirmed',
                            'diary_owner' => $validated['diary_owner'] ?? null,
                            'on_behalf_of' => $validated['on_behalf_of'] ?? null,
                            'location' => $validated['location'] ?? null,
                            'description' => $validated['description'] ?? null,
                            'reminder' => $validated['reminder'] ?? null,
                            'is_exception' => false,
                            'instance_status' => 'Scheduled',
                        ]);

                        // re‐sync reminders on each future child
                        $child->reminders()->delete();
                        if ($request->filled('reminders')) {
                            foreach ($validated['reminders'] as $r) {
                                if (!empty($r['minutes_before']) && !empty($r['channel'])) {
                                    $child->reminders()->create($r);
                                }
                            }
                        }

                        $this->syncMorphRelations($child, $validated);

                    }


                    \DB::commit();
                    return response()->json(['success' => true, 'message' => 'Future occurrences updated successfully.']);

                default:
                    \DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
            }

        } catch (\Throwable $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Generate all child events from a master, starting _after_ $startFrom (if given).
     *
     * @param  \App\Models\Event  $master
     * @param  array               $validated
     * @param  \Carbon\Carbon|null $startFrom
     * @return void
     */
    protected function generateChildInstances(Event $master, array $validated, ?Carbon $startFrom = null)
    {
        // 1) Normalize the RRULE
        $rruleStr = preg_replace('/^RRULE:/i', '', trim($validated['rrule']));

        $dtStart = Carbon::parse($validated['start_datetime']);
        $dtEnd = Carbon::parse($validated['end_datetime']);
        $duration = abs($dtEnd->diffInSeconds($dtStart));

        $rule = new RRule($rruleStr, $dtStart);
        $exdates = json_decode($validated['exdates'] ?? '[]', true);
        $reminders = $master->reminders()->get();
        $endLimit = now()->addYear();

        foreach ($rule as $occurrence) {
            $occStart = Carbon::instance($occurrence);

            // 2) Skip original master
            if ($occStart->equalTo($dtStart)) {
                continue;
            }

            // 3) If we’re rebuilding “future” only, skip before pivot
            if ($startFrom && $occStart->lt($startFrom)) {
                continue;
            }

            // 4) Too far out?
            if ($occStart->greaterThan($endLimit)) {
                break;
            }

            // 5) Skip exclusions
            if (in_array($occStart->toDateString(), $exdates, true)) {
                continue;
            }

            // 6) Create each child
            $child = Event::create([
                'parent_id' => $master->id,
                'title' => $master->title,
                'type_id' => $master->type_id,
                'sub_type_id' => $master->sub_type_id,
                'office' => $master->office,
                'status' => $master->status,
                'diary_owner' => $master->diary_owner,
                'on_behalf_of' => $master->on_behalf_of,
                'location' => $master->location,
                'description' => $master->description,
                'reminder' => $master->reminder,
                'start_datetime' => $occStart,
                'end_datetime' => $occStart->copy()->addSeconds($duration),
                'rrule' => $master->rrule,
                'exdates' => $master->exdates,
                'is_exception' => false,
                'instance_status' => 'Scheduled',
            ]);

            // 7) Copy over reminders
            foreach ($reminders as $r) {
                $child->reminders()->create([
                    'minutes_before' => $r->minutes_before,
                    'channel' => $r->channel,
                ]);
            }
            $this->syncMorphRelations($child, $validated);
        }
    }


    public function cancelInstance(Request $request, $id)
    {
        $request->validate([
            'choice_action' => 'in:single,series,future',
            'occurrence_start' => 'required|date',
        ]);

        $choice = $request->input('choice_action', 'single');
        $pivot = Carbon::parse($request->occurrence_start)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');

        $event = Event::findOrFail($id);

        switch ($choice) {
            case 'single':
                // Cancel only this instance
                $event->update([
                    'status' => 'Cancelled',
                    'instance_status' => 'Cancelled',
                    'is_exception' => true,
                ]);
                break;

            case 'series':
                // Determine the master event
                $master = $event->parent ?: $event; // if current event has no parent, it's master

                // Cancel the master event
                $master->update([
                    'status' => 'Cancelled',
                    'instance_status' => 'Cancelled',
                    'is_exception' => true,
                ]);

                // Cancel all its children (instances)
                $master->children()
                    ->where('status', '!=', 'Cancelled')
                    ->update([
                            'status' => 'Cancelled',
                            'instance_status' => 'Cancelled',
                            'is_exception' => true,
                        ]);
                break;

            case 'future':
                // Determine the master event
                $master = $event->parent ?: $event;

                // Cancel the master if it starts at or after the pivot
                if ($master->start_datetime >= $pivot) {
                    $master->update([
                        'status' => 'Cancelled',
                        'instance_status' => 'Cancelled',
                        'is_exception' => true,
                    ]);
                }

                // Cancel children starting from pivot onward
                $master->children()
                    ->where('start_datetime', '>=', $pivot)
                    ->where('status', '!=', 'Cancelled')
                    ->update([
                            'status' => 'Cancelled',
                            'instance_status' => 'Cancelled',
                            'is_exception' => true,
                        ]);
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Cancellation successful.']);
    }


    public function deleteInstance(Request $request, $id)
    {
        $request->validate([
            'choice_action' => 'in:single,series,future',
            'occurrence_start' => 'required|date',
        ]);

        $choice = $request->input('choice_action', 'single');
        $pivot = Carbon::parse($request->occurrence_start)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');

        $event = Event::findOrFail($id);

        switch ($choice) {
            case 'single':
                if ($event->children()->exists()) {
                    // Get the first child (by earliest start, or just first one)
                    $newParent = $event->children()->orderBy('start_datetime')->first();

                    if ($newParent) {
                        // Reassign all other children to the first child
                        $event->children()
                            ->where('id', '!=', $newParent->id)
                            ->update(['parent_id' => $newParent->id]);
                    }

                    // Now delete the original master
                    $event->delete();
                } else {
                    // No children, safe to delete
                    $event->delete();
                }
                break;

            case 'series':
                // Always resolve the real master event by following parent_id
                $master = $event->parent_id ? Event::find($event->parent_id) : $event;

                if (!$master) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Master event not found.'
                    ], 404);
                }

                // Delete all child events first
                $master->children()->delete();

                // Then delete the master event
                $master->delete();
                break;


            case 'future':
                // Determine the master event
                $master = $event->parent ?: $event;

                // Delete the master if it starts at or after the pivot
                if ($master->start_datetime >= $pivot) {
                    $master->delete();
                }

                // Delete children starting from pivot onward
                $master->children()
                    ->where('start_datetime', '>=', $pivot)
                    ->delete();
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Deletion successful.']);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:confirmed,pending,cancelled,rescheduled,scheduled,completed']);

        $event = Event::findOrFail($id);
        $event->status = $request->status;
        $event->save();

        return response()->json(['success' => true]);
    }

    protected function syncMorphRelations(Event $event, array $validated)
    {
        $event->properties()->sync($validated['property_ids'] ?? []);
        $event->repairIssues()->sync($validated['repair_ids'] ?? []);
        // $event->users()->sync($validated['user_ids'] ?? []);
    }

}