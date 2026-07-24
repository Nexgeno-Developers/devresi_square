<?php

namespace App\Http\Controllers\Backend;

use App\Models\EventType;
use Illuminate\Http\Request;

class EventTypeController
{
    // 1. List all types
    public function index()
    {
        $types = EventType::orderBy('name')->get();
        return view('backend.events.event_types.index', compact('types'));
    }

    // 2. Show form to create new type
    public function create()
    {
        return view('backend.events.event_types.create');
    }

    // 3. Store new type
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:event_types,name',
            'slug'        => 'nullable|string|unique:event_types,slug',
            'description' => 'nullable|string',
        ]);

        EventType::create($validated);

        return redirect()->route('backend.event_types.index')
                         ->with('success', 'Event Type created.');
    }

    // 4. Show single type (optional)
    public function show(EventType $eventType)
    {
        return view('backend.events.event_types.show', compact('eventType'));
    }

    // 5. Show form to edit
    public function edit(EventType $eventType)
    {
        return view('backend.events.event_types.edit', compact('eventType'));
    }

    // 6. Update
    public function update(Request $request, EventType $eventType)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:event_types,name,' . $eventType->id,
            'slug'        => 'nullable|string|unique:event_types,slug,' . $eventType->id,
            'description' => 'nullable|string',
        ]);

        $eventType->update($validated);

        return redirect()->route('backend.event_types.index')
                         ->with('success', 'Event Type updated.');
    }

    // 7. Delete
    public function destroy(EventType $eventType)
    {
        // This will cascade-delete associated subtypes if you've set it up.
        $eventType->delete();
        return redirect()->route('backend.event_types.index')
                         ->with('success', 'Event Type deleted.');
    }
}
