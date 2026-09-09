@props(['document' => null, 'documentTypes', 'documentable'])

<form id="documentsForm" method="POST" action="{{ $document ? route('admin.documents.save', $document->id) : route('admin.documents.save') }}">
    @csrf

    <input type="hidden" name="document_id"       value="{{ $document->id ?? '' }}">
    <input type="hidden" name="documentable_type" value="{{ get_class($documentable) }}">
    <input type="hidden" name="documentable_id" value="{{ $documentable->id }}">

    <div class="mb-3">
        <div class="form-group">
            <label for="documentType" class="form-label">Type</label>
            <select name="document_type_id" id="documentType" class="form-select" required>
                <option value="">Select Type</option>
                @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}" {{ $document && $document->document_type_id == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <!-- Upload Field -->
        <div class="form-group rs_upload_btn">
            <h6 class="sub_title mt-4">Upload Document</h6>
            <div class="media_wrapper2">
                <div class="input-group" data-toggle="aizuploader" data-type="all" data-multiple="true">
                    <label class="col-form-label" for="document">Documents</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input id="document" type="hidden" name="upload_ids" value="{{ isset($document) && $document->upload_ids ? $document->upload_ids : '' }}" class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="form-check">
            <input type="hidden" name="share_with_tenant" value="0">
            <input class="form-check-input" type="checkbox" name="share_with_tenant" id="shareWithTenant" value="1"
                {{ isset($document) && $document->isSharedWithTenant() ? 'checked' : '' }}>
            <label class="form-check-label" for="shareWithTenant">Share with tenant</label>
        </div>
        <div class="form-text">Tenants can download shared files from their portal. Private files stay on this account only.</div>
    </div>

    <button type="submit" class="btn btn-primary float-end">
        {{ $document ? 'Update' : 'Save' }}
    </button>
</form>
