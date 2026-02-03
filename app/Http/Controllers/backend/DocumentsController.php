<?php

namespace App\Http\Controllers\Backend;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentsController 
{   
    /**
     * Render the “create new document” form.
     */
    public function create(Request $request)
    {
        $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id'   => 'required|integer',
        ]);

        $documentableType = $request->documentable_type;
        $documentableId   = $request->documentable_id;

        if (! class_exists($documentableType)) {
            return response('Invalid documentable type.', 404);
        }

        $documentable = $documentableType::findOrFail($documentableId);
        $documentTypes = DocumentType::all();

        return view('components.backend.documents.documents_form', compact('documentable', 'documentTypes'))
               ->render();
    }
    
    /**
     * Render the “edit” form for an existing document.
     */
    public function edit(Document $document)
    {
        $documentable  = $document->documentable;
        $documentTypes = DocumentType::all();

        return view('components.backend.documents.documents_form', [
            'document'     => $document,
            'documentable' => $documentable,
            'documentTypes'=> $documentTypes,
        ])->render();
    }


    /**
     * Create or update a document record
     * Expected input: documentable_type, documentable_id, upload_ids, optional document_id for update, optional document_type_id
     */
    public function saveDocumentData(array $data)
    {
        if (!empty($data['document_id'])) {
            // Update existing
            $document = Document::where('documentable_type', $data['documentable_type'])
                                ->where('documentable_id', $data['documentable_id'])
                                ->findOrFail($data['document_id']);
            $document->update([
                'upload_ids'       => $data['upload_ids'],
                'document_type_id' => $data['document_type_id'] ?? null,
            ]);
        } else {
            // Create new
            $document = Document::create([
                'documentable_type'   => $data['documentable_type'],
                'documentable_id'     => $data['documentable_id'],
                'upload_ids'          => $data['upload_ids'],
                'document_type_id'    => $data['document_type_id'] ?? null,
            ]);
        }
        return $document;
    }

    /**
     * API endpoint to store or update document
     */
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'documentable_type'   => ['required', 'string'],
            'documentable_id'     => ['required', 'integer'],
            'upload_ids'          => ['required', 'string'], // comma-separated IDs
            'document_type_id'    => ['nullable', 'integer', Rule::exists('document_types', 'id')],
            'document_id'         => ['nullable', 'integer', Rule::exists('documents', 'id')],
        ]);

        $document = $this->saveDocumentData($data);

        return response()->json([
            'status'   => true,
            'message'  => !empty($data['document_id']) ? 'Document updated' : 'Document created',
            'document' => $document->load('documentType'),
        ]);
    }

    /**
     * List documents for a given model (with optional filters + pagination).
     */
    public function listDocuments(Request $request)
    {
        $data = $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id'   => 'required|integer',
            'document_id'       => 'nullable|integer|exists:documents,id',
            'document_type_id'  => 'nullable|integer',
            'search'            => 'nullable|string',
            'from_date'         => 'nullable|date',
            'to_date'           => 'nullable|date',
            'page'              => 'nullable|integer',
        ]);

        $q = Document::with('documentType')
            ->where('documentable_type', $data['documentable_type'])
            ->where('documentable_id',   $data['documentable_id']);

        if (! empty($data['document_type_id'])) {
            $q->where('document_type_id', $data['document_type_id']);
        }
        if (! empty($data['from_date'])) {
            $q->whereDate('created_at', '>=', $data['from_date']);
        }
        if (! empty($data['to_date'])) {
            $q->whereDate('created_at', '<=', $data['to_date']);
        }

        $documents    = $q->orderByDesc('updated_at')
                         ->paginate(5)
                         ->appends($request->except('page'));

        $documentTypes = DocumentType::orderBy('name')->get();

        $html = view('components.backend.documents._documents_list', compact('documents', 'documentTypes'))
                ->render();

        return response()->json(['html' => $html]);
    }

    /**
     * AJAX: Show a single document’s full content in a modal.
     */
    public function showDocument($id)
    {
        $document = Document::with('documentType')->findOrFail($id);

        $html = view('components.backend.documents._documents_show', compact('document'))
                ->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Delete a Document record by ID
     */
    public function deleteDocument($id)
    {
        $document = Document::findOrFail($id);
        $document->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Document deleted successfully!',
        ]);
    }
}
