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

class TenancyController
{


    // Display a list of all active tenancies for a specific property
    public function index($propertyId)
    {
        $tenancies = Tenancy::where('property_id', $propertyId)
            ->where('status', 'Active')
            ->get();

        return view('backend.properties.tabs.tenancy', compact('tenancies', 'propertyId'));
    }

    // Global tenancies listing
    public function all(Request $request)
    {
        $query = Tenancy::with(['property', 'tenantMembers.user', 'tenancySubStatus'])
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
        $tenants = User::role('Tenant')->get();
        $property_managers = User::role('Property Manager')->get();
        $tenancyTypes = TenancyType::all();
        $tenancySubStatuses = TenancySubStatus::all();
        $propertyId = $request->query('property_id');

        return view('backend.tenancies.create', compact('tenants', 'property_managers', 'tenancyTypes', 'tenancySubStatuses', 'propertyId'));
    }


    // Store a newly created tenancy
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'offer_id' => 'nullable|exists:offers,id',
            'status' => 'required|in:Active,Archive', // Assuming status is either Active or Archive
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
            'user_id' => 'required|array', // Validate that the user_id is an array
            'user_id.*' => 'exists:users,id', // Ensure each user_id exists in the users table

            'is_main_person' => 'required|exists:users,id', // Ensure main person is a valid user ID
            'property_manager' => 'nullable|array', // Ensure property_manager is an array (nullable)
            'property_manager.*' => 'exists:users,id', // Ensure each property manager exists in the users table
        ]);

        // Manually convert checkbox field
        // set it to true; if not, set to false
        $validated['periodic'] = $request->has('periodic') ? true : false;
        $validated['rolling_contract'] = $request->has('rolling_contract') ? true : false;
        $validated['renewal_exempt'] = $request->has('renewal_exempt') ? true : false;

        // If the new tenancy is Active, archive any current active tenancy for the same property.
        if ($validated['status'] === 'Active') {
            Tenancy::where('property_id', $validated['property_id'])
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
        foreach ($request->user_id as $userId) {
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

        flash("Tenancy Added successfully!")->success();
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Tenancy Added successfully!']);
        }
        
        return back();
    }

    // Display the specified tenancy
    public function show($id)
    {
        // $tenancy = Tenancy::findOrFail($id);
        // Find the tenancy with all needed relationships
        $tenancy = Tenancy::with([
            'property',
            'offer',
            'tenantMembers.user', // eager load tenant details
            'tenancyType',
            'tenancySubStatus',
            'propertyManagers'
        ])->findOrFail($id);
        return view('backend.tenancies.show', compact('tenancy'));
    }

    public function rentLedger($id)
    {
        $tenancy = Tenancy::with([
            'property',
            'tenantMembers.user',
            'tenancyType',
            'tenancySubStatus',
            'propertyManagers',
        ])->findOrFail($id);

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

        // Fetch related data needed for the edit form

        // Get all tenants (users where category_id is 3)
        // $tenants = User::where('category_id', 3)->get();

        // Get all property managers (users where category_id is 2)
        // $property_managers = User::where('category_id', 2)->get();

        // Get all tenants (users with role 'tenant')
        $tenants = User::role('Tenant')->get();

        // Get all property managers (users with role 'property_manager')
        $property_managers = User::role('Property Manager')->get();

        // Fetch all tenancy types
        $tenancyTypes = TenancyType::all();

        // Fetch all tenancy sub-statuses
        $tenancySubStatuses = TenancySubStatus::all();

        // Fetch the tenancy's current property managers
        $currentPropertyManagers = PropertyManagerTenancy::where('tenancy_id', $id)->pluck('property_manager_id')->toArray();

        // Fetch the tenancy's current tenant members
        $tenantMembers = TenantMember::where('tenancy_id', $id)->get();

        // Find the main person from the tenant members
        $mainPersonId = $tenantMembers->where('is_main_person', true)->pluck('user_id')->first();

        // Pass all data to the edit view
        return view('backend.tenancies.edit', compact(
            'tenancy',
            'tenants',
            'property_managers',
            'tenancyTypes',
            'tenancySubStatuses',
            'currentPropertyManagers',
            'tenantMembers',
            'mainPersonId'
        ));
    }


    // Update the specified tenancy
    public function update(Request $request, $id)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'offer_id' => 'nullable|exists:offers,id',
            'status' => 'required|in:Active,Archive', // Assuming status is either Active or Archive
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
            'user_id' => 'required|array', // Validate that the user_id is an array
            'user_id.*' => 'exists:users,id', // Ensure each user_id exists in the users table

            'is_main_person' => 'required|exists:users,id', // Ensure main person is a valid user ID
            'property_manager' => 'nullable|array', // Ensure property_manager is an array (nullable)
            'property_manager.*' => 'exists:users,id', // Ensure each property manager exists in the users table
        ]);

        // Manually convert checkbox field
        $validated['periodic'] = $request->has('periodic') ? true : false;
        $validated['rolling_contract'] = $request->has('rolling_contract') ? true : false;
        $validated['renewal_exempt'] = $request->has('renewal_exempt') ? true : false;

        // Find the existing tenancy record
        $tenancy = Tenancy::findOrFail($id);

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
                'tenancy_id' => $tenancy->id,
                'user_id' => $userId,
                'is_main_person' => $isMainPerson,
                'group_id' => $groupId, // Set group_id if necessary
            ]);
        }

        flash("Tenancy updated successfully!")->success();
        return back();
    }


    // Remove the specified tenancy from storage
    public function destroy($id)
    {
        $tenancy = Tenancy::findOrFail($id);
        $propertyId = $tenancy->property_id;
        $tenancy->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.properties.index', ['property_id' => $propertyId, 'tabname' => 'tenancy'])
            ->with('success', 'Tenancy deleted successfully!');
    }
}
