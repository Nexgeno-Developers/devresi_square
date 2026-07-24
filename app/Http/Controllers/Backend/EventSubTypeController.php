<?php

namespace App\Http\Controllers\Backend;

use App\Models\EventType;
use App\Models\EventSubType;
use Illuminate\Http\Request;

class EventSubTypeController
{
        // 1. List all subtypes (with their parent type)
    public function index()
    {
        $subtypes = EventSubType::with('type')->orderBy('name')->get();
        return view('backend.events.event_sub_types.index', compact('subtypes'));
    }

    // 2. Show form to create new subtype
    public function create()
    {
        // We need a list of existing types to choose from
        $types = EventType::orderBy('name')->pluck('name','id');
        return view('backend.events.event_sub_types.create', compact('types'));
    }

    // 3. Store new subtype
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_type_id' => 'required|exists:event_types,id',
            'name'          => 'required|string|max:100',
            'slug'          => 'nullable|string|unique:event_sub_types,slug',
            'description'   => 'nullable|string',
        ]);

        EventSubType::create($validated);

        return redirect()->route('backend.events.event_sub_types.index')
                         ->with('success', 'Event SubType created.');
    }

    // 4. Show single subtype
    public function show(EventSubType $eventSubType)
    {
        return view('backend.events.event_sub_types.show', compact('eventSubType'));
    }

    // 5. Show form to edit
    public function edit(EventSubType $eventSubType)
    {
        $types = EventType::orderBy('name')->pluck('name','id');
        return view('backend.events.event_sub_types.edit', compact('eventSubType','types'));
    }

    // 6. Update
    public function update(Request $request, EventSubType $eventSubType)
    {
        $validated = $request->validate([
            'event_type_id' => 'required|exists:event_types,id',
            'name'          => 'required|string|max:100',
            'slug'          => 'nullable|string|unique:event_sub_types,slug,' . $eventSubType->id,
            'description'   => 'nullable|string',
        ]);

        $eventSubType->update($validated);

        return redirect()->route('backend.event_sub_types.index')
                         ->with('success', 'Event SubType updated.');
    }

    // 7. Delete
    public function destroy(EventSubType $eventSubType)
    {
        $eventSubType->delete();
        return redirect()->route('backend.event_sub_types.index')
                         ->with('success', 'Event SubType deleted.');
    }

    /**
     * 8. AJAX Endpoint for subtypes by type.
     *    Called when a frontend dropdown changes type => load subtypes
     */
    public function byType($typeId)
    {
        $subs = EventSubType::where('event_type_id', $typeId)
                             ->orderBy('name')
                             ->pluck('name','id');
        return response()->json($subs);
    }
}
