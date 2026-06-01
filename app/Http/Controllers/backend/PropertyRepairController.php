<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use App\Models\Invoice;
use App\Models\JobType;
use App\Models\RepairAssignment;
use App\Models\RepairCategory;
use App\Models\RepairHistory;
use App\Models\RepairIssue;
use App\Models\RepairIssueUser;
use App\Models\RepairIssueContractorAssignment;
use App\Models\RepairIssuePropertyManager;
use App\Models\RepairPhoto;
use App\Models\TaxRates;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PropertyRepairController
{
    public function repairRaise()
    {
        $categories = RepairCategory::with(['subCategories', 'parentCategory'])  // Pass as an array of relationships
            ->whereNull('parent_id')
            ->orderBy('level')
            ->orderBy('position')
            ->get();

        // Get the maximum level in the table
        $maxLevel = RepairCategory::max('level');

        return view('backend.repair.create_raise_issue', compact('categories', 'maxLevel'));
    }

    public function getSubCategories($categoryId)
    {
        // var_dump($categoryId);
        $subCategories = RepairCategory::where('parent_id', $categoryId)
            ->orderBy('level')
            ->orderBy('position')
            ->get();

        if ($subCategories->isEmpty()) {
            return response()->json(['message' => 'No subcategories found'], 200);
        }
        return response()->json($subCategories);
    }

    public function getCategories()
    {
        // Fetch all categories with id, name, parent_id, and level
        $categories = RepairCategory::all(['id', 'name', 'parent_id', 'level']);

        // Organize categories into a hierarchical structure (group by parent_id)
        $categoriesByParent = [];

        // Loop through the categories to group them by parent_id
        foreach ($categories as $category) {
            $categoriesByParent[$category->parent_id][] = $category;
        }

        // Return categories as a JSON response, including their hierarchical structure
        return response()->json($categoriesByParent);
    }

    public function checkLastStep(Request $request)
    {
        // Get selected categories from the request
        $selectedCategories = $request->input('selectedCategories');

        // Ensure the selectedCategories array is not empty
        if (empty($selectedCategories)) {
            return response()->json([
                'isLastStep' => false,
                'message' => 'No categories selected.'
            ]);
        }

        // Get the last selected category ID and its corresponding level
        $lastCategoryId = end($selectedCategories);
        $lastCategory = RepairCategory::find($lastCategoryId);

        if (!$lastCategory) {
            return response()->json([
                'isLastStep' => false,
                'message' => 'Invalid category selected.'
            ]);
        }

        $currentLevel = $lastCategory->level;

        // Check if there are any categories with this category as a parent (level + 1)
        $hasSubcategories = RepairCategory::where('parent_id', $lastCategoryId)
            ->where('level', $currentLevel + 1)
            ->exists();

        return response()->json([
            'isLastStep' => !$hasSubcategories,  // If no subcategories exist, it's the last step
            'message' => $hasSubcategories ? 'Subcategories available.' : 'No further subcategories.'
        ]);
    }

    public function index(Request $request)
    {
        // $query = RepairIssue::query();

        // Eager load all defined relationships
        $query = RepairIssue::with([
            'property',
            'repairAssignments',
            'repairHistories',
            'repairIssueUsers',
            'repairPhotos',
            'repairCategory',
            'repairIssuePropertyManagers',
            'repairIssueContractorAssignments',
            'finalContractor',
            'tenant',
            'workOrder',
            'invoice',
        ]);
        $categories = RepairCategory::all();
        $maxLevel = RepairCategory::max('level');
        // $propertyManagers = User::whereHas('category', callback: function ($query) {
        //     $query->where('id', 2);
        // })->get();        
        // $contractors = User::whereHas('category', callback: function ($query) {
        //     $query->where('name', 'Contractor');
        // })->get();
        $propertyManagers = User::role('Property Manager')->get();
        $contractors = User::role('Contractor')->get();
        $jobTypes = JobType::getHierarchy();
        // Apply search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('property', function ($q) use ($search) {
                $q
                    ->where('prop_name', 'LIKE', "%$search%")
                    ->orWhere('prop_ref_no', 'LIKE', "%$search%")
                    ->orWhere('reference_number', 'LIKE', "%$search%");
            });
        }

        // Apply status filter
        if ($request->has('status') && in_array($request->status, [
            'Pending', 'Reported', 'Under Process', 'Work Completed', 'Invoice Received', 'Invoice Paid', 'Closed'
        ])) {
            $query->where('status', $request->status);
        }

        $repairIssues = $query->paginate(10);

        // Check if it's an AJAX request
        if ($request->ajax()) {
            $selectedRepairId = $repairIssues->first()?->id ?? null;
            return view('backend.repair.list.cards', compact('repairIssues', 'selectedRepairId'))->render();
        }

        // Auto load the first repair issue if not an AJAX request and there is at least one issue
        $firstRepairIssue = null;
        $assignedManagers = null;
        $contractorAssignments = null;
        if (!$request->ajax() && $repairIssues->count() > 0) {
            $firstRepairIssue = $repairIssues->first();
            $assignedManagers = RepairIssuePropertyManager::where('repair_issue_id', $firstRepairIssue->id)->pluck('property_manager_id')->toArray();
            $contractorAssignments = RepairIssueContractorAssignment::where('repair_issue_id', $firstRepairIssue->id)->get();
        }

        // if ($request->ajax()) {
        //     return view('backend.repair.index', [
        //         'repairIssues' => $repairIssues,
        //         'entity' => 'repair',
        //     ])->render();
        // }
        return view('backend.repair.index', [
            'repairIssues' => $repairIssues,
            'entity' => 'repair',
            'firstRepairIssue' => $firstRepairIssue,
            'categories' => $categories,
            'maxLevel' => $maxLevel,
            'propertyManagers' => $propertyManagers,
            'assignedManagers' => $assignedManagers,
            'contractorAssignments' => $contractorAssignments,
            'contractors' => $contractors,
            'jobTypes' => $jobTypes,
        ]);

        // return view('backend.repair.index', [
        //     'repairIssues' => $repairIssues,
        //     'entity' => 'repair',
        //     'firstRepairIssue' => $firstRepairIssue,
        //     'categories' => $categories,
        //     'maxLevel' => $maxLevel,
        // ]);
        // return view('backend.repair.index', compact('repairIssues'));
    }

    /*
     * public function index()
     * {
     *     $repairIssues = RepairIssue::paginate(10);
     *     return view('backend.repair.index', compact('repairIssues'));
     * }
     */

    // Show a single repair issue
    public function show(Request $request, $id)
    {
        // Load the repair issue with relationships if needed
        $repairIssue = RepairIssue::with([
            'repairAssignments',
            'repairHistories',
            'repairIssueUsers',
            'repairPhotos',
            'property',  // Eager load the related property
            'invoice',
        ])->findOrFail($id);
        $categories = RepairCategory::all();
        $maxLevel = RepairCategory::max('level');
        // $propertyManagers = User::whereHas('category', callback: function ($query) {
        // $query->where('id', 2);
        // })->get();
        $propertyManagers = User::role('Property Manager')->get();
        $assignedManagers = RepairIssuePropertyManager::where('repair_issue_id', $id)->pluck('property_manager_id')->toArray();
        $contractorAssignments = RepairIssueContractorAssignment::where('repair_issue_id', $id)->get();
        // $contractors = User::whereHas('category', callback: function ($query) {
        //     $query->where('name', 'Contractor');
        // })->get();
        $contractors = User::role('Contractor')->get();
        $jobTypes = JobType::getHierarchy();

        // Return partial HTML if request is AJAX (from jQuery)
        if ($request->ajax()) {
            return view('backend.repair.detail.show', data: compact(
                'repairIssue',
                'categories',
                'maxLevel',
                'propertyManagers',
                'assignedManagers',
                'contractorAssignments',
                'contractors',
                'jobTypes',
            ));
        }

        return view('backend.repair.view_raise_issue', data: compact(
            'repairIssue',
            'categories',
            'maxLevel',
            'propertyManagers',
            'assignedManagers',
            'contractorAssignments',
            'contractors',
            'jobTypes',
        ));
    }

    /*
     * public function show($id)
     * {
     *         // Load the repair issue with relationships if needed
     *         $repairIssue = RepairIssue::with([
     *             'repairAssignments',
     *             'repairHistories',
     *             'repairIssueUsers',
     *             'repairPhotos',
     *             'property' // Eager load the related property
     *         ])->findOrFail($id);
     *         return view('backend.repair.view_raise_issue', compact('repairIssue'));
     *     }
     */

    // Show the form for editing a repair issue
    // public function edit($id)
    // {
    //     $repairIssue = RepairIssue::findOrFail($id);
    //     return view('backend.repair.edit_raise_issue', compact('repairIssue'));
    // }
    public function edit($id)
    {
        // Load the repair issue with relationships if needed
        $repairIssue = RepairIssue::with([
            'repairAssignments',
            'repairHistories',
            'repairIssueUsers',
            'repairPhotos',
            'property',
            'workOrder'
            // 'workOrders'
        ])->findOrFail($id);

        // Load additional data for the form:
        $categories = RepairCategory::all();  // or get only the top-level categories for step2
        // Get the maximum level in the table
        $maxLevel = RepairCategory::max('level');
        // $propertyManagers = User::ofRole('property_manager')->get();
        // $propertyManagers = User::whereHas('category', callback: function ($query) {
        //     $query->where('id', 2);
        // })->get();
        $propertyManagers = User::role('Property Manager')->get();
        // dd($repairIssue->repairPhotos);

        $assignedManagers = RepairIssuePropertyManager::where('repair_issue_id', $id)->pluck('property_manager_id')->toArray();
        $contractorAssignments = RepairIssueContractorAssignment::where('repair_issue_id', $id)->get();
        // $contractors = User::whereHas('category', callback: function ($query) {
        //     $query->where('name', 'Contractor');
        // })->get();
        // $contractors = User::whereHas('role', function ($query) {
        //     $query->where('name', 'contractor');
        // })->get();
        $contractors = User::role('Contractor')->get();

        $jobTypes = JobType::getHierarchy();

        return view('backend.repair.edit_raise_issue', data: compact(
            'repairIssue',
            'categories',
            'maxLevel',
            'propertyManagers',
            'assignedManagers',
            'contractorAssignments',
            'contractors',
            'jobTypes',
        ));
    }

    // Update the specified repair issue
    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'repair_category_id' => 'required',
    //         'description' => 'required',
    //     ]);

    //     $repairIssue = RepairIssue::findOrFail($id);
    //     $repairIssue->repair_category_id = $request->repair_category_id;
    //     $repairIssue->description = $request->description;
    //     $repairIssue->status = $request->status ?? $repairIssue->status; // Keep the current status if not updated
    //     $repairIssue->save();

    //     flash('Repair issue updated successfully')->success();
    //     return redirect()->route('admin.repairs.index');
    // }
    public function update(Request $request, $id)
    {
        // Use Validator::make() to validate input.
        $validator = Validator::make($request->all(), [
            'property_id' => 'required',  // May come as an array; we'll extract a scalar below.
            'repair_navigation' => 'nullable',  // Expected as a JSON string.
            'repair_category_id' => 'nullable|integer|exists:repair_categories,id',
            'repair_navigation_old' => 'nullable',  // Expected as a JSON string.
            'repair_category_id_old' => 'nullable|integer|exists:repair_categories,id',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:Pending,Reported,Under Process,Work Completed,Invoice Received,Invoice Paid,Closed',
            'tenant_availability' => 'nullable|date_format:Y-m-d\TH:i',
            'access_details' => 'nullable|string',
            'estimated_price' => 'required|numeric',
            'vat_type' => 'required|in:inclusive,exclusive',
            'vat_percentage' => 'required_if:vat_type,exclusive|numeric',  // VAT percentage is required if VAT type is 'exclusive'
            'property_managers' => 'required|array',
            'tenant_id' => 'nullable',
            'repair_photos' => 'nullable|string',  // The input is a string of IDs
            'repair_photos.*' => 'nullable|integer|exists:uploads,id',  // Validate each ID
            'final_contractor_id' => 'nullable|integer|exists:users,id',
            // Note: Contractor assignments are validated via dynamic rules.
        ]);

        // Check for validation failure.
        // if ($validator->fails()) {
        //     return redirect()->back()
        //                     ->withErrors($validator)
        //                     ->withInput();
        // }
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();  // Get all errors as an array

            foreach ($errorMessages as $error) {
                flash($error)->error();  // Flash each error separately
            }

            return back()->withInput();
        }
        // Get validated data.
        $validated = $validator->validated();

        // Retrieve the repair issue record.
        $repairIssue = RepairIssue::findOrFail($id);

        // Process property_id: If it's a JSON-encoded array, decode it first.
        $propertyId = $validated['property_id'];
        if (!is_array($propertyId)) {
            $decoded = json_decode($propertyId, true);
            if (is_array($decoded)) {
                $propertyId = $decoded;
            }
        }

        if (is_array($propertyId)) {
            $propertyId = (int) reset($propertyId);
        } else {
            $propertyId = (int) $propertyId;
        }

        // Assuming there's a pivot table for many-to-many relationship
        if ($request->has('repair_photos')) {
            // Get the comma-separated list of photo IDs
            $photoIds = $request->input('repair_photos');

            // Assuming you want to update the 'photos' column in the repair_photos table
            $repairIssue->repairPhotos()->update(['photos' => $photoIds]);
        }

        // dd($propertyId);
        // Capture the original status before updating.
        $oldStatus = $repairIssue->status;

        // Get new values from the validated request
        $repairNavigation = $validated['repair_navigation'] ?? null;
        $repairCategoryId = $validated['repair_category_id'] ?? null;
        $finalContractorId = $validated['final_contractor_id'] ?? null;

        // If new values are not provided, use the old values
        if (empty($repairNavigation) || $repairNavigation == '{}') {
            $repairNavigation = $request->input('repair_navigation_old');
        }

        if (empty($repairCategoryId)) {
            $repairCategoryId = $request->input('repair_category_id_old');
        }

        // Update the main repair issue record.
        $repairIssue->update([
            'property_id' => $propertyId,
            'repair_navigation' => $repairNavigation,  // using new value if provided or original value
            'repair_category_id' => $repairCategoryId,  // using new value if provided or original value
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'tenant_availability' => $validated['tenant_availability'] ?? null,
            'access_details' => $validated['access_details'] ?? null,
            'estimated_price' => $validated['estimated_price'],
            'vat_type' => $validated['vat_type'],
            'vat_percentage' => $validated['vat_percentage'],
            'final_contractor_id' => $finalContractorId,
        ]);
        // Update property manager assignments:
        RepairIssuePropertyManager::where('repair_issue_id', $id)->delete();
        if ($request->has('property_managers')) {
            $assignedBy = Auth::id();
            if (!$assignedBy) {
                abort(403, 'Unauthorized: No user logged in.');
            }
            foreach ($request->input('property_managers') as $managerId) {
                RepairIssuePropertyManager::create([
                    'repair_issue_id' => $id,
                    'property_manager_id' => $managerId,
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                ]);
            }
        }

        // Update contractor assignments:
        $submittedAssignments = $request->input('contractor_assignments', []);

        foreach ($submittedAssignments as $index => $assignmentData) {
            if (!isset($assignmentData['id']) || empty($assignmentData['id'])) {
                RepairIssueContractorAssignment::create([
                    'repair_issue_id' => $repairIssue->id,
                    'contractor_id' => $assignmentData['contractor_id'],
                    'cost_price' => $assignmentData['cost_price'],
                    'assigned_by' => Auth::id(),
                    'quote_attachment' => $assignmentData['quote_attachment'] ?? null,
                    'contractor_preferred_availability' => $assignmentData['contractor_preferred_availability'] ?? null,
                    'status' => 'Proposed',
                ]);
            } else {
                RepairIssueContractorAssignment::where('id', $assignmentData['id'])
                    ->update([
                        'contractor_id' => $assignmentData['contractor_id'],
                        'cost_price' => $assignmentData['cost_price'],
                        'assigned_by' => Auth::id(),
                        'quote_attachment' => $assignmentData['quote_attachment'] ?? null,
                        'contractor_preferred_availability' => $assignmentData['contractor_preferred_availability'] ?? null,
                    ]);
            }
        }

        // Update tenant selection if provided.
        if ($request->filled('tenant_id')) {
            $repairIssue->tenant_id = $request->input('tenant_id');
            $repairIssue->save();
        }

        if ($oldStatus != $validated['status']) {
            // --- Record a History Entry ---
            RepairHistory::create([
                'repair_issue_id' => $id,
                'action' => 'Updated repair issue',
                'previous_status' => $oldStatus,
                'new_status' => $validated['status'],
            ]);

            // // --- Send Notifications ---
            // // Assuming you have a Notification class: App\Notifications\RepairIssueUpdated
            // // Gather users to notify. For example, notify assigned property managers and admin.
            // $usersToNotify = [];
            // // Notify property managers assigned to this repair issue.
            // foreach ($repairIssue->repairIssuePropertyManagers as $assignment) {
            //     if ($assignment->propertyManager) {
            //         $usersToNotify[] = $assignment->propertyManager;
            //     }
            // }
            // // Optionally, add admin users. For example, if admin has id 1:
            // $adminUser = User::find(1);
            // if ($adminUser) {
            //     $usersToNotify[] = $adminUser;
            // }
            // // Remove duplicate users.
            // $usersToNotify = array_unique($usersToNotify);
            // // Send notification.
            // foreach ($usersToNotify as $user) {
            //     $user->notify(new \App\Notifications\RepairIssueUpdated([
            //         'repair_issue_id' => $id,
            //         'message'       => "Repair issue updated from {$oldStatus} to {$validated['status']}"
            //     ]));
            // }
        }

        return redirect()
            ->route('admin.property_repairs.index')
            ->with('success', 'Repair issue updated successfully.');
    }

    // Remove the specified repair issue
    public function destroy($id)
    {
        $repairIssue = RepairIssue::findOrFail($id);
        $repairIssue->delete();

        flash('Repair issue deleted successfully')->success();
        return redirect()->route('admin.property_repairs.index');
    }

    public function raiseIssueStore(Request $request)
    {
        $request->validate([
            'property_id' => 'required',  // Ensure it's an array with at least one item
            'repair_category_id' => 'required|integer|exists:repair_categories,id',
            'repair_navigation' => 'required|json',
            'description' => 'required|string',
        ]);

        // Decode JSON categories
        $categories = json_decode($request->repair_navigation, true);

        // dd([
        //     'original_categories' => $request->repair_navigation,
        //     'converted_categories' => $categories,
        // ]);

        // Decode `property_id` if it's a stringified array (e.g., "[5]")
        $propertyId = $request->property_id;

        if (is_string($propertyId) && str_starts_with($propertyId, '[') && str_ends_with($propertyId, ']')) {
            $propertyId = json_decode($propertyId, true);  // Convert JSON string to PHP array
        }

        // If it's an array, extract the first value
        if (is_array($propertyId)) {
            $propertyId = reset($propertyId);
        }

        // Ensure it's a valid integer
        $propertyId = (int) $propertyId;

        // dd([
        //     'original_property_id' => $request->property_id,
        //     'converted_property_id' => $propertyId,
        // ]);

        // Generate Reference Number using a private function
        // $repairReference = $this->generateRepairReferenceNumber();
        $repairReference = generateReferenceNumber(RepairIssue::class, 'reference_number', 'RESISQRPR');

        // Store repair request
        $repair = RepairIssue::create([
            'property_id' => $propertyId,
            'repair_navigation' => json_encode($categories),
            'repair_category_id' => $request->repair_category_id,
            'description' => $request->description,
            'status' => 'Pending',
            'reference_number' => $repairReference,  // Store the reference number
        ]);

        // Store repair photos
        if ($request->has('repair_photos')) {
            RepairPhoto::create([
                'photos' => $request->repair_photos,
                'repair_issue_id' => $repair->id,
                'photo_type' => 'jpg',
            ]);
        }

        flash('Repair request raised successfully')->success();
        return redirect()->route('admin.property_repairs.create');
    }

    // public function update(Request $request, $id)
    // {
    //     $repairIssue = RepairIssue::findOrFail($id);
    //     $repairIssue->update($request->all());
    //     return redirect()->route('repairs.index');
    // }

    public function assignRepair(Request $request, $repairIssueId)
    {
        $repairAssignment = new RepairAssignment();
        $repairAssignment->repair_issue_id = $repairIssueId;
        $repairAssignment->assigned_to = $request->assigned_to;
        $repairAssignment->assigned_at = now();
        $repairAssignment->status = 'assigned';
        $repairAssignment->save();

        return redirect()->route('repairs.index');
    }

    public function createHistory($repairIssueId, $action)
    {
        RepairHistory::create([
            'repair_issue_id' => $repairIssueId,
            'action' => $action,
            'previous_status' => 'pending',  // Example
            'new_status' => 'in-progress',  // Example
        ]);

        return redirect()->route('repairs.index');
    }

    public function getPropertyTenants(Request $request)
    {
        // Get the property_id from the request.
        // Note: The property ID may be passed as an array; if so, we take the first element.
        $propertyId = $request->input('property_id');
        if (is_array($propertyId)) {
            $propertyId = (int) reset($propertyId);
        } else {
            $propertyId = (int) $propertyId;
        }

        // Retrieve tenancy IDs for the given property.
        $tenancyIds = Tenancy::where('property_id', $propertyId)
            ->pluck('id')
            ->toArray();

        // Retrieve tenant members associated with those tenancies, with their user details.
        $tenantMembers = TenantMember::whereIn('tenancy_id', $tenancyIds)
            ->with('user')
            ->get();

        // Map the results to a unique list of tenants.
        $tenants = $tenantMembers
            ->map(function ($member) {
                if ($member->user) {
                    return [
                        'id' => $member->user->id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'phone' => $member->user->phone,
                    ];
                }
                return null;
            })
            ->filter()  // Remove any null entries.
            ->unique('id')  // Ensure unique tenant users.
            ->values();  // Reset the keys.

        return response()->json($tenants);
    }

    /**
     * Generate a unique and sequential repair reference number.
     *
     * @return string
     */
    // Generate a unique reference number
    // private function generateRepairReferenceNumber()
    // {
    //     // Find the last inserted property
    //     $lastProperty = RepairIssue::orderBy('id', 'desc')->first();
    //     // Extract and increment the numeric part
    //     if ($lastProperty && preg_match('/RESISQREP(\d+)/', $lastProperty->reference_number, $matches)) {
    //         $number = (int)$matches[1] + 1;
    //     } else {
    //         $number = 1; // Start from 1 if no property exists
    //     }
    //     // Format the new reference number (e.g., RESISQREP0000001)
    //     return 'RESISQREP' . str_pad($number, 7, '0', STR_PAD_LEFT);
    // }
    public function workOrderInvoice($repairId)
    {
        // Fetch the repair details
        $repairIssue = RepairIssue::findOrFail($repairId);

        // Fetch work order
        $workorder = WorkOrder::where('repair_issue_id', $repairIssue->id)->first();
        $invoice = $workorder ? Invoice::where('work_order_id', $workorder->id)->first() : null;

        // Determine mode
        $mode = (!$workorder || !$invoice) ? 'create' : 'edit';

        // Fetch necessary data
        $jobTypes = JobType::getHierarchy();
        $users = User::all();
        $taxRates = TaxRates::all();

        // Contractor assignment details
        $contractorAssignment = RepairIssueContractorAssignment::where('repair_issue_id', $repairIssue->id)
            ->where('contractor_id', $repairIssue->final_contractor_id)
            ->first();

        $contractorCost = $contractorAssignment->cost_price ?? 0;
        $quoteAttachment = $contractorAssignment->quote_attachment ?? null;

        // Pass data to the view
        return view('backend.repair.workorder-invoice', compact('repairIssue', 'workorder', 'invoice', 'users', 'taxRates', 'jobTypes', 'contractorCost', 'quoteAttachment', 'mode'));
    }

    public function loadForm(Request $request)
    {
        $repairIssue = RepairIssue::with([
            'property',
            'repairCategory',
            'repairPhotos',
            'repairAssignments',
            'repairIssuePropertyManagers',
            'repairIssueContractorAssignments',
            'repairHistories',
            'repairIssueUsers',
            'finalContractor',
            'tenant',
            'workOrder',
            'invoice'
        ])->find($request->repair_id);
        // Load additional data for the form:
        $categories = RepairCategory::all();  // or get only the top-level categories for step2
        // Get the maximum level in the table
        $maxLevel = RepairCategory::max('level');
        $formType = $request->form_type;

        if (!$repairIssue) {
            return response()->json(['error' => 'repair not found'], 404);
        }

        $viewPath = "backend.repair.popup_forms.$formType";

        if (!view()->exists($viewPath)) {
            return response()->json(['error' => 'Invalid form type'], 400);
        }
        $property = $repairIssue->property;
        $extraData = $this->getFormTypeExtras($formType, $repairIssue);
        // Merge the additional form data into the extra data array
        $extraData = array_merge($extraData, [
            'categories' => $categories,
            'maxLevel'   => $maxLevel,
        ]);
        $html = view($viewPath, array_merge(['repairIssue' => $repairIssue], ['property' => $property], ['editMode' => true], $extraData))->render();

        return response()->json(['success' => true, 'form_html' => $html]);
    }


    public function saveForm(Request $request)
    {
        $id = $request->input('repair_id');
        $repairIssue = RepairIssue::find($id);
        $formType = $request->input('form_type');

        if (!$repairIssue) {
            return response()->json(['error' => 'repair not found'], 404);
        }

        $extraData = [];  // <-- This prevents undefined variable errors

        // Save the form data based on the form type
        switch ($formType) {
            case 'property_details':

                // Process property_id: If it's a JSON-encoded array, decode it first.
                $propertyId = $request->input('property_id');
                if (!is_array($propertyId)) {
                    $decoded = json_decode($propertyId, true);
                    if (is_array($decoded)) {
                        $propertyId = $decoded;
                    }
                }

                if (is_array($propertyId)) {
                    $propertyId = (int) reset($propertyId);
                } else {
                    $propertyId = (int) $propertyId;
                }

                $data = [
                    'property_id' => $propertyId,
                ];

                // $data = $request->only([
                //     'property_id'
                // ]);
                break;
            case 'property_issue_details':
                // Get the values from the request (could be empty)
                $repairNavigation = $request->input('repair_navigation');
                $repairCategoryId = $request->input('repair_category_id');

                // Fallback to old values if new values are empty or '{}'
                if (empty($repairNavigation) || $repairNavigation === '{}') {
                    $repairNavigation = $request->input('repair_navigation_old');
                }

                if (empty($repairCategoryId)) {
                    $repairCategoryId = $request->input('repair_category_id_old');
                }
                // Update tenant selection if provided.
                if ($request->filled('tenant_id')) {
                    $tenant_id = $request->input('tenant_id');
                   
                }
                
                // Assuming there's a pivot table for many-to-many relationship
                if ($request->has('repair_photos')) {
                    // Get the comma-separated list of photo IDs
                    $photoIds = $request->input('repair_photos');

                    // Assuming you want to update the 'photos' column in the repair_photos table
                    $repairIssue->repairPhotos()->update(['photos' => $photoIds]);
                }
                
                // Get the rest of the values directly
                $data = [
                    'repair_navigation' => $repairNavigation,
                    'repair_category_id' => $repairCategoryId,
                    'description' => $request->input('description'),
                    'priority' => $request->input('priority'),
                    'sub_status' => $request->input('status'),
                    'status' => $request->input('status'),
                    'tenant_availability' => $request->input('tenant_availability'),
                    'access_details' => $request->input('access_details'),
                    'estimated_price' => $request->input('estimated_price'),
                    'vat_type' => $request->input('vat_type'),
                    'vat_percentage' => $request->input('vat_percentage'),
                    'tenant_id' => $tenant_id,
                ];
                break;
            case 'manager_assign':
                
                // Update property manager assignments:
                RepairIssuePropertyManager::where('repair_issue_id', $id)->delete();
                if ($request->has('property_managers')) {
                    $assignedBy = Auth::id();
                    if (!$assignedBy) {
                        abort(403, 'Unauthorized: No user logged in.');
                    }
                    foreach ($request->input('property_managers') as $managerId) {
                        RepairIssuePropertyManager::create([
                            'repair_issue_id' => $id,
                            'property_manager_id' => $managerId,
                            'assigned_by' => $assignedBy,
                            'assigned_at' => now(),
                        ]);
                    }
                }
                
                $data = $request->only([
                    'property_managers'
                ]);
                // $extraData = $this->getFormTypeExtras($formType, $repair);
                break;
            case 'contractor_assign':

                // Update contractor assignments:
                $submittedAssignments = $request->input('contractor_assignments', []);

                foreach ($submittedAssignments as $index => $assignmentData) {
                    if (!isset($assignmentData['id']) || empty($assignmentData['id'])) {
                        RepairIssueContractorAssignment::create([
                            'repair_issue_id' => $repairIssue->id,
                            'contractor_id' => $assignmentData['contractor_id'],
                            'cost_price' => $assignmentData['cost_price'],
                            'assigned_by' => Auth::id(),
                            'quote_attachment' => $assignmentData['quote_attachment'] ?? null,
                            'contractor_preferred_availability' => $assignmentData['contractor_preferred_availability'] ?? null,
                            'status' => 'Proposed',
                        ]);
                    } else {
                        RepairIssueContractorAssignment::where('id', $assignmentData['id'])
                            ->update([
                                'contractor_id' => $assignmentData['contractor_id'],
                                'cost_price' => $assignmentData['cost_price'],
                                'assigned_by' => Auth::id(),
                                'quote_attachment' => $assignmentData['quote_attachment'] ?? null,
                                'contractor_preferred_availability' => $assignmentData['contractor_preferred_availability'] ?? null,
                            ]);
                    }
                }

                $data = $request->only([
                    ''
                ]);
                break;
            case 'final_contractor':
                $data = $request->only([
                    'final_contractor_id'
                ]);
                break;
            case 'repair_history':
                $status = $request->input('status');
                // Capture the original status before updating.
                $oldStatus = $repairIssue->status;
                if ($oldStatus != $status) {
                    // --- Record a History Entry ---
                    RepairHistory::create([
                        'repair_issue_id' => $id,
                        'action' => 'Updated repair issue',
                        'previous_status' => $oldStatus,
                        'new_status' => $status,
                    ]);
                }

                $data = $request->only([
                    'parking', 'parking_location', 'service', 'pets_allow'
                ]);
                break;
            default:
                return response()->json(['message' => 'Invalid form type'], 400);
        }

        // Handle different form types dynamically
        // if ($formType === 'availability_pricing') {
        //     $repair->available_from = $request->input('available_from');
        //     $repair->price = $request->input('price');
        //     $repair->letting_price = $request->input('letting_price');
        // } elseif ($formType === 'some_other_form') {
        //     // Handle other form types dynamically
        //     $repair->some_field = $request->input('some_field');
        // }

        $repairIssue->update($data);

        // 🛠️ Fix: Re-fetch related data like school/station names
        $extraData = $this->getFormTypeExtras($formType, $repairIssue);

        // Render updated section
        $updatedView = view("backend.repair.popup_forms.$formType", array_merge(['repairIssue' => $repairIssue], $extraData))->render();
        // $updatedView = view("backend.repair.popup_forms.$formType", compact('repair'))->render();

        return response()->json([
            'success' => 'Form updated successfully',
            'updated_html' => $updatedView
        ]);
    }

    private function getFormTypeExtras($formType, $repair)
    {
        if ($formType === 'any') {
            // Fetch all stations and schools
            // $allstations = StationName::select('id', 'name')->get();
            // $allschools = SchoolName::select('id', 'name')->get();

            // // Get the nearest station and school IDs from the repair (comma-separated)
            // $stationIds = explode(',', $property->nearest_station);
            // $schoolIds = explode(',', $property->nearest_school);

            // // Fetch names using IDs
            // $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
            // $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

            // return compact('allstations', 'allschools', 'stations', 'schools');
        }

        return [];
    }

    public function ajaxList(Request $request)
    {
        $term = $request->input('q');

        $query = RepairIssue::query();

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('reference_number', 'like', "%$term%")
                ->orWhere('description', 'like', "%$term%")
                ->orWhere('status', 'like', "%$term%");
            });
        }

        $repairIssues = $query
            ->select('id', 'reference_number', 'description', 'status')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $results = $repairIssues->map(function ($issue) {
            $shortDesc = mb_strimwidth($issue->description, 0, 15, '...');
            return [
                'id' => $issue->id,
                'text' => "{$issue->reference_number} - {$shortDesc}, {$issue->status}",
            ];
        });

        return response()->json(['results' => $results]);
    }
}
