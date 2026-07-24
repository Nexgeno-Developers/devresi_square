<?php

namespace App\Http\Controllers\Backend;

use App\Models\NoteType;
use Illuminate\Http\Request;

class NoteTypeController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $noteTypes = NoteType::latest()->paginate(10);
        return view('backend.note_types.index', compact('noteTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $noteType = new NoteType();
        return view('backend.note_types.form', compact('noteType'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:note_types,name']);
        NoteType::create($request->only('name'));
        flash("Note type created!")->success();
        return redirect()->route('admin.note-types.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $noteType = NoteType::findOrFail($id);
        return view('backend.note-types.show', compact('noteType'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $noteType = NoteType::findOrFail($id);
        return view('backend.note_types.form', compact('noteType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate(['name' => 'required|string|unique:note_types,name,' . $id]);
        $noteType = NoteType::findOrFail($id);
        $noteType->update($request->only('name'));
        flash("Note type updated!")->success();
        return redirect()->route('admin.note-types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $noteType = NoteType::findOrFail($id);
        $noteType->delete();
        flash( 'Note type deleted.')->success();
        return redirect()->route('admin.note-types.index');
    }
}
