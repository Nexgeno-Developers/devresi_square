<?php

namespace App\Http\Controllers\Backend;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\Upload;
use App\Models\User;
use App\Services\Saas\PortalAccessService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentsController 
{
    public function index()
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->isSuperAdmin() || WorkspaceAccess::canManageCurrentWorkspace($user)),
            403
        );

        $documents = Document::query()
            ->with(['documentType', 'documentable'])
            ->forAccount(current_account_id())
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('backend.documents.index', compact('documents'));
    }

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

        if (! $this->isSupportedDocumentableType($documentableType)) {
            return response('Invalid documentable type.', 404);
        }

        $documentable = $this->resolveDocumentable($documentableType, (int) $documentableId);
        $this->ensureDocumentableIsAccessible($documentable);
        $this->authorizeDocumentableUpload($documentable);
        $documentTypes = DocumentType::all();

        return view('components.backend.documents.documents_form', compact('documentable', 'documentTypes'))
               ->render();
    }
    
    /**
     * Render the “edit” form for an existing document.
     */
    public function edit(Document $document)
    {
        ensureModelBelongsToCurrentAccount($document);

        $documentable  = $document->documentable;
        $this->ensureDocumentableIsAccessible($documentable);
        $this->authorizeDocumentableUpload($documentable);
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
        $previousVisibility = null;
        if (!empty($data['document_id'])) {
            // Update existing
            $document = Document::whereIn('documentable_type', $this->documentableTypeAliases($data['documentable_type']))
                                ->where('documentable_id', $data['documentable_id'])
                                ->findOrFail($data['document_id']);
            ensureModelBelongsToCurrentAccount($document);
            $this->ensureDocumentableIsAccessible($document->documentable);
            $this->authorizeDocumentableUpload($document->documentable);
            $previousVisibility = $document->visibility;
            $document->update([
                'upload_ids'       => $data['upload_ids'],
                'document_type_id' => $data['document_type_id'] ?? null,
                'title'            => $data['title'] ?? $document->title,
                'visibility'       => $data['visibility'] ?? $document->visibility,
            ]);
        } else {
            $documentable = $this->resolveDocumentable($data['documentable_type'], (int) $data['documentable_id']);
            $this->ensureDocumentableIsAccessible($documentable);
            $this->authorizeDocumentableUpload($documentable);

            // Create new
            $document = Document::create([
                'account_id' => current_account_id(),
                'documentable_type'   => $documentable->getMorphClass(),
                'documentable_id'     => $documentable->id,
                'upload_ids'          => $data['upload_ids'],
                'document_type_id'    => $data['document_type_id'] ?? null,
                'title'               => $data['title'] ?? null,
                'visibility'          => $data['visibility'] ?? 'private',
                'created_by'          => auth()->id(),
            ]);
        }

        $document->refresh();
        if ($document->isSharedWithTenant() && ! in_array((string) $previousVisibility, Document::TENANT_VISIBILITIES, true)) {
            app(\App\Services\Documents\DocumentShareNotifier::class)->shared($document);
        }

        return $document;
    }

    /**
     * API endpoint to store or update document
     */
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'documentable_type'   => ['required', 'string', Rule::in($this->supportedDocumentableTypes())],
            'documentable_id'     => ['required', 'integer'],
            'upload_ids'          => ['required', 'string'], // comma-separated IDs
            'title'               => ['required', 'string', 'max:255'],
            'document_type_id'    => ['required', 'integer', Rule::exists('document_types', 'id')],
            'document_id'         => ['nullable', 'integer', Rule::exists('documents', 'id')],
            'share_with_tenant'   => ['nullable', 'boolean'],
            'visibility'          => ['nullable', Rule::in(['private', 'shared', 'portal'])],
        ]);
        $this->ensureUploadsAreAccessible($data['upload_ids']);

        if ($request->has('share_with_tenant')) {
            $data['visibility'] = $request->boolean('share_with_tenant') ? 'portal' : 'private';
        }

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
            'documentable_type' => ['required', 'string', Rule::in($this->supportedDocumentableTypes())],
            'documentable_id'   => 'required|integer',
            'document_id'       => 'nullable|integer|exists:documents,id',
            'document_type_id'  => 'nullable|integer',
            'search'            => 'nullable|string',
            'from_date'         => 'nullable|date',
            'to_date'           => 'nullable|date',
            'page'              => 'nullable|integer',
        ]);

        $q = Document::with('documentType')
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->whereIn('documentable_type', $this->documentableTypeAliases($data['documentable_type']))
            ->where('documentable_id',   $data['documentable_id']);

        $documentable = $this->resolveDocumentable($data['documentable_type'], (int) $data['documentable_id']);
        $this->ensureDocumentableIsAccessible($documentable);
        $this->authorizeDocumentableView($documentable);

        if (is_tenant_portal_user() && Schema::hasColumn('documents', 'visibility')) {
            $q->whereIn('visibility', Document::TENANT_VISIBILITIES);
        }

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

    public function download(Document $document)
    {
        ensureModelBelongsToCurrentAccount($document);
        Gate::authorize('download', $document);
        $this->ensureDocumentableIsAccessible($document->documentable);
        $this->authorizeDocumentableView($document->documentable);

        return $document->downloadResponse();
    }

    public function share(Request $request, Document $document)
    {
        ensureModelBelongsToCurrentAccount($document);
        Gate::authorize('share', $document);
        $this->ensureDocumentableIsAccessible($document->documentable);
        $this->authorizeDocumentableUpload($document->documentable);

        $share = $request->boolean('share_with_tenant');
        $wasShared = $document->isSharedWithTenant();
        $document->update([
            'visibility' => $share ? 'portal' : 'private',
        ]);
        if ($share && ! $wasShared) {
            app(\App\Services\Documents\DocumentShareNotifier::class)->shared($document->fresh());
        }

        flash($share
            ? 'This file is now visible to the tenant.'
            : 'This file is no longer shared with the tenant.'
        )->success();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'visibility' => $document->visibility,
            ]);
        }

        return back();
    }

    /**
     * AJAX: Show a single document’s full content in a modal.
     */
    public function showDocument($id)
    {
        $document = Document::with('documentType')->findOrFail($id);
        ensureModelBelongsToCurrentAccount($document);
        Gate::authorize('view', $document);
        $this->ensureDocumentableIsAccessible($document->documentable);
        $this->authorizeDocumentableView($document->documentable);

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
        ensureModelBelongsToCurrentAccount($document);
        Gate::authorize('delete', $document);
        $this->ensureDocumentableIsAccessible($document->documentable);
        $this->authorizeDocumentableUpload($document->documentable);
        $document->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Document deleted successfully!',
        ]);
    }

    private function authorizeDocumentableView($documentable): void
    {
        if (! $documentable instanceof Property) {
            return;
        }

        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user || ! $accountId) {
            return;
        }

        $portalAccessService = app(PortalAccessService::class);

        if ($portalAccessService->isPortalUser($user, $accountId)) {
            abort_unless($portalAccessService->canViewDocuments($user, $documentable), 403, 'You do not have access to property documents.');
        }
    }

    private function resolveDocumentable(string $type, int $id)
    {
        $class = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($type) ?: $type;
        if (! class_exists($class)) {
            abort(404, 'Invalid documentable type.');
        }

        return $class::findOrFail($id);
    }

    private function documentableTypeAliases(string $type): array
    {
        $class = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($type) ?: $type;
        $aliases = [$type, $class];
        if (class_exists($class)) {
            $aliases[] = (new $class)->getMorphClass();
        }

        return array_values(array_unique(array_filter($aliases)));
    }

    private function supportedDocumentableTypes(): array
    {
        return [
            Property::class,
            User::class,
            Tenancy::class,
            'Property',
            'Tenancy',
            'App\\Models\\Property',
            'App\\Models\\User',
            'App\\Models\\Tenancy',
        ];
    }

    private function isSupportedDocumentableType(string $type): bool
    {
        return in_array($type, $this->supportedDocumentableTypes(), true);
    }

    private function ensureDocumentableIsAccessible($documentable): void
    {
        ensureModelBelongsToCurrentAccount($documentable);

        if (
            $documentable instanceof User
            && ! auth()->user()?->hasRole('Super Admin')
            && ! User::forAccount(current_account_id())->whereKey($documentable->id)->exists()
        ) {
            abort(403, 'This user does not belong to your subscriber account.');
        }
    }

    private function ensureUploadsAreAccessible(string $uploadIds): void
    {
        if (auth()->user()?->hasRole('Super Admin')) {
            return;
        }

        $tokens = collect(explode(',', $uploadIds))->map(fn ($id) => trim($id))->filter()->values();
        if ($tokens->contains(fn ($id) => ! ctype_digit($id))) {
            throw ValidationException::withMessages([
                'upload_ids' => ['The selected files are invalid.'],
            ]);
        }

        $requestedIds = $tokens->map(fn ($id) => (int) $id)->unique()->values();
        $accessibleIds = Upload::forAccount(current_account_id())
            ->whereKey($requestedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($requestedIds->diff($accessibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'upload_ids' => ['One or more selected files do not belong to your subscriber account.'],
            ]);
        }
    }

    private function authorizeDocumentableUpload($documentable): void
    {
        if (! $documentable instanceof Property) {
            return;
        }

        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user || ! $accountId) {
            return;
        }

        $portalAccessService = app(PortalAccessService::class);

        if ($portalAccessService->isPortalUser($user, $accountId)) {
            abort_unless($portalAccessService->canUploadDocuments($user, $documentable), 403, 'You cannot upload documents for this property.');
        }
    }
}
