<?php

namespace App\Http\Controllers\Backend;

use App\Models\Notes;
use App\Models\NoteType;
use App\Models\Property;
use App\Services\Saas\PortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
        ensureModelBelongsToCurrentAccount($noteable);
        $this->authorizeNoteableWrite($noteable);
        $noteTypes = NoteType::all();

        return view('components.backend.notes.notes_form', compact('noteTypes', 'noteable'))->render();
    }

    public function edit(Notes $note)
    {
        ensureModelBelongsToCurrentAccount($note);

        $noteable = $note->noteable;
        ensureModelBelongsToCurrentAccount($noteable);
        $this->authorizeNoteableWrite($noteable);
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
            ensureModelBelongsToCurrentAccount($note);
            $this->authorizeNoteableWrite($note->noteable);
            $note->update([
                'note_type_id' => $data['note_type_id'],
                'content' => $data['content'],
                'visibility' => $data['visibility'] ?? $note->visibility ?? 'private',
            ]);
        } else {
            $noteable = $data['noteable_type']::findOrFail($data['noteable_id']);
            ensureModelBelongsToCurrentAccount($noteable);
            $this->authorizeNoteableWrite($noteable);

            $note = Notes::create([
                'account_id' => current_account_id(),
                'noteable_type' => $data['noteable_type'],
                'noteable_id'   => $data['noteable_id'],
                'note_type_id'  => $data['note_type_id'],
                'content'       => $data['content'],
                'visibility'    => $data['visibility'] ?? 'private',
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
            'visibility'    => 'nullable|in:private,shared,portal',
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
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->where('noteable_type', $data['noteable_type'])
            ->where('noteable_id', $data['noteable_id']);

        if (class_exists($data['noteable_type'])) {
            $noteable = $data['noteable_type']::findOrFail($data['noteable_id']);
            ensureModelBelongsToCurrentAccount($noteable);
            $this->authorizeNoteableView($noteable);

            if ($this->isPortalPropertyViewer($noteable) && Schema::hasColumn('notes', 'visibility')) {
                $q->where('visibility', 'portal');
            }
        }

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
        ensureModelBelongsToCurrentAccount($note);
        $this->authorizeNoteableView($note->noteable);

        if ($this->isPortalPropertyViewer($note->noteable)) {
            abort_unless(($note->visibility ?? 'private') === 'portal', 403, 'You do not have access to this note.');
        }

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
        ensureModelBelongsToCurrentAccount($note);
        $this->authorizeNoteableWrite($note->noteable);
        $note->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Note deleted successfully!',
        ]);
    }

    private function authorizeNoteableView($noteable): void
    {
        if (! $noteable instanceof Property) {
            return;
        }

        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user || ! $accountId) {
            return;
        }

        $portalAccessService = app(PortalAccessService::class);

        if ($portalAccessService->isPortalUser($user, $accountId)) {
            abort_unless($portalAccessService->canAccessProperty($user, $noteable), 403, 'You do not have access to property notes.');
        }
    }

    private function authorizeNoteableWrite($noteable): void
    {
        if (! $noteable instanceof Property) {
            return;
        }

        if ($this->isPortalPropertyViewer($noteable)) {
            abort(403, 'Portal users cannot manage private notes.');
        }
    }

    private function isPortalPropertyViewer($noteable): bool
    {
        if (! $noteable instanceof Property) {
            return false;
        }

        $user = auth()->user();
        $accountId = current_account_id();

        return (bool) ($user && $accountId && app(PortalAccessService::class)->isPortalUser($user, $accountId));
    }
}
