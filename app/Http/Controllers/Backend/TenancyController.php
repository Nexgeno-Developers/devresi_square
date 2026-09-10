<?php

namespace App\Http\Controllers\Backend;

use App\Models\Tenancy;
use App\Models\Property;
use App\Models\Offer;
use App\Models\User;
use App\Models\TenantMember;
use App\Models\TenancyType;
use App\Models\TenancySubStatus;
use App\Models\PropertyManagerTenancy;
use App\Models\SysSaleInvoice;
use App\Models\EmailTemplate;
use App\Mail\MailManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Enums\CrmNotificationEvent;
use App\Services\Notifications\CrmNotificationService;
use Illuminate\Support\Facades\Gate;

class TenancyController
{
    public function updateRightToRent(Request $request, Tenancy $tenancy, TenantMember $member)
    {
        abort_unless((int) $tenancy->account_id === (int) current_account_id(), 404);
        abort_unless((int) $member->tenancy_id === (int) $tenancy->id, 404);
        $validated = $request->validate([
            'right_to_rent_required' => ['nullable', 'boolean'],
            'right_to_rent_checked_at' => ['nullable', 'date'],
            'right_to_rent_follow_up_due_at' => ['nullable', 'date', 'after:right_to_rent_checked_at'],
        ]);
        $member->update([
            'right_to_rent_required' => $request->boolean('right_to_rent_required'),
            'right_to_rent_checked_at' => $validated['right_to_rent_checked_at'] ?? null,
            'right_to_rent_follow_up_due_at' => $validated['right_to_rent_follow_up_due_at'] ?? null,
        ]);

        return back()->with('success', 'Right to rent follow-up record updated.');
    }


    // Display a list of all active tenancies for a specific property
    public function index($propertyId)
    {
        $property = Property::findOrFail($propertyId);
        ensureModelBelongsToCurrentAccount($property);

        Gate::authorize('viewAny', Tenancy::class);

        $tenancies = Tenancy::where('property_id', $propertyId)
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->where('status', 'Active')
            ->get();

        return view('backend.properties.tabs.tenancy', compact('tenancies', 'propertyId'));
    }

    // Global tenancies listing
    public function all(Request $request)
    {
        Gate::authorize('viewAny', Tenancy::class);

        $query = Tenancy::with(['property', 'tenantMembers.user', 'tenancySubStatus'])
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenancies = $query->paginate(20);
        return view('backend.tenancies.index', compact('tenancies'));
    }

    // Show the form for creating a new tenancy
    public function create(Request $request)
    {
        Gate::authorize('create', Tenancy::class);

        $tenants = $this->usersWithRoleForCurrentAccount('Tenant')->get();
        $property_managers = $this->usersWithRoleForCurrentAccount('Property Manager')->get();
        $tenancyTypes = TenancyType::all();
        $tenancySubStatuses = TenancySubStatus::all();
        $propertyId = $request->query('property_id');
        if ($propertyId) {
            $property = Property::findOrFail($propertyId);
            ensureModelBelongsToCurrentAccount($property);
        }

        $properties = Property::query()
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->orderBy('line_1')
            ->get();

        $payload = compact('tenants', 'property_managers', 'tenancyTypes', 'tenancySubStatuses', 'propertyId', 'properties');

        if ($request->ajax()) {
            return view('backend.tenancies._create-form', $payload);
        }

        return view('backend.tenancies.create', $payload);
    }


    // Store a newly created tenancy
    public function store(Request $request)
    {
        Gate::authorize('create', Tenancy::class);

        // Validate the incoming request
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'offer_id' => 'nullable|exists:offers,id',
            'status' => 'required|in:Active,Archived,Archive',
            // 'sub_status' => 'nullable|string|max:255',
            'move_in' => 'required|date',
            'move_out' => 'nullable|date',
            'tenancy_renewal_confirm_date' => 'nullable|date', // Assuming it's a date format
            'extension_date' => 'nullable|date',
            'rent' => 'required|numeric', // Renamed from 'price' to 'rent' to match the model
            'deposit' => 'required|numeric',
            'deposit_type' => 'nullable|string|max:255', // Adjusting validation based on possible values for 'deposit_type'
            'deposit_number' => 'nullable|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'tenancy_sub_status_id' => 'nullable|exists:tenancy_sub_statuses,id', // Assuming foreign key relationship
            'tenancy_type_id' => 'nullable|exists:tenancy_types,id', // Assuming foreign key relationship
            'deposit_held_by' => 'nullable|string|max:255',
            'deposit_service' => 'nullable|string|max:255',
            'tds_dps_number' => 'nullable|string|max:155',
            'reference_number' => 'nullable|string|max:155',
            'deposit_scheme' => 'nullable|string|max:155',
            // 'periodic' => 'nullable|boolean', // Assuming it's a boolean field
            // 'rolling_contract' => 'nullable|boolean', // Assuming it's a boolean field
            // 'renewal_exempt' => 'nullable|boolean', // Assuming it's a boolean field
            'term_months' => 'nullable|integer',
            'term_days' => 'nullable|integer',
            'deposit_received_at' => 'nullable|date',
            'deposit_protected_at' => 'nullable|date',
            'prescribed_information_sent_at' => 'nullable|date',
            'written_terms_sent_at' => 'nullable|date',
            'user_id' => 'required|array', // Validate that the user_id is an array
            'user_id.*' => 'exists:users,id', // Ensure each user_id exists in the users table

            'is_main_person' => [
                'required',
                'integer',
                Rule::in($request->input('user_id', [])),
            ],
            'property_manager' => 'nullable|array', // Ensure property_manager is an array (nullable)
            'property_manager.*' => 'exists:users,id', // Ensure each property manager exists in the users table
        ]);

        if (($validated['status'] ?? null) === 'Archive') {
            $validated['status'] = 'Archived';
        }

        // Manually convert checkbox field
        // set it to true; if not, set to false
        $validated['periodic'] = $request->has('periodic') ? true : false;
        $validated['rolling_contract'] = $request->has('rolling_contract') ? true : false;
        $validated['renewal_exempt'] = $request->has('renewal_exempt') ? true : false;

        $property = Property::findOrFail($validated['property_id']);
        ensureModelBelongsToCurrentAccount($property);
        $validated['account_id'] = current_account_id();
        $this->ensureRoleUsersAreAccessible($validated['user_id'], 'Tenant', 'user_id');
        $this->ensureRoleUsersAreAccessible($validated['property_manager'] ?? [], 'Property Manager', 'property_manager');
        $this->ensureOfferMatchesProperty($validated['offer_id'] ?? null, $property);

        // If the new tenancy is Active, archive any current active tenancy for the same property.
        if ($validated['status'] === 'Active') {
            Tenancy::where('property_id', $validated['property_id'])
                ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                ->where('status', 'Active')
                ->update(['status' => 'Archived']);
        }
        
        // Create a new tenancy
        $tenancy = Tenancy::create($validated);

        // Attach property managers to the tenancy if provided
        if ($request->has('property_manager')) {
            foreach ($request->property_manager as $propertyManagerId) {
                PropertyManagerTenancy::create([
                    'tenancy_id' => $tenancy->id,
                    'property_manager_id' => $propertyManagerId,
                    'property_id' => $validated['property_id'], // Ensure property_id is passed in the form
                ]);
            }
        }

        // Generate a unique group ID (e.g., GROUP_1, GROUP_2)
        $groupId = 'GROUP_' . $tenancy->id;

        // Store multiple TenantMember records
        foreach ($request->user_id as $userId) {
            // Determine if the user is the main person
            $isMainPerson = $userId == $request->is_main_person;
            TenantMember::create([
                'account_id' => $validated['account_id'],
                'tenancy_id' => $tenancy->id,
                'user_id' => $userId,
                'is_main_person' => $isMainPerson,
                // 'is_main_person' => false, // Default or based on logic, set is_main_person flag
                'group_id' => $groupId, // Set group_id if necessary
            ]);
        }

        // ── Feature 1: Add property_id to each tenant's selected_properties ──
        $property = Property::find($validated['property_id']);
        foreach ($request->user_id as $userId) {
            $tenantUser = User::find($userId);
            if ($tenantUser) {
                $existing = is_array($tenantUser->selected_properties)
                    ? $tenantUser->selected_properties
                    : (json_decode($tenantUser->selected_properties ?? '[]', true) ?? []);
                if (!in_array((int) $validated['property_id'], $existing)) {
                    $existing[] = (int) $validated['property_id'];
                    $tenantUser->update(['selected_properties' => json_encode($existing)]);
                }
            }
        }

        // ── Feature 2: Send tenancy details email to each tenant ──
        $template = EmailTemplate::getByIdentifier('tenant_welcome');
        foreach ([] as $userId) {
            $tenantUser = User::find($userId);
            if (!$tenantUser) continue;

            $placeholders = [
                'tenant_name'      => $tenantUser->name ?? $tenantUser->email,
                'tenant_email'     => $tenantUser->email,
                'property_name'    => $property->prop_name ?? $property->line_1 ?? 'N/A',
                'property_address' => trim(implode(', ', array_filter([
                    $property->line_1, $property->line_2,
                    $property->city, $property->postcode,
                ]))),
                'move_in_date'     => $validated['move_in'] ?? 'N/A',
                'rent'             => '£' . number_format((float)($validated['rent'] ?? 0), 2),
                'login_url'        => url('/admin/login'),
                'crm_name'         => config('app.name'),
                'admin_email'      => config('mail.from.address'),
            ];

            try {
                if ($template) {
                    $renderedHtml = $template->replace($placeholders, ['login_url']);
                    $subject = render_template($template->subject, $placeholders);
                } else {
                    $subject = 'Your Tenancy at ' . $placeholders['property_name'];
                    $renderedHtml = "<p>Hi {$placeholders['tenant_name']},</p>"
                        . "<p>You have been added as a tenant for <strong>{$placeholders['property_name']}</strong>.</p>"
                        . "<p>Move-in: {$placeholders['move_in_date']} | Rent: {$placeholders['rent']}</p>"
                        . "<p><a href='{$placeholders['login_url']}'>Login here</a></p>";
                }

                Mail::to($tenantUser->email)->send(new MailManager([
                    'subject'     => $subject,
                    'content'     => $renderedHtml,
                    'attachments' => [],
                ]));

                Log::info("Tenant tenancy email sent to {$tenantUser->email}");
            } catch (\Exception $e) {
                Log::error("Failed to send tenant tenancy email to {$tenantUser->email}: {$e->getMessage()}");
            }
        }

        $tenancy->load('tenantMembers.user', 'propertyManagers', 'property');
        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::TenancyActivated,
            $tenancy,
            [
                'account_id' => $tenancy->account_id,
                'property_address' => $property->full_address ?: $property->prop_name,
                'move_in_date' => optional($tenancy->move_in)->format('d M Y'),
                'rent' => '£'.number_format((float) $tenancy->rent, 2),
                'action_url' => route('admin.tenancies.show', $tenancy->id),
                'milestone' => 'activated-'.$tenancy->id,
            ],
            auth()->user(),
        );

        flash("Tenancy Added successfully!")->success();
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Tenancy Added successfully!']);
        }

        return redirect()->route('admin.tenancies.all');
    }

    // Display the specified tenancy
    public function show($id)
    {
        // $tenancy = Tenancy::findOrFail($id);
        // Find the tenancy with all needed relationships
        $tenancy = Tenancy::with([
            'property',
            'offer',
            'tenantMembers' => fn ($query) => $query->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($memberQuery) => $memberQuery->forAccount(current_account_id())
            )->with('user'),
            'tenancyType',
            'tenancySubStatus',
            'propertyManagers' => fn ($query) => $query->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($userQuery) => $userQuery->forAccount(current_account_id())
            ),
        ])->findOrFail($id);
        ensureModelBelongsToCurrentAccount($tenancy);
        Gate::authorize('view', $tenancy);

        return view('backend.tenancies.show', compact('tenancy'));
    }

    public function rentLedger($id)
    {
        $tenancy = Tenancy::with([
            'property',
            'tenantMembers' => fn ($query) => $query->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($memberQuery) => $memberQuery->forAccount(current_account_id())
            )->with('user'),
            'tenancyType',
            'tenancySubStatus',
            'propertyManagers' => fn ($query) => $query->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($userQuery) => $userQuery->forAccount(current_account_id())
            ),
        ])->findOrFail($id);
        ensureModelBelongsToCurrentAccount($tenancy);

        $tenantUserIds = $tenancy->tenantMembers
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $invoices = SysSaleInvoice::query()
            ->with([
                'payments' => function ($query) {
                    $query->where(function ($paymentQuery) {
                        $paymentQuery->whereNull('is_voided')->orWhere('is_voided', false);
                    })->with(['paymentMethod', 'bankAccount'])->orderBy('payment_date')->orderBy('id');
                },
                'chargeTo',
                'user',
            ])
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->where(function ($query) use ($tenancy, $tenantUserIds) {
                $query->where(function ($direct) use ($tenancy) {
                    $direct->where('link_to_type', 'Tenancy')
                        ->where('link_to_id', $tenancy->id);
                });

                if (!empty($tenantUserIds) && $tenancy->property_id) {
                    $query->orWhere(function ($propertyLinked) use ($tenancy, $tenantUserIds) {
                        $propertyLinked->where('link_to_type', 'Property')
                            ->where('link_to_id', $tenancy->property_id)
                            ->whereIn('charge_to_id', $tenantUserIds);
                    });
                }
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $invoiceRows = $invoices->map(function (SysSaleInvoice $invoice) use ($tenancy) {
            $paid = (float) $invoice->payments->sum('amount');
            $total = (float) ($invoice->total_amount ?? 0);
            $balance = $invoice->balance_amount === null
                ? max(0, $total - $paid)
                : max(0, (float) $invoice->balance_amount);

            return [
                'invoice' => $invoice,
                'paid' => $paid,
                'balance' => $balance,
                'source' => $invoice->link_to_type === 'Tenancy' && (int) $invoice->link_to_id === (int) $tenancy->id
                    ? 'Tenancy'
                    : 'Property',
                'latest_payment_date' => $invoice->payments->max('payment_date'),
            ];
        });

        $payments = $invoiceRows
            ->flatMap(function (array $row) {
                return $row['invoice']->payments->map(function ($payment) use ($row) {
                    return [
                        'invoice' => $row['invoice'],
                        'payment' => $payment,
                    ];
                });
            })
            ->sortByDesc(fn (array $row) => $row['payment']->payment_date . '-' . str_pad((string) $row['payment']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        $latestPayment = $payments->first();

        $summary = [
            'invoice_count' => $invoices->count(),
            'total_invoiced' => (float) $invoices->sum(fn (SysSaleInvoice $invoice) => (float) ($invoice->total_amount ?? 0)),
            'total_paid' => (float) $invoiceRows->sum('paid'),
            'balance' => (float) $invoiceRows->sum('balance'),
            'latest_payment_date' => $latestPayment ? $latestPayment['payment']->payment_date : null,
        ];

        if ($summary['invoice_count'] === 0) {
            $summary['status'] = 'Not Invoiced';
            $summary['status_class'] = 'secondary';
        } elseif ($summary['total_invoiced'] > 0 && $summary['balance'] <= 0.0001) {
            $summary['status'] = 'Paid';
            $summary['status_class'] = 'success';
        } elseif ($summary['total_paid'] > 0 && $summary['balance'] > 0) {
            $summary['status'] = 'Partial';
            $summary['status_class'] = 'warning';
        } else {
            $summary['status'] = 'Due';
            $summary['status_class'] = 'danger';
        }

        return view('backend.tenancies.rent-ledger', compact('tenancy', 'invoiceRows', 'payments', 'summary'));
    }

    // Show the form for editing the specified tenancy
    public function edit($id)
    {
        // Find the tenancy by its ID
        $tenancy = Tenancy::findOrFail($id);
        ensureModelBelongsToCurrentAccount($tenancy);
        Gate::authorize('update', $tenancy);

        // Fetch related data needed for the edit form

        // Get all tenants (users where category_id is 3)
        // $tenants = User::where('category_id', 3)->get();

        // Get all property managers (users where category_id is 2)
        // $property_managers = User::where('category_id', 2)->get();

        // Get all tenants (users with role 'tenant')
        $tenants = $this->usersWithRoleForCurrentAccount('Tenant')->get();

        // Get all property managers (users with role 'property_manager')
        $property_managers = $this->usersWithRoleForCurrentAccount('Property Manager')->get();

        // Fetch all tenancy types
        $tenancyTypes = TenancyType::all();

        // Fetch all tenancy sub-statuses
        $tenancySubStatuses = TenancySubStatus::all();

        // Fetch the tenancy's current property managers
        $currentPropertyManagers = PropertyManagerTenancy::where('tenancy_id', $id)
            ->whereIn('property_manager_id', $property_managers->pluck('id'))
            ->pluck('property_manager_id')
            ->toArray();

        // Fetch the tenancy's current tenant members
        $tenantMembers = TenantMember::where('tenancy_id', $id)
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->get();

        // Find the main person from the tenant members
        $mainPersonId = $tenantMembers->where('is_main_person', true)->pluck('user_id')->first();

        $properties = Property::query()
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->orderBy('line_1')
            ->get();

        if (request()->ajax()) {
            return view('backend.tenancies.edit', compact(
                'tenancy',
                'tenants',
                'property_managers',
                'tenancyTypes',
                'tenancySubStatuses',
                'currentPropertyManagers',
                'tenantMembers',
                'mainPersonId',
                'properties'
            ));
        }

        return view('backend.tenancies.edit-page', compact(
            'tenancy',
            'tenants',
            'property_managers',
            'tenancyTypes',
            'tenancySubStatuses',
            'currentPropertyManagers',
            'tenantMembers',
            'mainPersonId',
            'properties'
        ));
    }


    // Update the specified tenancy
    public function update(Request $request, $id)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'offer_id' => 'nullable|exists:offers,id',
            'status' => 'required|in:Active,Archived,Archive',
            'move_in' => 'required|date',
            'move_out' => 'nullable|date',
            'tenancy_renewal_confirm_date' => 'nullable|date', // Assuming it's a date format
            'extension_date' => 'nullable|date',
            'rent' => 'required|numeric', // Renamed from 'price' to 'rent' to match the model
            'deposit' => 'required|numeric',
            'deposit_type' => 'nullable|string|max:255', // Adjusting validation based on possible values for 'deposit_type'
            'deposit_number' => 'nullable|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'tenancy_sub_status_id' => 'nullable|exists:tenancy_sub_statuses,id', // Assuming foreign key relationship
            'tenancy_type_id' => 'nullable|exists:tenancy_types,id', // Assuming foreign key relationship
            'deposit_held_by' => 'nullable|string|max:255',
            'deposit_service' => 'nullable|string|max:255',
            'tds_dps_number' => 'nullable|string|max:155',
            'reference_number' => 'nullable|string|max:155',
            'deposit_scheme' => 'nullable|string|max:155',
            'term_months' => 'nullable|integer',
            'term_days' => 'nullable|integer',
            'deposit_received_at' => 'nullable|date',
            'deposit_protected_at' => 'nullable|date',
            'prescribed_information_sent_at' => 'nullable|date',
            'written_terms_sent_at' => 'nullable|date',
            'user_id' => 'required|array', // Validate that the user_id is an array
            'user_id.*' => 'exists:users,id', // Ensure each user_id exists in the users table

            'is_main_person' => [
                'required',
                'integer',
                Rule::in($request->input('user_id', [])),
            ],
            'property_manager' => 'nullable|array', // Ensure property_manager is an array (nullable)
            'property_manager.*' => 'exists:users,id', // Ensure each property manager exists in the users table
        ]);

        if (($validated['status'] ?? null) === 'Archive') {
            $validated['status'] = 'Archived';
        }

        // Manually convert checkbox field
        $validated['periodic'] = $request->has('periodic') ? true : false;
        $validated['rolling_contract'] = $request->has('rolling_contract') ? true : false;
        $validated['renewal_exempt'] = $request->has('renewal_exempt') ? true : false;

        // Find the existing tenancy record
        $tenancy = Tenancy::findOrFail($id);
        ensureModelBelongsToCurrentAccount($tenancy);
        Gate::authorize('update', $tenancy);

        $property = Property::findOrFail($validated['property_id']);
        ensureModelBelongsToCurrentAccount($property);
        $validated['account_id'] = current_account_id();
        $this->ensureRoleUsersAreAccessible($validated['user_id'], 'Tenant', 'user_id');
        $this->ensureRoleUsersAreAccessible($validated['property_manager'] ?? [], 'Property Manager', 'property_manager');
        $this->ensureOfferMatchesProperty($validated['offer_id'] ?? null, $property);

        // Update the tenancy record
        $tenancy->update($validated);

        // Sync property managers for the tenancy
        if ($request->has('property_manager')) {
            // First, remove existing property managers
            PropertyManagerTenancy::where('tenancy_id', $tenancy->id)->delete();

            // Attach new property managers
            foreach ($request->property_manager as $propertyManagerId) {
                PropertyManagerTenancy::create([
                    'tenancy_id' => $tenancy->id,
                    'property_manager_id' => $propertyManagerId,
                    'property_id' => $validated['property_id'], // Ensure property_id is passed in the form
                ]);
            }
        }

        // Update TenantMember records
        // First, remove all existing tenant members
        TenantMember::where('tenancy_id', $tenancy->id)->delete();

        // Store new TenantMember records
        $groupId = 'GROUP_' . $tenancy->id; // Regenerate group_id
        foreach ($request->user_id as $userId) {
            // Determine if the user is the main person
            $isMainPerson = $userId == $request->is_main_person;
            TenantMember::create([
                'account_id' => $validated['account_id'],
                'tenancy_id' => $tenancy->id,
                'user_id' => $userId,
                'is_main_person' => $isMainPerson,
                'group_id' => $groupId, // Set group_id if necessary
            ]);
        }

        $tenancy->refresh()->load('tenantMembers.user', 'propertyManagers', 'property');
        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::TenancyUpdated,
            $tenancy,
            [
                'account_id' => $tenancy->account_id,
                'property_address' => $property->full_address ?: $property->prop_name,
                'move_in_date' => optional($tenancy->move_in)->format('d M Y'),
                'action_url' => route('admin.tenancies.show', $tenancy->id),
                'milestone' => 'updated-'.$tenancy->updated_at?->timestamp,
            ],
            auth()->user(),
        );

        flash("Tenancy updated successfully!")->success();
        return back();
    }


    // Remove the specified tenancy from storage
    public function destroy($id)
    {
        $tenancy = Tenancy::findOrFail($id);
        ensureModelBelongsToCurrentAccount($tenancy);
        Gate::authorize('delete', $tenancy);
        $propertyId = $tenancy->property_id;
        $tenancy->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.properties.index', ['property_id' => $propertyId, 'tabname' => 'tenancy'])
            ->with('success', 'Tenancy deleted successfully!');
    }

    private function usersWithRoleForCurrentAccount(string $role)
    {
        return User::role($role)
            ->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($query) => $query->forAccount(current_account_id())
            )
            ->orderBy('name');
    }

    private function ensureRoleUsersAreAccessible(array $userIds, string $role, string $field): void
    {
        if (auth()->user()?->hasRole('Super Admin') || empty($userIds)) {
            return;
        }

        $requestedIds = collect($userIds)->map(fn ($id) => (int) $id)->unique()->values();
        $accessibleIds = $this->usersWithRoleForCurrentAccount($role)
            ->whereKey($requestedIds)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id);

        if ($requestedIds->diff($accessibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => ["One or more selected {$role} users do not belong to your subscriber account."],
            ]);
        }
    }

    private function ensureOfferMatchesProperty(int|string|null $offerId, Property $property): void
    {
        if (! $offerId) {
            return;
        }

        $offerExists = Offer::whereKey($offerId)
            ->where('property_id', $property->id)
            ->exists();

        if (! $offerExists) {
            throw ValidationException::withMessages([
                'offer_id' => ['The selected offer does not belong to this property.'],
            ]);
        }
    }
}
