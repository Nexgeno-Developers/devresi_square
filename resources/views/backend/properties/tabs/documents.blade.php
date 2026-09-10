@if(is_landlord_plan_user())
    <p class="pcc-cert-lead">Tenancy agreements, inventories and files you share with the tenant. Energy and safety certificates are on the Certificates tab.</p>
@else
    <h1>Documents</h1>
@endif
<x-backend-documents-component
    :documentable-type="$property ? get_class($property) : null"
    :documentable-id="$property->id"
    :document-types="$documentTypes"
    :initial-documents="$documents"
    :can-upload-documents="$canUploadDocuments ?? true"
/>
