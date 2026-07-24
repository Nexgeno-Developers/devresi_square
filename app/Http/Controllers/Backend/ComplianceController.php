<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\ComplianceType;
use App\Models\ComplianceRecord;
use App\Models\ComplianceDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ComplianceController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        ? ComplianceRecord::with('complianceType', 'complianceDetails')->findOrFail($complianceRecordId)
        : null;

        // Pass the complianceDetails data to the view to pre-fill form fields
        $complianceDetails = $complianceRecord ? $complianceRecord->complianceDetails->keyBy('key') : [];

        $heading = $complianceRecord ? 'EDIT ' . convert_to_uppercase(beautify_string($complianceType->alias))
                                     : 'ADD ' . convert_to_uppercase(beautify_string($complianceType->alias));

        $content = view('backend.compliance.' . $complianceType->alias . '._form', compact('complianceType', 'complianceRecord', 'complianceDetails'))->render();

        return response()->json(['heading' => $heading, 'content' => $content]);
    }


    public function storeCompliance(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'compliance_type_id' => 'required|exists:compliance_types,id',
            'property_id' => 'required|exists:properties,id',
            'expiry_date' => 'required|date',
            'photos' => 'nullable|string', // Validate as a string (e.g., "1,2")
        ]);

        // Handle the photos field (either uploaded photos or a comma-separated string of photo IDs)
        $photos = $validated['photos'] ? explode(',', $validated['photos']) : [];

        // Convert the array of photos back to a comma-separated string
        $photosString = implode(',', $photos);

        // Store data in `compliance_records`
        $complianceRecord = ComplianceRecord::create([
            'compliance_type_id' => $validated['compliance_type_id'],
            'property_id' => $validated['property_id'],
            'expiry_date' => $validated['expiry_date'],
            'photos' => $photosString,
        ]);

        // Handle dynamic fields (e.g., "rating")
        $dynamicFields = $request->except([
            '_token',
            'compliance_type_id',
            'property_id',
            'expiry_date',
            'photos',
        ]);

        foreach ($dynamicFields as $key => $value) {
            ComplianceDetail::create([
                'compliance_record_id' => $complianceRecord->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Compliance record stored successfully.',
        ]);
    }

    public function updateCompliance(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'record_id' => 'required|exists:compliance_records,id',
            'compliance_type_id' => 'required|exists:compliance_types,id',
            'property_id' => 'required|exists:properties,id',
            'expiry_date' => 'required|date',
            'photos' => 'nullable|string',
        ]);

        // Find the existing compliance record by ID
        $complianceRecordId = $validated['record_id'];
        $complianceRecord = ComplianceRecord::findOrFail($complianceRecordId);

        // Handle the photos field (either uploaded photos or a comma-separated string of photo IDs)
        $photos = $validated['photos'] ? implode(',', explode(',', $validated['photos'])) : $complianceRecord->photos;

        // Update the compliance record with validated data
        $complianceRecord->update([
            'compliance_type_id' => $validated['compliance_type_id'],
            'property_id' => $validated['property_id'],
            'expiry_date' => $validated['expiry_date'],
            'photos' => $photos,
        ]);

        // Handle dynamic fields (e.g., "rating")
        $dynamicFields = $request->except([
            '_token',
            'record_id',  // Do not process 'record_id' in dynamic fields
            'compliance_type_id',
            'property_id',
            'expiry_date',
            'photos',
        ]);

        // Update or create new compliance details for dynamic fields
        foreach ($dynamicFields as $key => $value) {
            // Check if this dynamic field already exists, and update it; otherwise, create a new record
            $complianceDetail = ComplianceDetail::where('compliance_record_id', $complianceRecord->id)
                                                ->where('key', $key)
                                                ->first();

            if ($complianceDetail) {
                // Update existing detail
                $complianceDetail->update(['value' => $value]);
            } else {
                // Create new compliance detail
                ComplianceDetail::create([
                    'compliance_record_id' => $complianceRecord->id,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Compliance record updated successfully.',
        ]);
    }

    public function deleteCompliance($id)
    {
        try {
            $complianceRecord = ComplianceRecord::findOrFail($id);
            $complianceRecord->delete(); // Delete the compliance record
            return response()->json(['success' => true, 'message' => 'Compliance record deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting compliance record.']);
        }
    }


}
