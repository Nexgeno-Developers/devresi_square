<?php

namespace App\Http\Controllers\Backend;

use App\Models\Notes;
use App\Models\NoteType;
use Illuminate\Http\Request;

class NotesController 
{   
    public function create(Request $request)
    {
        $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id' => 'required|integer',
        ]);

        $noteableType = $request->noteable_type;
        $noteableId = $request->noteable_id;

        if (!class_exists($noteableType)) {
            return response('Invalid noteable type.', 404);
        }

        $noteable = $noteableType::findOrFail($noteableId);
        $noteTypes = NoteType::all();

        return view('components.backend.notes.notes_form', compact('noteTypes', 'noteable'))->render();
    }

    public function edit(Notes $note)
    {
        $noteable = $note->noteable;
        $noteTypes = NoteType::all();

        return view('components.backend.notes.notes_form', [
            'noteTypes' => $noteTypes,
            'note' => $note,
            'noteable' => $noteable,
        ])->render();
    }

    /**
     * Create or update a note
     * Expected input: noteable_type, noteable_id, type, content, optional note_id for update
     */
    // Add a method to handle logic, returning Note model
    public function saveNoteData(array $data)
    {
        if (!empty($data['note_id'])) {
            $note = Notes::where('noteable_type', $data['noteable_type'])
                        ->where('noteable_id', $data['noteable_id'])
                        ->findOrFail($data['note_id']);
            $note->update([
                'note_type_id' => $data['note_type_id'],
                'content' => $data['content'],
            ]);
        } else {
            $note = Notes::create([
                'noteable_type' => $data['noteable_type'],
                'noteable_id'   => $data['noteable_id'],
                'note_type_id'  => $data['note_type_id'],
                'content'       => $data['content'],
            ]);
        }
        return $note;
    }

    // Keep storeOrUpdate as an API endpoint
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id'   => 'required|integer',
            'note_type_id'   => 'required|exists:note_types,id',
            'content'       => 'required|string',
            'note_id'       => 'nullable|exists:notes,id',
        ]);

        $note = $this->saveNoteData($data);

        return response()->json([
            'status'  => true,
            'message' => $data['note_id'] ? 'Note updated' : 'Note created',
            'note'    => $note->load('noteType'),
        ]);
    }
        

    // Get list of notes for a property (and optional single note for edit)
    /**
     * List notes optionally filtered by noteable_type and noteable_id.
     * You can also pass note_id to get a single note separately if needed.
     */
    public function listNotes(Request $request)
    {
        $data = $request->validate([
            'noteable_type' => 'required|string',
            'noteable_id'   => 'required|integer',
            'note_id'       => 'nullable|integer|exists:notes,id',
            'note_type_id'  => 'nullable|integer',
            'search'        => 'nullable|string',
            'from_date'     => 'nullable|date',
            'to_date'       => 'nullable|date',
            'page'          => 'nullable|integer',
        ]);

        $q = Notes::with('noteType')
            ->where('noteable_type', $data['noteable_type'])
            ->where('noteable_id', $data['noteable_id']);

        if (isset($data['note_type_id'] ) && $data['note_type_id']) {
            $q->where('note_type_id', $data['note_type_id']);
        }
        if (!empty($data['search'])) {
            $q->where('content','like','%'.$data['search'].'%');
        }
        if (!empty($data['from_date'])) {
            $q->whereDate('created_at','>=',$data['from_date']);
        }
        if (!empty($data['to_date'])) {
            $q->whereDate('created_at','<=',$data['to_date']);
        }

        $notes     = $q->orderByDesc('updated_at')
                           ->paginate(5)
                           ->appends($request->except('page'));
        $noteTypes = NoteType::orderBy('name')->get();

        // Render the list partial
        $html = view('components.backend.notes._notes_list', compact('notes','noteTypes'))->render();

        return response()->json(['html' => $html]);
    }   


    // Show single note content (for popup)
    /**
     * Show a single note by ID
     */
    /**
     * AJAX: Show a single note in “view” mode (rendered HTML).
     */
    public function showNote($id)
    {
        $note = Notes::with('noteType')->findOrFail($id);

        // Render the “show” partial
        $html = view('components.backend.notes._notes_show', compact('note'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Delete a note by ID
     */
    public function deleteNote($id)
    {
        $note = Notes::findOrFail($id);
        $note->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Note deleted successfully!',
        ]);
    }
}
