<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\ComplianceType;
use App\Models\ComplianceRecord;
use App\Models\ComplianceDetail;
use App\Models\Document;
use App\Models\Property;
use App\Models\Upload;
use App\Services\Documents\DocumentShareNotifier;
use App\Services\SecureUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Enums\CrmNotificationEvent;
use App\Services\Notifications\CrmNotificationService;

class ComplianceController
{
    /**
     * Display a listing of the resource.
     */
    public function certificates()
    {
        $accountId = (int) current_account_id();
        abort_unless($accountId, 403);

        $rows = ComplianceRecord::certificateGapsForAccount($accountId);
        $propertyCount = Property::query()->forAccount($accountId)->count();
        $typeCount = ComplianceType::query()->whereIn('alias', ['gas', 'epc', 'eicr'])->count();

        return view('backend.compliance.certificates', [
            'rows' => $rows,
            'propertyCount' => $propertyCount,
            'typeCount' => $typeCount,
        ]);
    }

    public function index()
    {
        return $this->certificates();
    }

    public function shareCertificate(Request $request, DocumentShareNotifier $notifier)
    {
        $accountId = (int) current_account_id();
        abort_unless($accountId, 403);

        $validated = $request->validate([
            'property_id' => 'required|integer',
            'compliance_type_id' => 'required|integer',
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $property = Property::query()->forAccount($accountId)->findOrFail($validated['property_id']);
        $type = ComplianceType::query()
            ->whereIn('alias', ['gas', 'epc', 'eicr'])
            ->findOrFail($validated['compliance_type_id']);

        $stored = app(SecureUploadService::class)->store($request->file('certificate'));
        $upload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => pathinfo($stored['original_name'], PATHINFO_FILENAME) ?: $type->name,
            'extension' => $stored['extension'],
            'file_name' => $stored['path'],
            'user_id' => $request->user()->id,
            'type' => $stored['type'],
            'file_size' => $stored['size'],
        ]);

        $document = Document::create([
            'account_id' => $accountId,
            'documentable_type' => $property->getMorphClass(),
            'documentable_id' => $property->id,
            'upload_ids' => (string) $upload->id,
            'title' => $type->name,
            'visibility' => 'portal',
            'created_by' => $request->user()->id,
        ]);

        $record = ComplianceRecord::query()
            ->where('property_id', $property->id)
            ->where('compliance_type_id', $type->id)
            ->orderByDesc('id')
            ->first();

        if ($record) {
            $record->forceFill([
                'served_to_tenant_at' => now(),
                'photos' => (string) $upload->id,
            ])->save();
        } else {
            ComplianceRecord::create([
                'property_id' => $property->id,
                'compliance_type_id' => $type->id,
                'photos' => (string) $upload->id,
                'served_to_tenant_at' => now(),
                'status' => 'shared',
            ]);
        }

        $notifier->shared($document->fresh());

        flash($type->name.' is shared with the tenant.')->success();

        return redirect()->route('admin.compliance.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getComplianceForm($complianceTypeId, $complianceRecordId = null)
    {
        $complianceType = ComplianceType::findOrFail($complianceTypeId);
        $complianceRecord = $complianceRecordId
        ? $this->complianceRecordsForCurrentAccount()
            ->with('complianceType', 'complianceDetails')
            ->where('compliance_type_id', $complianceType->id)
            ->findOrFail($complianceRecordId)
        : null;

        // Pass the complianceDetails data to the view to pre-fill form fields
        $complianceDetails = $complianceRecord ? $complianceRecord->complianceDetails->keyBy('key') : [];

        $heading = $complianceRecord
            ? (is_landlord_plan_user() ? 'Update certificate' : 'EDIT ' . convert_to_uppercase(beautify_string($complianceType->alias)))
            : (is_landlord_plan_user() ? 'Upload certificate' : 'ADD ' . convert_to_uppercase(beautify_string($complianceType->alias)));
        $responsibleUsers = current_account()?->users()->orderBy('name')->get() ?? collect();

        $content = view('backend.compliance.' . $complianceType->alias . '._form', compact('complianceType', 'complianceRecord', 'complianceDetails', 'responsibleUsers'))->render();

        return response()->json(['heading' => $heading, 'content' => $content]);
    }


    public function storeCompliance(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'compliance_type_id' => 'required|exists:compliance_types,id',
            'property_id' => 'required|exists:properties,id',
            'issued_date' => 'nullable|date',
            'expiry_date' => 'required|date',
            'photos' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'remediation_due_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'served_to_tenant_at' => 'nullable|date',
            'served_notes' => 'nullable|string|max:1000',
        ]);
        $this->findAccessibleProperty((int) $validated['property_id']);
        $this->ensureUploadsAreAccessible($validated['photos'] ?? null);

        $photos = ! empty($validated['photos']) ? explode(',', $validated['photos']) : [];
        $photosString = implode(',', $photos);

        // Leave completed_at null so expiry reminders can fire until the landlord closes the item.
        $complianceRecord = ComplianceRecord::create([
            'compliance_type_id' => $validated['compliance_type_id'],
            'property_id' => $validated['property_id'],
            'issued_date' => $validated['issued_date'] ?? null,
            'expiry_date' => $validated['expiry_date'],
            'photos' => $photosString,
            'responsible_user_id' => $validated['responsible_user_id'] ?? auth()->id(),
            'remediation_due_at' => $validated['remediation_due_at'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
            'served_to_tenant_at' => $validated['served_to_tenant_at'] ?? null,
            'served_notes' => $validated['served_notes'] ?? null,
        ]);

        $dynamicFields = $request->except([
            '_token',
            'compliance_type_id',
            'property_id',
            'issued_date',
            'expiry_date',
            'photos',
            'responsible_user_id',
            'remediation_due_at',
            'completed_at',
            'served_to_tenant_at',
            'served_notes',
        ]);

        foreach ($dynamicFields as $key => $value) {
            ComplianceDetail::create([
                'compliance_record_id' => $complianceRecord->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        $this->notifyComplianceRenewed($complianceRecord, 'created-'.$complianceRecord->id);

        return response()->json([
            'success' => true,
            'message' => 'Compliance record stored successfully.',
        ]);
    }

    public function updateCompliance(Request $request)
    {
        $validated = $request->validate([
            'record_id' => 'required|exists:compliance_records,id',
            'compliance_type_id' => 'required|exists:compliance_types,id',
            'property_id' => 'required|exists:properties,id',
            'issued_date' => 'nullable|date',
            'expiry_date' => 'required|date',
            'photos' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'remediation_due_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'served_to_tenant_at' => 'nullable|date',
            'served_notes' => 'nullable|string|max:1000',
        ]);

        $complianceRecordId = $validated['record_id'];
        $this->findAccessibleProperty((int) $validated['property_id']);
        $this->ensureUploadsAreAccessible($validated['photos'] ?? null);
        $complianceRecord = $this->complianceRecordsForCurrentAccount()
            ->findOrFail($complianceRecordId);

        $photos = $validated['photos'] ? implode(',', explode(',', $validated['photos'])) : $complianceRecord->photos;

        $complianceRecord->update([
            'compliance_type_id' => $validated['compliance_type_id'],
            'property_id' => $validated['property_id'],
            'issued_date' => $validated['issued_date'] ?? $complianceRecord->issued_date,
            'expiry_date' => $validated['expiry_date'],
            'photos' => $photos,
            'responsible_user_id' => $validated['responsible_user_id'] ?? $complianceRecord->responsible_user_id,
            'remediation_due_at' => array_key_exists('remediation_due_at', $validated)
                ? $validated['remediation_due_at']
                : $complianceRecord->remediation_due_at,
            'completed_at' => array_key_exists('completed_at', $validated)
                ? $validated['completed_at']
                : $complianceRecord->completed_at,
            'served_to_tenant_at' => array_key_exists('served_to_tenant_at', $validated)
                ? $validated['served_to_tenant_at']
                : $complianceRecord->served_to_tenant_at,
            'served_notes' => array_key_exists('served_notes', $validated)
                ? $validated['served_notes']
                : $complianceRecord->served_notes,
        ]);

        $dynamicFields = $request->except([
            '_token',
            'record_id',
            'compliance_type_id',
            'property_id',
            'issued_date',
            'expiry_date',
            'photos',
            'responsible_user_id',
            'remediation_due_at',
            'completed_at',
            'served_to_tenant_at',
            'served_notes',
        ]);

        foreach ($dynamicFields as $key => $value) {
            $complianceDetail = ComplianceDetail::where('compliance_record_id', $complianceRecord->id)
                ->where('key', $key)
                ->first();

            if ($complianceDetail) {
                $complianceDetail->update(['value' => $value]);
            } else {
                ComplianceDetail::create([
                    'compliance_record_id' => $complianceRecord->id,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }

        $this->notifyComplianceRenewed($complianceRecord->fresh(), 'updated-'.$complianceRecord->updated_at?->timestamp);

        return response()->json([
            'success' => true,
            'message' => 'Compliance record updated successfully.',
        ]);
    }

    public function deleteCompliance($id)
    {
        $complianceRecord = $this->complianceRecordsForCurrentAccount()->findOrFail($id);
        $complianceRecord->delete();

        return response()->json(['success' => true, 'message' => 'Compliance record deleted successfully.']);
    }

    private function complianceRecordsForCurrentAccount()
    {
        return ComplianceRecord::query()
            ->whereHas('property', function ($propertyQuery) {
                if (! auth()->user()?->hasRole('Super Admin')) {
                    $propertyQuery->forAccount(current_account_id());
                }
            });
    }

    private function findAccessibleProperty(int $propertyId): Property
    {
        return Property::query()
            ->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($query) => $query->forAccount(current_account_id())
            )
            ->findOrFail($propertyId);
    }

    private function notifyComplianceRenewed(ComplianceRecord $record, string $milestone): void
    {
        $record->load('property', 'complianceType', 'responsibleUser');
        $property = $record->property;
        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::ComplianceRenewed,
            $record,
            [
                'account_id' => $property->account_id ?: current_account_id(),
                'compliance_type' => $record->complianceType?->name ?: 'Compliance record',
                'property_address' => $property->full_address ?: $property->prop_name,
                'due_date' => optional($record->expiry_date)->format('d M Y'),
                'action_url' => route('admin.properties.view', $property->id),
                'milestone' => $milestone,
            ],
            auth()->user(),
        );
    }

    private function ensureUploadsAreAccessible(?string $uploadIds): void
    {
        if (auth()->user()?->hasRole('Super Admin') || blank($uploadIds)) {
            return;
        }

        $requestedIds = collect(explode(',', $uploadIds))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $accessibleIds = Upload::forAccount(current_account_id())
            ->whereKey($requestedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($requestedIds->diff($accessibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'photos' => ['One or more selected files do not belong to your subscriber account.'],
            ]);
        }
    }


}
