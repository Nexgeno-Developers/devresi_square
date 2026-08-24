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
use App\Models\PropertyManagerTenancy;
use App\Models\Property;
use App\Models\TaxRates;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\UserCategory;
use App\Models\WorkOrder;
use App\Enums\CrmNotificationEvent;
use App\Services\Notifications\CrmNotificationService;
use App\Services\Saas\PortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Role;

class PropertyRepairController
{
    public function repairRaise()
    {
        $categories = RepairCategory::with(['subCategories', 'parentCategory'])
            ->whereNull('parent_id')
            ->orderBy('level')
            ->orderBy('position')
            ->get();

        $maxLevel = RepairCategory::max('level');

        // For Tenant users: resolve their linked active-tenancy properties
        $tenantProperties = null;
        if (auth()->user()->hasRole('Tenant')) {
            $tenantProperties = \App\Models\TenantMember::where('user_id', auth()->id())
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenancies.status', 'Active')
                ->join('properties', 'tenancies.property_id', '=', 'properties.id')
                ->select('properties.id', 'properties.prop_name', 'properties.prop_ref_no',
                         'properties.line_1', 'properties.line_2', 'properties.city', 'properties.postcode')
                ->get();
        }

        return view('backend.repair.create_raise_issue', compact('categories', 'maxLevel', 'tenantProperties'));
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
        $this->scopeRepairQuery($query);
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

        // Tenant: only see repair issues for their linked active-tenancy properties
        if (auth()->user()->hasRole('Tenant')) {
            $tenantPropertyIds = TenantMember::where('user_id', auth()->id())
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenancies.status', 'Active')
                ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->where('tenancies.account_id', current_account_id()))
                ->pluck('tenancies.property_id')
                ->unique();
            $query->whereIn('property_id', $tenantPropertyIds);
        }

        $repairIssues = $query->orderByDesc('id')->paginate(10);

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

    public function indexTabbed(Request $request)
    {
        $query = RepairIssue::with([
            'property',
            'repairAssignments',
            'repairHistories',
            'repairIssueUsers',
            'repairPhotos',
            'repairCategory',
            'repairIssuePropertyManagers.propertyManager',
            'repairIssueContractorAssignments.contractor',
            'finalContractor',
            'tenant',
            'workOrder.items',
            'workOrder.jobType',
            'workOrder.jobSubType',
        ]);
        $this->scopeRepairQuery($query);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('property', function ($q) use ($search) {
                $q->where('prop_name', 'LIKE', "%$search%")
                    ->orWhere('prop_ref_no', 'LIKE', "%$search%")
                    ->orWhere('reference_number', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('status') && in_array($request->status, [
            'Pending', 'Reported', 'Under Process', 'Work Completed', 'Invoice Received', 'Invoice Paid', 'Closed'
        ])) {
            $query->where('status', $request->status);
        }

        if (auth()->user()->hasRole('Tenant')) {
            $tenantPropertyIds = TenantMember::where('user_id', auth()->id())
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenancies.status', 'Active')
                ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->where('tenancies.account_id', current_account_id()))
                ->pluck('tenancies.property_id')
                ->unique();
            $query->whereIn('property_id', $tenantPropertyIds);
        }

        $selectedRepairQuery = clone $query;
        $repairIssues = $query->orderByDesc('id')->paginate(10);
        $tabName = $this->normalizeRepairTabName($request->query('tabname', 'issue'));
        $selectedRepairId = $request->query('repair_id');

        if ($request->ajax() && $request->has('list_only')) {
            if ($selectedRepairId && ! $repairIssues->contains('id', (int) $selectedRepairId)) {
                $selectedRepairId = $repairIssues->first()?->id;
            }

            return response()->json([
                'html' => view('backend.repair.list.tabbed-cards', compact('repairIssues', 'selectedRepairId'))->render(),
                'selectedRepairId' => $selectedRepairId,
            ]);
        }

        if (! $selectedRepairId && $repairIssues->count() > 0 && ! $request->ajax()) {
            return redirect()->route('admin.property_repairs.index_tabbed', array_merge(
                $request->except(['repair_id']),
                [
                    'repair_id' => $repairIssues->first()->id,
                    'tabname' => $tabName,
                ]
            ));
        }

        $repairIssue = null;
        if ($selectedRepairId) {
            $repairIssue = $selectedRepairQuery->find($selectedRepairId);
        }

        if (! $repairIssue && $repairIssues->count() > 0) {
            $repairIssue = $repairIssues->first();
            $selectedRepairId = $repairIssue->id;
        }

        $tabs = [
            ['name' => 'Issue', 'key' => 'issue'],
            ['name' => 'Property Manager', 'key' => 'property-manager'],
            ['name' => 'Request a quote', 'key' => 'contractors'],
            ['name' => 'Work Order', 'key' => 'work-order'],
        ];

        $content = $repairIssue
            ? $this->getTabbedRepairContent($tabName, $repairIssue)
            : '<div class="alert alert-info m-3">No repair issues found.</div>';

        if ($request->ajax()) {
            return response()->json([
                'content' => $content,
                'repair_id' => $selectedRepairId,
                'tabname' => $tabName,
            ]);
        }

        return view('backend.repair.index_tabbed', [
            'repairIssues' => $repairIssues,
            'tabs' => $tabs,
            'tabName' => $tabName,
            'selectedRepairId' => $selectedRepairId,
            'repairIssue' => $repairIssue,
            'content' => $content,
        ]);
    }

    private function normalizeRepairTabName(?string $tabName): string
    {
        $tabName = strtolower(str_replace(' ', '-', (string) $tabName));

        return in_array($tabName, ['issue', 'property-manager', 'contractors', 'work-order'])
            ? $tabName
            : 'issue';
    }

    private function getTabbedRepairContent(string $tabName, RepairIssue $repairIssue): string
    {
        $tabName = $this->normalizeRepairTabName($tabName);
        $viewData = ['repairIssue' => $repairIssue];

        if ($tabName === 'work-order') {
            $workorder = WorkOrder::where('repair_issue_id', $repairIssue->id)->with('items')->first();
            $jobTypes = JobType::getHierarchy();
            $taxRates = TaxRates::all();
            $workOrderFinalizeAssignments = RepairIssueContractorAssignment::with('contractor')
                ->where('repair_issue_id', $repairIssue->id)
                ->get();
            $contractorAssignment = $workOrderFinalizeAssignments
                ->where('contractor_id', $repairIssue->final_contractor_id)
                ->first();
            $contractorCost = $contractorAssignment->cost_price ?? 0;
            $quoteAttachment = $contractorAssignment->quote_attachment ?? null;
            $propertyId = $repairIssue->property_id;
            $showInvoiceActions = false;

            $viewData = array_merge($viewData, compact(
                'workorder',
                'jobTypes',
                'taxRates',
                'contractorCost',
                'quoteAttachment',
                'propertyId',
                'showInvoiceActions',
                'workOrderFinalizeAssignments'
            ));
        }

        if ($tabName === 'contractors') {
            $contractors = $this->contractorUsersQuery()->orderBy('name')->get();
            $viewData = array_merge($viewData, compact('contractors'));
        }

        if ($tabName === 'property-manager') {
            $propertyManagers = User::role('Property Manager')->orderBy('name')->get();
            $assignedManagers = RepairIssuePropertyManager::where('repair_issue_id', $repairIssue->id)
                ->pluck('property_manager_id')
                ->toArray();
            $viewData = array_merge($viewData, compact('propertyManagers', 'assignedManagers'));
        }

        return view("backend.repair.tabs.$tabName", $viewData)->render();
    }

    private function contractorUsersQuery()
    {
        $contractorRoleId = Role::where('name', 'Contractor')->value('id');
        $contractorCategoryId = UserCategory::where('name', 'Contractor')->value('id');

        return User::query()
            ->where(function ($query) use ($contractorRoleId, $contractorCategoryId) {
                $query->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Contractor');
                });

                if ($contractorRoleId && Schema::hasColumn('users', 'role_id')) {
                    $query->orWhere('role_id', $contractorRoleId);
                }

                if ($contractorCategoryId) {
                    $query->orWhere('category_id', $contractorCategoryId);
                }
            });
    }

    private function saveQuoteContractor(array $contractorData): User
    {
        $contractorCategoryId = UserCategory::where('name', 'Contractor')->value('id');
        $contractor = User::firstOrNew(['email' => $contractorData['email']]);

        $contractor->fill([
            'first_name' => $contractorData['first_name'] ?? $contractor->first_name,
            'last_name' => $contractorData['last_name'] ?? $contractor->last_name,
            'name' => trim(($contractorData['first_name'] ?? $contractor->first_name ?? '') . ' ' . ($contractorData['last_name'] ?? $contractor->last_name ?? '')) ?: $contractor->name,
            'phone' => $contractorData['phone'] ?? $contractor->phone,
            'address_line_1' => $contractorData['address_line_1'] ?? $contractor->address_line_1,
            'address_line_2' => $contractorData['address_line_2'] ?? $contractor->address_line_2,
            'city' => $contractorData['city'] ?? $contractor->city,
            'postcode' => $contractorData['postcode'] ?? $contractor->postcode,
            'category_id' => $contractorCategoryId ?: $contractor->category_id,
            'created_by' => $contractor->exists ? $contractor->created_by : Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        $contractor->save();

        $role = Role::where('name', 'Contractor')->first();
        if ($role && ! $contractor->hasRole($role->name)) {
            $contractor->assignRole($role);
        }

        return $contractor;
    }

    public function storeQuoteContractor(Request $request)
    {
        $validated = $request->validate([
            'new_contractor.first_name' => 'nullable|string|max:55',
            'new_contractor.last_name' => 'nullable|string|max:55',
            'new_contractor.email' => 'required|email|max:55',
            'new_contractor.phone' => 'nullable|string|max:20',
            'new_contractor.address_line_1' => 'nullable|string|max:255',
            'new_contractor.address_line_2' => 'nullable|string|max:255',
            'new_contractor.city' => 'nullable|string|max:55',
            'new_contractor.postcode' => 'nullable|string|max:15',
        ]);

        $contractor = $this->saveQuoteContractor($validated['new_contractor']);

        return response()->json([
            'message' => 'Contractor saved.',
            'contractor' => [
                'id' => $contractor->id,
                'label' => trim($contractor->name . ' - ' . $contractor->email, ' -'),
            ],
        ]);
    }

    public function sendQuoteRequests(Request $request, RepairIssue $repairIssue)
    {
        ensureModelBelongsToCurrentAccount($repairIssue);

        $validated = $request->validate([
            'contractor_ids' => 'nullable|array',
            'contractor_ids.*' => 'integer|exists:users,id',
            'new_contractor.first_name' => 'nullable|string|max:55',
            'new_contractor.last_name' => 'nullable|string|max:55',
            'new_contractor.email' => 'nullable|email|max:55',
            'new_contractor.phone' => 'nullable|string|max:20',
            'new_contractor.address_line_1' => 'nullable|string|max:255',
            'new_contractor.address_line_2' => 'nullable|string|max:255',
            'new_contractor.city' => 'nullable|string|max:55',
            'new_contractor.postcode' => 'nullable|string|max:15',
        ]);

        $contractorIds = collect($validated['contractor_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $newContractor = $validated['new_contractor'] ?? [];
        if (! empty($newContractor['email'])) {
            $contractor = $this->saveQuoteContractor($newContractor);
            $contractorIds->push($contractor->id);
        }

        if ($contractorIds->isEmpty()) {
            return response()->json(['message' => 'Select or add at least one contractor.'], 422);
        }

        $queued = 0;

        foreach ($contractorIds->unique() as $contractorId) {
            $contractor = $this->contractorUsersQuery()->find($contractorId);
            if (! $contractor) {
                continue;
            }

            $assignment = RepairIssueContractorAssignment::firstOrNew([
                'repair_issue_id' => $repairIssue->id,
                'contractor_id' => $contractor->id,
            ]);

            $assignment->fill([
                'assigned_by' => Auth::id(),
                'status' => 'Quote Requested',
                'quote_token' => $assignment->quote_token ?: Str::random(64),
                'quote_requested_at' => now(),
            ]);
            $assignment->save();

            $quoteUrl = URL::temporarySignedRoute('repair-quotes.show', now()->addDays(14), [
                'assignment' => $assignment->id,
                'token' => $assignment->quote_token,
            ]);
            app(CrmNotificationService::class)->dispatch(
                CrmNotificationEvent::RepairQuoteRequested,
                $repairIssue,
                [
                    'account_id' => $repairIssue->account_id,
                    'recipients' => [$contractor],
                    'repair_reference' => $repairIssue->reference_number,
                    'property_address' => $repairIssue->property?->full_address,
                    'action_url' => $quoteUrl,
                    'quote_url' => $quoteUrl,
                    'attachment_type' => 'repair_scope',
                    'repair_issue_id' => $repairIssue->id,
                    'milestone' => 'quote-request-'.$assignment->id,
                ],
                auth()->user(),
            );
            $queued++;
        }

        return response()->json([
            'message' => "Quote request saved for {$queued} contractor(s). Emails will be sent in the background.",
        ]);
    }

    public function finalizeContractor(Request $request, RepairIssue $repairIssue, RepairIssueContractorAssignment $assignment)
    {
        ensureModelBelongsToCurrentAccount($repairIssue);

        if ((int) $assignment->repair_issue_id !== (int) $repairIssue->id) {
            abort(404);
        }

        if ($repairIssue->final_contractor_id) {
            return response()->json(['message' => 'A final contractor has already been selected for this repair issue.'], 422);
        }

        $repairIssue->update(['final_contractor_id' => $assignment->contractor_id]);
        $assignment->update(['status' => 'Finalized']);
        $workOrder = WorkOrder::where('repair_issue_id', $repairIssue->id)->first();
        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::RepairContractorAssigned,
            $repairIssue->fresh(),
            [
                'account_id' => $repairIssue->account_id,
                'recipients' => [$assignment->contractor_id],
                'repair_reference' => $repairIssue->reference_number,
                'property_address' => $repairIssue->property?->full_address,
                'action_url' => route('admin.property_repairs.show', $repairIssue->id),
                'milestone' => 'contractor-'.$assignment->id,
                'attachment_type' => $workOrder ? 'work_order' : null,
                'work_order_id' => $workOrder?->id,
            ],
            auth()->user(),
        );

        return response()->json(['message' => 'Final contractor selected successfully.']);
    }

    public function scopeOfWorkPdf(RepairIssue $repairIssue)
    {
        ensureModelBelongsToCurrentAccount($repairIssue);

        $startedAt = microtime(true);

        try {
            Log::info('Scope of work PDF request started.', [
                'repair_issue_id' => $repairIssue->id,
                'reference_number' => $repairIssue->reference_number,
            ]);

            $repairIssue->load(['property', 'repairCategory', 'repairPhotos']);

            Log::info('Scope of work PDF relations loaded.', [
                'repair_issue_id' => $repairIssue->id,
                'photo_rows' => $repairIssue->repairPhotos->count(),
            ]);

            $pdf = $this->buildScopeOfWorkPdf($repairIssue);
            $content = $pdf->output();
            $reference = Str::slug($repairIssue->reference_number ?: $repairIssue->id);
            $filename = 'scope-of-work-' . $reference . '.pdf';

            Log::info('Scope of work PDF rendered.', [
                'repair_issue_id' => $repairIssue->id,
                'bytes' => strlen($content),
                'seconds' => round(microtime(true) - $startedAt, 3),
            ]);

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($content),
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'public',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Scope of work PDF failed.', [
                'repair_issue_id' => $repairIssue->id,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }

    private function buildScopeOfWorkPdf(RepairIssue $repairIssue)
    {
        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);

        return Pdf::loadView('backend.repair.pdf.scope_of_work', [
            'repairIssue' => $repairIssue,
        ], [], ['format' => 'A4', 'tempDir' => $tempDir]);
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
        ensureModelBelongsToCurrentAccount($repairIssue);
        $this->ensurePortalCanAccessRepair($repairIssue);

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
        ensureModelBelongsToCurrentAccount($repairIssue);
        $this->ensurePortalCanAccessRepair($repairIssue, 'edit');

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

        // For Tenant: resolve their linked active-tenancy properties
        $tenantProperties = null;
        if (auth()->user()->hasRole('Tenant')) {
            $tenantProperties = \App\Models\TenantMember::where('user_id', auth()->id())
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenancies.status', 'Active')
                ->join('properties', 'tenancies.property_id', '=', 'properties.id')
                ->select('properties.id', 'properties.prop_name', 'properties.prop_ref_no',
                         'properties.line_1', 'properties.line_2', 'properties.city', 'properties.postcode')
                ->get();
        }

        return view('backend.repair.edit_raise_issue', data: compact(
            'repairIssue',
            'categories',
            'maxLevel',
            'propertyManagers',
            'assignedManagers',
            'contractorAssignments',
            'contractors',
            'jobTypes',
            'tenantProperties',
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
        ensureModelBelongsToCurrentAccount($repairIssue);
        $this->ensurePortalCanAccessRepair($repairIssue, 'edit');

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
        $property = Property::findOrFail($propertyId);
        ensureModelBelongsToCurrentAccount($property);

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

        if ($repairIssue->final_contractor_id && (int) $repairIssue->final_contractor_id !== (int) $finalContractorId) {
            $finalContractorId = $repairIssue->final_contractor_id;
        }

        $previousPropertyId = (int) $repairIssue->property_id;

        // Update the main repair issue record.
        $repairIssue->update([
            'property_id' => $propertyId,
            'account_id' => $property->account_id ?: $repairIssue->account_id ?: current_account_id(),
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

        if ($previousPropertyId !== (int) $propertyId) {
            $this->syncRepairIssuePropertyManagers(
                $repairIssue,
                $this->propertyManagerIdsForProperty((int) $propertyId)
            );
        } else {
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
            if (! $repairIssue->acknowledged_at && strtolower((string) $validated['status']) !== 'pending') {
                $repairIssue->forceFill(['acknowledged_at' => now(), 'acknowledged_by' => Auth::id()])->save();
            }
            // --- Record a History Entry ---
            RepairHistory::create([
                'repair_issue_id' => $id,
                'action' => 'Updated repair issue',
                'previous_status' => $oldStatus,
                'new_status' => $validated['status'],
            ]);

            app(CrmNotificationService::class)->dispatch(
                CrmNotificationEvent::RepairStatusChanged,
                $repairIssue->fresh(),
                [
                    'account_id' => $repairIssue->account_id,
                    'repair_reference' => $repairIssue->reference_number,
                    'property_address' => $repairIssue->property?->full_address,
                    'old_status' => $oldStatus,
                    'new_status' => $validated['status'],
                    'action_url' => route('admin.property_repairs.show', $repairIssue->id),
                    'milestone' => 'status-'.$validated['status'].'-'.$repairIssue->updated_at?->timestamp,
                ],
                auth()->user(),
            );

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
        ensureModelBelongsToCurrentAccount($repairIssue);
        $this->ensurePortalCanAccessRepair($repairIssue, 'edit');
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
        $property = Property::findOrFail($propertyId);
        ensureModelBelongsToCurrentAccount($property);
        $this->ensurePortalCanAccessPropertyForRepair($property);

        // dd([
        //     'original_property_id' => $request->property_id,
        //     'converted_property_id' => $propertyId,
        // ]);

        // Generate Reference Number using a private function
        // $repairReference = $this->generateRepairReferenceNumber();
        $repairReference = generateReferenceNumber(RepairIssue::class, 'reference_number', 'RESISQRPR');

        // Store repair request
        $repair = RepairIssue::create([
            'account_id' => $property->account_id ?: current_account_id(),
            'property_id' => $propertyId,
            'repair_navigation' => json_encode($categories),
            'repair_category_id' => $request->repair_category_id,
            'description' => $request->description,
            'status' => 'Pending',
            'reference_number' => $repairReference,  // Store the reference number
        ]);

        $this->syncRepairIssuePropertyManagers(
            $repair,
            $this->propertyManagerIdsForProperty($propertyId)
        );

        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::RepairReported,
            $repair,
            [
                'account_id' => $repair->account_id,
                'include_account_admins' => in_array($repair->priority, ['high', 'critical'], true),
                'repair_reference' => $repair->reference_number,
                'repair_priority' => $repair->priority ?: 'normal',
                'property_address' => $property->full_address ?: $property->prop_name,
                'action_url' => route('admin.property_repairs.show', $repair->id),
                'milestone' => 'reported-'.$repair->id,
            ],
            auth()->user(),
        );

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
        $repairIssue = RepairIssue::findOrFail($repairIssueId);
        ensureModelBelongsToCurrentAccount($repairIssue);

        $repairAssignment = new RepairAssignment();
        $repairAssignment->repair_issue_id = $repairIssue->id;
        $repairAssignment->assigned_to = $request->assigned_to;
        $repairAssignment->assigned_at = now();
        $repairAssignment->status = 'assigned';
        $repairAssignment->save();

        return redirect()->route('repairs.index');
    }

    public function createHistory($repairIssueId, $action)
    {
        $repairIssue = RepairIssue::findOrFail($repairIssueId);
        ensureModelBelongsToCurrentAccount($repairIssue);

        RepairHistory::create([
            'repair_issue_id' => $repairIssue->id,
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

        $property = Property::findOrFail($propertyId);
        ensureModelBelongsToCurrentAccount($property);

        // Retrieve tenancy IDs for the given property.
        $tenancyIds = Tenancy::where('property_id', $propertyId)
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->pluck('id')
            ->toArray();

        // Retrieve tenant members associated with those tenancies, with their user details.
        $tenantMembers = TenantMember::whereIn('tenancy_id', $tenancyIds)
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
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
        ensureModelBelongsToCurrentAccount($repairIssue);

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

        if (!$repairIssue) {
            return response()->json(['error' => 'Repair issue not found'], 404);
        }

        ensureModelBelongsToCurrentAccount($repairIssue);

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

        ensureModelBelongsToCurrentAccount($repairIssue);

        $extraData = [];  // <-- This prevents undefined variable errors
        $previousPropertyId = (int) $repairIssue->property_id;
        $syncManagersFromPropertyId = null;

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

                $property = Property::findOrFail($propertyId);
                ensureModelBelongsToCurrentAccount($property);

                $data = [
                    'property_id' => $propertyId,
                    'account_id' => $property->account_id ?: current_account_id(),
                ];
                if ($previousPropertyId !== (int) $propertyId) {
                    $syncManagersFromPropertyId = (int) $propertyId;
                }

                // $data = $request->only([
                //     'property_id'
                // ]);
                break;
            case 'property_issue_details':
                // Get the values from the request (could be empty)
                $repairNavigation = $request->input('repair_navigation');
                $repairCategoryId = $request->input('repair_category_id');
                $tenant_id = $repairIssue->tenant_id;

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
                if (Auth::user()?->hasRole('Tenant')) {
                    $tenant_id = Auth::id();
                }
                
                // Assuming there's a pivot table for many-to-many relationship
                if ($request->has('repair_photos')) {
                    // Get the comma-separated list of photo IDs
                    $photoIds = $request->input('repair_photos');

                    // Assuming you want to update the 'photos' column in the repair_photos table
                    $repairIssue->repairPhotos()->update(['photos' => $photoIds]);
                }
                
                $user = Auth::user();
                $canEditRepairAdminFields = $user && $user->hasAnyRole(['Super Admin', 'Landlord', 'Estate Agent', 'Agent']);
                $propertyId = $repairIssue->property_id;
                if ($canEditRepairAdminFields && $request->filled('property_id')) {
                    $propertyInput = $request->input('property_id');
                    if (is_array($propertyInput)) {
                        $propertyInput = reset($propertyInput);
                    }
                    $propertyId = (int) $propertyInput;
                }

                $property = Property::findOrFail($propertyId);
                ensureModelBelongsToCurrentAccount($property);

                $priority = $canEditRepairAdminFields ? $request->input('priority', $repairIssue->priority) : $repairIssue->priority;
                $status = $canEditRepairAdminFields ? $request->input('status', $repairIssue->status) : $repairIssue->status;
                $subStatus = $canEditRepairAdminFields ? $request->input('sub_status', $repairIssue->sub_status) : $repairIssue->sub_status;
                $estimatedPrice = $canEditRepairAdminFields ? $request->input('estimated_price', $repairIssue->estimated_price) : $repairIssue->estimated_price;
                $vatType = $canEditRepairAdminFields ? $request->input('vat_type', $repairIssue->vat_type) : $repairIssue->vat_type;
                $vatPercentage = $canEditRepairAdminFields ? $request->input('vat_percentage', $repairIssue->vat_percentage) : $repairIssue->vat_percentage;

                $data = [
                    'property_id' => $propertyId,
                    'account_id' => $property->account_id ?: $repairIssue->account_id ?: current_account_id(),
                    'repair_navigation' => $repairNavigation,
                    'repair_category_id' => $repairCategoryId,
                    'description' => $request->input('description'),
                    'priority' => $priority,
                    'sub_status' => $subStatus,
                    'status' => $status,
                    'tenant_availability' => $request->input('tenant_availability'),
                    'access_details' => $request->input('access_details'),
                    'estimated_price' => $estimatedPrice,
                    'vat_type' => $vatType,
                    'vat_percentage' => $vatPercentage,
                    'tenant_id' => $tenant_id,
                ];
                if ($previousPropertyId !== (int) $propertyId) {
                    $syncManagersFromPropertyId = (int) $propertyId;
                }
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
                
                $data = [];
                // $extraData = $this->getFormTypeExtras($formType, $repair);
                break;
            case 'contractor_assign':

                // Update contractor assignments:
                $submittedAssignments = $request->input('contractor_assignments', []);
                $submittedAssignmentIds = collect($submittedAssignments)
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                RepairIssueContractorAssignment::where('repair_issue_id', $repairIssue->id)
                    ->when(! empty($submittedAssignmentIds), function ($query) use ($submittedAssignmentIds) {
                        $query->whereNotIn('id', $submittedAssignmentIds);
                    })
                    ->delete();

                foreach ($submittedAssignments as $index => $assignmentData) {
                    if (empty($assignmentData['contractor_id'])) {
                        continue;
                    }

                    if (!isset($assignmentData['id']) || empty($assignmentData['id'])) {
                        RepairIssueContractorAssignment::create([
                            'repair_issue_id' => $repairIssue->id,
                            'contractor_id' => $assignmentData['contractor_id'],
                            'cost_price' => $assignmentData['cost_price'] ?? null,
                            'assigned_by' => Auth::id(),
                            'quote_attachment' => $assignmentData['quote_attachment'] ?? null,
                            'contractor_preferred_availability' => $assignmentData['contractor_preferred_availability'] ?? null,
                            'status' => 'Proposed',
                        ]);
                    } else {
                        RepairIssueContractorAssignment::where('id', $assignmentData['id'])
                            ->update([
                                'contractor_id' => $assignmentData['contractor_id'],
                                'cost_price' => $assignmentData['cost_price'] ?? null,
                                'assigned_by' => Auth::id(),
                                'quote_attachment' => $assignmentData['quote_attachment'] ?? null,
                                'contractor_preferred_availability' => $assignmentData['contractor_preferred_availability'] ?? null,
                            ]);
                    }
                }

                $data = [];
                break;
            case 'final_contractor':
                $requestedFinalContractorId = $request->input('final_contractor_id');
                $previousFinalContractorId = $repairIssue->final_contractor_id;
                $data = ['final_contractor_id' => $requestedFinalContractorId ?: null];
                if ($requestedFinalContractorId && (int) $previousFinalContractorId !== (int) $requestedFinalContractorId) {
                    $repairIssue->forceFill($data);
                    $this->sendFinalContractorAssignedEmail($repairIssue, (int) $requestedFinalContractorId);
                }
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

        if ($syncManagersFromPropertyId !== null) {
            $this->syncRepairIssuePropertyManagers(
                $repairIssue,
                $this->propertyManagerIdsForProperty($syncManagersFromPropertyId)
            );
        }

        $repairIssue->refresh()->load(['repairIssuePropertyManagers.propertyManager']);

        if ($formType === 'manager_assign' && $repairIssue->repairIssuePropertyManagers->isNotEmpty()) {
            if (! $repairIssue->acknowledged_at) {
                $repairIssue->forceFill(['acknowledged_at' => now(), 'acknowledged_by' => Auth::id()])->save();
            }
            app(CrmNotificationService::class)->dispatch(CrmNotificationEvent::RepairManagerAssigned, $repairIssue, [
                'account_id' => $repairIssue->account_id,
                'repair_reference' => $repairIssue->reference_number,
                'property_address' => $repairIssue->property?->full_address,
                'action_url' => route('admin.property_repairs.show', $repairIssue->id),
                'milestone' => 'manager-'.collect($repairIssue->repairIssuePropertyManagers)->pluck('property_manager_id')->sort()->implode('-'),
            ], auth()->user());
        }

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
        if ($formType === 'manager_assign') {
            $propertyManagers = User::role('Property Manager')->orderBy('name')->get();
            $assignedManagers = RepairIssuePropertyManager::where('repair_issue_id', $repair->id)
                ->pluck('property_manager_id')
                ->toArray();

            return compact('propertyManagers', 'assignedManagers');
        }

        if ($formType === 'contractor_assign' || $formType === 'final_contractor') {
            $contractors = $this->contractorUsersQuery()->orderBy('name')->get();
            $contractorAssignments = RepairIssueContractorAssignment::where('repair_issue_id', $repair->id)
                ->get();
            $assignedContractorIds = $contractorAssignments
                ->pluck('contractor_id')
                ->filter()
                ->unique()
                ->values();
            $assignedContractorUsers = User::whereIn('id', $assignedContractorIds)->get()->keyBy('id');

            if ($assignedContractorIds->isNotEmpty()) {
                $assignedContractors = $assignedContractorUsers->values();
                $contractors = $contractors
                    ->merge($assignedContractors)
                    ->unique('id')
                    ->sortBy('name')
                    ->values();
            }

            return compact('contractors', 'contractorAssignments', 'assignedContractorUsers');
        }

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

    private function sendFinalContractorAssignedEmail(RepairIssue $repairIssue, int $contractorId): void
    {
        $workOrder = WorkOrder::where('repair_issue_id', $repairIssue->id)->first();
        app(CrmNotificationService::class)->dispatch(CrmNotificationEvent::RepairContractorAssigned, $repairIssue, [
            'account_id' => $repairIssue->account_id,
            'recipients' => [$contractorId],
            'repair_reference' => $repairIssue->reference_number,
            'property_address' => $repairIssue->property?->full_address,
            'action_url' => route('admin.property_repairs.show', $repairIssue->id),
            'milestone' => 'final-contractor-'.$contractorId,
            'attachment_type' => $workOrder ? 'work_order' : null,
            'work_order_id' => $workOrder?->id,
        ], auth()->user());
    }

    private function propertyManagerIdsForProperty(?int $propertyId): array
    {
        if (! $propertyId) {
            return [];
        }

        return PropertyManagerTenancy::where('property_id', $propertyId)
            ->pluck('property_manager_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function syncRepairIssuePropertyManagers(RepairIssue $repairIssue, array $managerIds): void
    {
        RepairIssuePropertyManager::where('repair_issue_id', $repairIssue->id)->delete();

        foreach (array_unique(array_filter($managerIds)) as $managerId) {
            RepairIssuePropertyManager::create([
                'repair_issue_id' => $repairIssue->id,
                'property_manager_id' => $managerId,
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]);
        }
    }

    public function ajaxList(Request $request)
    {
        $term = $request->input('q');

        $query = RepairIssue::query();
        $this->scopeRepairQuery($query);

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

    private function scopeRepairQuery($query)
    {
        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user?->hasRole('Super Admin')) {
            $query->forAccount(current_account_id());
        }

        if ($user && $accountId) {
            $portalAccessService = app(PortalAccessService::class);

            if ($portalAccessService->isPortalUser($user, $accountId)) {
                $propertyIds = $portalAccessService->accessiblePropertyIds($user, $accountId);

                $query->where(function ($repairQuery) use ($propertyIds, $user) {
                    $repairQuery->whereIn('property_id', $propertyIds);

                    if ($user->hasRole('Contractor')) {
                        $repairQuery->orWhere('final_contractor_id', $user->id)
                            ->orWhereHas('repairIssueContractorAssignments', function ($assignmentQuery) use ($user) {
                                $assignmentQuery->where('contractor_id', $user->id);
                            });
                    }
                });
            }
        }

        return $query;
    }

    private function ensurePortalCanAccessRepair(RepairIssue $repairIssue, string $permission = 'view'): void
    {
        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user || ! $accountId) {
            return;
        }

        $portalAccessService = app(PortalAccessService::class);

        if (! $portalAccessService->isPortalUser($user, $accountId)) {
            return;
        }

        if ($permission === 'view' && (int) $repairIssue->final_contractor_id === (int) $user->id) {
            return;
        }

        $property = $repairIssue->property ?: Property::find($repairIssue->property_id);

        abort_unless($property && $portalAccessService->canAccessProperty($user, $property, $permission), 403, 'You do not have access to this repair.');
    }

    private function ensurePortalCanAccessPropertyForRepair(Property $property): void
    {
        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user || ! $accountId) {
            return;
        }

        $portalAccessService = app(PortalAccessService::class);

        if ($portalAccessService->isPortalUser($user, $accountId)) {
            abort_unless($portalAccessService->canAccessProperty($user, $property), 403, 'You do not have access to this property.');
        }
    }
}
