@props(['documentableType', 'documentableId', 'documentTypes', 'initialDocuments' => null, 'canUploadDocuments' => true])
<div class="documents-component{{ is_landlord_plan_user() ? ' documents-component--landlord' : '' }}" data-documentable-type="{{ $documentableType }}" data-documentable-id="{{ $documentableId }}">

    {{-- ADD NEW --}}
    @if($canUploadDocuments)
    <div class="mb-3">
        <button type="button" class="btn btn-outline-primary documents-add">Add document</button>
    </div>
    @endif
    {{-- FILTER FORM --}}
    <form class="documents-filter-form row g-2 mb-3">
        <div class="col-md-3">
            <select name="document_type_id" class="form-select">
                <option value="">All Types</option>
                @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        @unless(is_landlord_plan_user())
        <div class="col-md-2">
            <input type="date" name="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <input type="date" name="to_date" class="form-control">
        </div>
        @endunless
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary">Filter</button>
            <button type="button" class="btn btn-secondary documents-reset">Reset</button>
        </div>
    </form>
    {{-- LIST --}}
    {{-- <div class="documents-list"></div> --}}
    {{-- Render initial list server-side: --}}
    <div class="documents-list">
        @include('components.backend.documents._documents_list', [
            'documents'     => $initialDocuments,
            'documentTypes' => $documentTypes
        ])
    </div>

    <div class="modal fade" id="documentDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="document-delete-message mb-0">Delete this document? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDocumentDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL for Add/Edit/View --}}
    <div class="modal fade" id="documentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentsModalLabel">Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Form will be appended here dynamically -->
                </div>
            </div>
        </div>
    </div>

</div>
