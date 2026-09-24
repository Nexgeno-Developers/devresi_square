<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RepairIssue;
use App\Services\Repairs\RepairSlaRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RepairSlaController extends Controller
{
    public function store(Request $request, RepairIssue $repairIssue, string $event, RepairSlaRecorder $recorder): RedirectResponse
    {
        ensureModelBelongsToCurrentAccount($repairIssue);
        Gate::authorize('update', $repairIssue);
        abort_unless($repairIssue->isPriorityComplaint(), 404);
        abort_unless(in_array($event, ['dispatched', 'made_safe', 'resolved'], true), 404);

        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        $recorded = $recorder->milestone($repairIssue, $event, $request->user()?->id, $validated['note'] ?? null);

        if ($recorded) {
            flash('The time has been saved on this repair.')->success();
        } else {
            flash('That time is already on the record.')->error();
        }

        return redirect()->route('admin.property_repairs.show', $repairIssue);
    }
}
