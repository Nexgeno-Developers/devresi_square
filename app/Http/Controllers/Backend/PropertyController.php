<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use App\Models\AccountUser;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Notes;
use App\Models\Offer;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Tenancy;
use App\Models\NoteType;
use App\Models\Property;
use App\Models\OwnerGroup;
use App\Models\SchoolName;
use App\Models\Upload;
use App\Models\Designation;
use App\Models\StationName;
// use App\Models\EstateCharge;
use App\Models\EstateCharge;
use App\Services\Onboarding\LandlordOnboardingService;
use App\Services\Saas\PortalAccessService;
use Dom\Document;
use Illuminate\Http\Request;
use App\Models\ComplianceType;
use App\Models\LocalAuthority;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\PropertyResponsibility;
use App\Rules\UniquePropertyIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class PropertyController
{
    use EnforcesSaasPlanLimits;

    public function index(Request $request)
    {
        // Fetch all properties
        // $properties = Property::all();
        // Fetch all properties in descending order
        // $properties = Property::orderBy('id', 'desc')->get();

        // Get logged-in user
        $user = auth()->user();
        $onboarding = app(LandlordOnboardingService::class);
        if ($user && $user->hasRole('Landlord')) {
            $onboarding->syncOverlaySessionForPage($user, current_account(), $request->boolean('add_property'));
        }
        $portalAccessService = app(PortalAccessService::class);
        $accountId = current_account_id();
        $isPortalUser = $accountId && $portalAccessService->isPortalUser($user, $accountId);

        // Build base query
        $propertiesQuery = Property::query();
        $this->scopePropertyQuery($propertiesQuery);

        $this->applyListFilters($propertiesQuery, $request);

        // Fetch properties based on role
        if ($isPortalUser) {
            $properties = $propertiesQuery->whereIn('id', $portalAccessService->accessiblePropertyIds($user, $accountId))
                ->orderBy('id', 'desc')
                ->paginate(15);
        } elseif ($user->hasRole('Property Manager') || $user->hasRole('Super Admin')) {
            // Property managers see all properties
            $properties = $propertiesQuery->orderBy('id', 'desc')->paginate(15);
        } elseif ($user->hasRole('Tenant')) {
            // Tenants see only properties where they have an active tenancy
            $propertyIds = \App\Models\TenantMember::where('user_id', $user->id)
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenant_members.account_id', $accountId)
                ->where('tenancies.account_id', $accountId)
                ->where('tenancies.status', 'Active')
                ->pluck('tenancies.property_id')
                ->unique();
            $properties = $propertiesQuery->whereIn('id', $propertyIds)
                ->orderBy('id', 'desc')
                ->paginate(15);
        } elseif (is_landlord_plan_user($user)) {
            $properties = $propertiesQuery->orderBy('id', 'desc')->paginate(15);
        } elseif ($user->hasRole('Staff') || $user->hasRole('Test')) {
            $properties = $propertiesQuery->where('created_by', $user->id)
                ->orderBy('id', 'desc')
                ->paginate(15);
        } elseif ($user->hasRole('Estate Agent')) {
            // Estate agents see properties created by them or their sub-users
            $createdUserIds = User::where('created_by', $user->id)->pluck('id');
            $properties = $propertiesQuery->whereIn('created_by', $createdUserIds->push($user->id))
                ->orderBy('id', 'desc')
                ->paginate(15);
        } else {
            // Default: no access — return empty paginator
            $properties = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        if (method_exists($properties, 'appends')) {
            $properties->appends($request->except(['list_only']));
        }

        // If AJAX request for property list only (search/pagination)
        if ($request->ajax() && $request->has('list_only')) {
            $selectedPropertyId = $request->integer('property_id') ?: null;

            if ($request->filled('highlight_id')) {
                $highlightId = (int) $request->highlight_id;
                $perPage = 15;
                $positionQuery = Property::query();
                $this->scopePropertyQuery($positionQuery);
                $this->applyListFilters($positionQuery, $request);
                if ($isPortalUser) {
                    $positionQuery->whereIn('id', $portalAccessService->accessiblePropertyIds($user, $accountId));
                } elseif (is_landlord_plan_user($user)) {
                    // Workspace-scoped via forAccount()
                } elseif ($user->hasRole('Staff') || $user->hasRole('Test')) {
                    $positionQuery->where('created_by', $user->id);
                } elseif ($user->hasRole('Estate Agent')) {
                    $createdUserIds = User::where('created_by', $user->id)->pluck('id');
                    $positionQuery->whereIn('created_by', $createdUserIds->push($user->id));
                }

                $position = $positionQuery->orderBy('id', 'desc')->pluck('id')->search($highlightId);

                if ($position !== false) {
                    $page = (int) floor($position / $perPage) + 1;
                    $properties = $positionQuery->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
                    $properties->appends($request->except(['list_only']));
                }
            }

            $response = [
                'html' => view('backend.properties.partials.property-list', [
                    'properties' => $properties,
                    'propertyId' => $selectedPropertyId,
                    'isPortalUser' => $isPortalUser,
                ])->render(),
                'pagination' => $properties->hasPages() ? (string) $properties->links() : '',
            ];

            if ($selectedPropertyId) {
                $selectedProperty = Property::find($selectedPropertyId);
                if ($selectedProperty) {
                    ensureModelBelongsToCurrentAccount($selectedProperty);
                    $this->assertPropertyVisibleToUser($user, $selectedProperty, $isPortalUser, $portalAccessService);

                    $response['tabs'] = $this->tabsForUser($user, $selectedProperty, $isPortalUser, $portalAccessService);
                    $response['detail_header'] = view('backend.properties.partials.detail-header', ['property' => $selectedProperty])->render();
                }
            }

            return response()->json($response);
        }
        
        // Redirect to 'quick' if there are no properties
        if ($properties->isEmpty()) {
            if ($isPortalUser || $user->hasRole('Tenant')) {
                $tabs = $this->tabsForUser($user, null, $isPortalUser, $portalAccessService);
                $content = '<div class="alert alert-info m-3">You don\'t have any active tenancy properties linked to your account. Please contact your property manager.</div>';
                return view('backend.properties.control-center', [
                    'properties' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
                    'tabs'       => $tabs,
                    'propertyId' => null,
                    'tabName'    => 'property',
                    'content'    => $content,
                    'property'   => null,
                    'isPortalUser' => $isPortalUser,
                ]);
            }
            if ($this->shouldUseLandlordWizard()) {
                $account = current_account();
                if (app(LandlordOnboardingService::class)->shouldShow($user, $account)) {
                    $tabs = $this->tabsForUser($user, null, $isPortalUser, $portalAccessService);

                    return view('backend.properties.control-center', [
                        'properties' => $properties,
                        'tabs'       => $tabs,
                        'propertyId' => null,
                        'tabName'    => 'property',
                        'content'    => '',
                        'property'   => null,
                        'isPortalUser' => $isPortalUser,
                    ]);
                }
            }
            flash("You don't have any properties yet!")->error();
            return redirect()->route($this->propertyCreateRoute());
        }

        // Get property_id and tabname from query parameters
        $propertyId = $request->query('property_id');
        $tabName = $request->query('tabname', 'property');

        // If no property_id in URL:
        // Tenant → show list only, nothing selected (clean page)
        // Others → redirect to first property
        if (!$propertyId) {
            if ($user->hasRole('Tenant') || $isPortalUser) {
                $tabs = $this->tabsForUser($user, null, $isPortalUser, $portalAccessService);
                return view('backend.properties.control-center', [
                    'properties' => $properties,
                    'tabs'       => $tabs,
                    'propertyId' => null,
                    'tabName'    => $tabName,
                    'content'    => '',
                    'property'   => null,
                    'isPortalUser' => $isPortalUser,
                ]);
            }
            $firstProperty = $properties->first();
            return redirect()->route('admin.properties.index', array_filter([
                'property_id' => $firstProperty->id,
                'tabname'     => $tabName,
                'add_property' => $request->boolean('add_property') ? 1 : null,
            ]));
        }

        $property = Property::find($propertyId);

        if ($property) {
            ensureModelBelongsToCurrentAccount($property);
        }

        /*if (!$property) {
            // Get the first property that is NOT soft-deleted
            $firstProperty = Property::withoutTrashed()->orderBy('id', 'desc')->first();

            if (!$firstProperty) {
                flash("You don't have any properties yet!")->error();
                return redirect()->route('admin.properties.quick');
            }

            $propertyId = $firstProperty->id;
            $property = $firstProperty;

            // flash("The selected property does not exist or has been deleted. Showing another one instead.")->error();

        }*/
                    
        if ($property) {
            $this->assertPropertyVisibleToUser($user, $property, $isPortalUser, $portalAccessService);
        } else {
            $firstProperty = $properties->first();
            return redirect()->route('admin.properties.index', [
                'property_id' => $firstProperty->id,
                'tabname'     => $tabName,
            ]);
        }

        $tabs = $this->tabsForUser($user, $property, $isPortalUser, $portalAccessService);

        $requestedTabIsAllowed = collect($tabs)->contains(
            fn (array $tab) => strtolower($tab['name']) === strtolower($tabName)
        );
        if (! $requestedTabIsAllowed && is_landlord_plan_user($user) && $property) {
            return redirect()->route('admin.properties.index', [
                'property_id' => $property->id,
                'tabname' => 'Property',
            ]);
        }
        abort_unless($requestedTabIsAllowed, 403, 'You do not have access to this property tab.');

        $content = $this->getTabContent($tabName, $propertyId, $property);

        if ($request->ajax()) {
            return response()->json(['content' => $content]);
        }

        return view('backend.properties.control-center', compact(
            'properties',
            'tabs',
            'propertyId',
            'tabName',
            'content',
            'property',
            'isPortalUser'
        ));
    }

    private function buildPermissionTabs($user): array
    {
        $availableTabs = [
            'view properties'          => 'Property',
            'view property owners'     => 'Owners',
            'manage property compliance' => 'Compliance',
            'view property media'      => 'Media',
            'view property offers'     => 'Offers',
            'view property tenancy'    => 'Tenancy',
            'view property aps'        => 'APD',
            'view property teams'      => 'Teams',
            'view property documents'  => 'Documents',
            'view property notes'      => 'Notes',
            'view property appointments' => 'Appointments',
            'view property statement'  => 'Statement',
        ];
        $tabs = [];
        foreach ($availableTabs as $permission => $name) {
            if ($user->can($permission)) {
                $tabs[] = ['name' => $name];
            }
        }

        if ($user->can('create properties') || $user->can('edit properties')) {
            $this->ensureTab($tabs, 'Property');
        }

        if ($this->shouldUseLandlordWizard()) {
            foreach (['Property', 'Owners', 'Tenancy', 'Documents'] as $name) {
                $this->ensureTab($tabs, $name);
            }
        }

        if ($user->can('view property teams') && $this->hasPropertyManagerAddon()) {
            $this->ensureTab($tabs, 'Responsibility');
        }

        return $tabs;
    }

    private function ensureTab(array &$tabs, string $name): void
    {
        if (! collect($tabs)->contains(fn (array $tab) => $tab['name'] === $name)) {
            $tabs[] = ['name' => $name];
        }
    }

    private function tabsForUser($user, ?Property $property, bool $isPortalUser, PortalAccessService $portalAccessService): array
    {
        if (is_landlord_plan_user($user)) {
            return client_facing_property_tabs();
        }

        if ($isPortalUser) {
            if (! $property) {
                return [['name' => 'Property']];
            }

            $participant = $portalAccessService->getParticipant($user, $property);
            $portalTabs = [['name' => 'Property']];

            if ($participant && in_array($participant->participant_type, ['tenant', 'landlord', 'owner', 'property_manager'], true)) {
                $portalTabs[] = ['name' => 'Tenancy'];
            }

            if ($portalAccessService->canViewDocuments($user, $property)) {
                $portalTabs[] = ['name' => 'Documents'];
            }

            if ($participant?->participant_type === 'property_manager') {
                $portalTabs[] = ['name' => 'Notes'];
            }

            if ($portalAccessService->canViewFinance($user, $property)) {
                $portalTabs[] = ['name' => 'Statement'];
            }

            return $portalTabs;
        }

        return $this->buildPermissionTabs($user);
    }

    private function applyListFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prop_name', 'like', "%{$search}%")
                    ->orWhere('prop_ref_no', 'like', "%{$search}%")
                    ->orWhere('line_1', 'like', "%{$search}%")
                    ->orWhere('line_2', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('postcode', 'like', "%{$search}%")
                    ->orWhere('property_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->filled('status')) {
            $statuses = collect(is_array($request->status) ? $request->status : explode(',', (string) $request->status))
                ->map(fn ($status) => trim((string) $status))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($statuses) {
                $query->where(function ($q) use ($statuses) {
                    $q->whereIn('sales_current_status', $statuses)
                        ->orWhereIn('letting_current_status', $statuses);
                });
            }
        }

        if ($request->boolean('compliance_expiring')) {
            $query->whereHas('complianceRecords', function ($q) {
                $q->where('expiry_date', '<=', now()->addMonths(2))
                    ->where('expiry_date', '>=', now());
            });
        }

        if ($request->boolean('has_open_repairs')) {
            $query->whereHas('repairIssues', function ($q) {
                $q->whereNotIn('status', ['Closed', 'Invoice Paid']);
            });
        }

        return $query;
    }

    private function assertPropertyVisibleToUser($user, Property $property, bool $isPortalUser, PortalAccessService $portalAccessService): void
    {
        if ($isPortalUser) {
            abort_unless($portalAccessService->canAccessProperty($user, $property), 403, 'You do not have access to this property.');
            return;
        }

        $isAuthorized = $user->hasRole('Super Admin')
            || $user->hasRole('Property Manager')
            || (is_landlord_plan_user($user) && (int) $property->account_id === (int) current_account_id())
            || ($user->hasRole('Landlord') && ! is_landlord_plan_user($user) && $property->created_by === $user->id)
            || ($user->hasRole('Estate Agent') && ($property->created_by === $user->id || $user->createdUsers()->pluck('id')->contains($property->created_by)))
            || (($user->hasRole('Staff') || $user->hasRole('Test')) && $property->created_by === $user->id)
            || ($user->hasRole('Tenant') && \App\Models\TenantMember::where('user_id', $user->id)
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenant_members.account_id', current_account_id())
                ->where('tenancies.account_id', current_account_id())
                ->where('tenancies.status', 'Active')
                ->where('tenancies.property_id', $property->id)
                ->exists());

        abort_unless($isAuthorized, 403, 'You do not have access to this property.');
    }

    private function hasPropertyManagerAddon(): bool
    {
        if (auth()->user()?->hasRole('Super Admin')) {
            return true;
        }

        return \App\Models\AccountSubscriptionAddon::query()
            ->where('account_id', current_account_id())
            ->where('status', 'active')
            ->whereHas('addon', fn ($query) => $query
                ->where('addon_type', 'property_manager')
                ->where('is_active', true))
            ->exists();
    }

    private function getTabContent($tabname, $propertyId, $property)
    {
        $user = auth()->user();
        $portalAccessService = app(PortalAccessService::class);
        $isPortalUser = current_account_id() && $portalAccessService->isPortalUser($user, current_account_id());

        if ($isPortalUser) {
            abort_unless($portalAccessService->canAccessProperty($user, $property), 403, 'You do not have access to this property.');
        }

        switch (strtolower($tabname)) {
            case 'property':

                // Fetch all station names and school names
                $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
                $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

                $stationIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_station))));
                $schoolIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_school))));

                // Fetch the station and school names using the IDs
                $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
                $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

                // Pass only the selected property details
                return view('backend.properties.tabs.property', compact('propertyId', 'tabname', 'property', 'allstations', 'allschools', 'stations', 'schools'))->render();
            case 'owners':
                // Fetch the owner groups for the given propertyId, along with related users and properties.
                // $ownerGroups = OwnerGroup::with(['user', 'property'])
                // ->where('property_id', $propertyId)
                // ->get();

                // Fetch the owner groups for the given propertyId, along with related users and properties.
                $ownerGroups = OwnerGroup::with([
                    'ownerGroupUsers' => function ($ownerUserQuery) {
                        $ownerUserQuery
                            ->when(
                                ! auth()->user()?->hasRole('Super Admin'),
                                fn ($query) => $query->whereHas(
                                    'user',
                                    fn ($userQuery) => $userQuery->forAccount(current_account_id())
                                )
                            )
                            ->with('user');
                    },
                    'property',
                ])
                    ->where('property_id', $propertyId)
                    ->get();

                return view('backend.properties.tabs.owners', compact('propertyId', 'ownerGroups'))->render();
            case 'offers':

                // Fetch all offers for the specific property
                $offers = Offer::where('property_id', $propertyId)->get();

                // Decode tenant details for each offer
                foreach ($offers as $offer) {
                    $offer->tenant_details = json_decode($offer->tenant_details, true);
                }
                $tenantIds = $offers
                    ->flatMap(fn ($offer) => array_keys($offer->tenant_details ?: []))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $tenantUsers = User::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->whereIn('id', $tenantIds)
                    ->with('details')
                    ->get()
                    ->keyBy('id');

                foreach ($offers as $offer) {
                    $offer->setRelation(
                        'tenantUsers',
                        collect(array_keys($offer->tenant_details ?: []))
                            ->map(fn ($id) => $tenantUsers->get((int) $id))
                            ->filter()
                            ->values()
                    );
                }

                return view('backend.properties.tabs.offers', compact('propertyId', 'offers'))->render();
            case 'compliance':
                // Fetch compliance types
                $complianceTypes = ComplianceType::all();

                // Fetch compliance records for the specific property and group them by compliance type
                $complianceRecords = $property->complianceRecords()
                    ->with('complianceType', 'complianceDetails') // Eager load relationships
                    ->where('property_id', $propertyId) // Filter by property ID
                    ->latest()
                    ->get()
                    ->groupBy('compliance_type_id'); // Group by compliance type

                return view('backend.properties.tabs.compliance', compact('propertyId', 'complianceTypes', 'complianceRecords'))->render();

            case 'tenancy':
                // Fetch tenancies for all statuses
                $tenancies = Tenancy::where('property_id', $propertyId)
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->with(['tenantMembers.user', 'tenancySubStatus'])
                    ->get(); // Fetch all tenancies for the property

                // Get distinct status types for filtering
                // $statuses = ['Active', 'Inactive', 'Terminated', 'Archived'];
                $statuses = ['Active', 'Archived'];
                return view('backend.properties.tabs.tenancy2', compact('statuses', 'tenancies', 'propertyId'))->render();

            // case 'tenancy':

            //     // Fetch active tenancies and order them by move_in date (latest first)
            //     // $tenancies = Tenancy::where('property_id', $propertyId)
            //     // ->where('status', 'Active')   // Filter by active status
            //     // ->orderBy('move_in', 'desc')  // Order by move_in date (latest first)
            //     // ->first()->get();

            //     $tenancies = Tenancy::where('property_id', $propertyId)
            //             ->where('status', 'Active')   // Filter by active status
            //             ->get(); // Always get a collection (empty or with one or more records)


            //     // $tenancies = Tenancy::where('property_id', $propertyId)
            //     //             ->where('status', 'Active')   // Filter by active status
            //     //             ->first(); // Get only the first (latest) record

            //     // Pass the data to the tenancy view
            //     return view('backend.properties.tabs.tenancy', compact('tenancies', 'propertyId'))->render();

            case 'apd':
            case 'aps':
                return view('backend.properties.tabs.aps', compact('propertyId', 'property'))->render();
            case 'media':
                return view('backend.properties.tabs.media', compact('propertyId', 'property'))->render();
            case 'teams':
                return view('backend.properties.tabs.teams', compact('propertyId', 'property'))->render();
            case 'responsibility':
                abort_unless($this->hasPropertyManagerAddon(), 403, 'The Property Manager add-on is required for responsibility mapping.');
                $responsibilities = PropertyResponsibility::with('user')
                    ->where('property_id', $propertyId)
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->get();

                return view('backend.properties.tabs.responsibility', compact('propertyId', 'property', 'responsibilities'))->render();
            case 'documents':
                if ($isPortalUser) {
                    abort_unless($portalAccessService->canViewDocuments($user, $property), 403, 'You do not have access to property documents.');
                }

                $documents = $property->documents()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->with('documentType')
                    ->orderByDesc('updated_at')
                    ->paginate(5);

                // Ensure it's an empty collection if no documents are found
                if ($documents->isEmpty()) {
                    $documents = collect();  // Make sure it's an empty collection, not null
                }
                $documentTypes = DocumentType::all();
                $canUploadDocuments = ! $isPortalUser || $portalAccessService->canUploadDocuments($user, $property);
                return view('backend.properties.tabs.documents', compact('propertyId', 'property', 'documentTypes', 'documents', 'canUploadDocuments'))->render();
            // case 'contractor':
            //     return view('backend.properties.tabs.contractor', compact('propertyId'))->render();
            // case 'work offer':
            //     return view('backend.properties.tabs.work_offer', compact('propertyId'))->render();
            case 'notes':
                if ($isPortalUser) {
                    abort_unless($portalAccessService->canAccessProperty($user, $property), 403, 'You do not have access to property notes.');
                }
                // Fetch the notes related to the specific property by property ID
                // $notes = Notes::where('property_id', $propertyId)->orderBy('updated_at', 'desc')->get();
                $notesQuery = $property->notes()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->with('noteType')
                    ->orderByDesc('updated_at');

                if ($isPortalUser && Schema::hasColumn('notes', 'visibility')) {
                    $notesQuery->where('visibility', 'portal');
                }

                $notes = $notesQuery->paginate(5);

                // Ensure it's an empty collection if no notes are found
                if ($notes->isEmpty()) {
                    $notes = collect();  // Make sure it's an empty collection, not null
                }
                $noteTypes = NoteType::all();
                // Return the view and pass the notes data (null or the notes collection)
                return view('backend.properties.tabs.notes', compact('propertyId', 'property', 'notes', 'noteTypes'))->render();

            case 'appointments':
                $query = $property->events()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($eventQuery) => $eventQuery->forAccount(current_account_id()))
                    ->with(['diaryOwner', 'onBehalfOf', 'users'])
                    ->orderBy('start_datetime', 'desc');

                if ($request = request()) {
                    if ($search = $request->query('search')) {
                        $query->where(function ($q) use ($search) {
                            $q->where('title', 'like', "%$search%")
                                ->orWhereHas('diaryOwner', fn($q2) => $q2->where('name', 'like', "%$search%"))
                                ->orWhereHas('onBehalfOf', fn($q2) => $q2->where('name', 'like', "%$search%"));
                        });
                    }

                    if ($status = $request->query('status')) {
                        $query->where('status', $status);
                    }

                    if ($start = $request->query('start_date')) {
                        $query->whereDate('start_datetime', '>=', $start);
                    }

                    if ($end = $request->query('end_date')) {
                        $query->whereDate('start_datetime', '<=', $end);
                    }
                }

                $events = $query->paginate(10); // 👈 paginate instead of get()

                // If AJAX just return table partial
                if (request()->ajax() && request()->query('ajax_only') == 1) {
                    return view('backend.properties.tabs.component._appointments_table', compact('events'))->render();
                }

                return view('backend.properties.tabs.appointments', compact('propertyId', 'property', 'events'))->render();

            case 'statement':
                if ($isPortalUser) {
                    abort_unless($portalAccessService->canViewFinance($user, $property), 403, 'You do not have access to property finance.');
                }

                $filters = $this->statementFilters(request());
                $statement = app(\App\Services\Accounting\StatementService::class)
                    ->propertyStatement(
                        $property->id,
                        (int) ($property->account_id ?: current_account_id()),
                        $property->company_id ?? null,
                        $filters['date_from'],
                        $filters['date_to']
                    );
                $statement['summary']['balance_due'] = $statement['closing'];
                return view('backend.properties.tabs.statement', [
                    'property' => $property,
                    'filters' => $filters,
                    'statement' => $statement,
                ])->render();

            default:
                return 'Tab content not found';
        }
    }

    // Show the form for creating a new property.
    public function create()
    {
        if ($response = $this->redirectIfSaasLimitDenied('property', 'admin.properties.index')) {
            return $response;
        }

        return view('backend.properties.create'); // Return the create property view
    }

    // show quick form
    public function quick()
    {
        if ($response = $this->redirectIfSaasLimitDenied('property', 'admin.properties.index')) {
            return $response;
        }

        if ($this->shouldUseLandlordWizard()) {
            return redirect()->route('admin.properties.index', ['add_property' => 1]);
        }

        $countries = Country::orderBy('name')->get();
        return view('backend.properties.quick', compact('countries')); // Return the create property view
    }
    public function store(Request $request)
    {
        // Ensure the user ID is stored in the session
        // if (!session()->has('user_id')) {
        //     $request->session()->put('user_id', Auth::id());
        // }
        // $request->session()->put('current_step', $request->step + 1);

        // Validate data based on the current step
        if ($request->has('step')) {
            // Validate the request data
            $validatedData = $request->validate($this->getValidationRules($request->step, $request));

            // Convert market_on to JSON if it's an array
            // if ($request->has('market_on') && is_array($request->market_on)) {
            //     $validatedData['market_on'] = json_encode($request->market_on);  // Serialize the array to JSON
            // }

            // Store all data in session excluding token and step
            //$request->session()->put($request->except('_token', 'step'));

            //$userId = $request->session()->get('user_id'); // Retrieve the user ID from the session

            // Get property_id from the session or request
            $property_id = $request->property_id;
            if ($property_id) {
                $existingProperty = Property::findOrFail($property_id);
                ensureModelBelongsToCurrentAccount($existingProperty);
            }

            // Collect responsibility data from the form
            $propertyResponsibilityIds = $request->input('PropertyResponsibility_id', []);
            $user_ids = $request->input('user_id', []);
            $designation_ids = $request->input('designation_id', []);
            $branch_ids = $request->input('branch_id', []);
            $commission_percentages = $request->input('commission_percentage', []);
            $commission_amounts = $request->input('commission_amount', []);

            if ((int) $request->step === 9) {
                $this->ensureUploadsAreAccessible($validatedData['photos'] ?? null);
                $this->ensureUploadsAreAccessible($validatedData['floor_plan'] ?? null);
                $this->ensureUploadsAreAccessible($validatedData['view_360'] ?? null);
            }

            if (! empty($user_ids)) {
                $this->ensureAccountIdsAreAccessible($user_ids, User::query(), 'user_id', 'users');
                $this->ensureAccountIdsAreAccessible($designation_ids, Designation::query(), 'designation_id', 'designations');
                $this->ensureAccountIdsAreAccessible($branch_ids, Branch::query(), 'branch_id', 'branches');
            }

            $submitted_ids = []; // Track IDs of processed responsibilities

            // Iterate through the responsibilities and update or create them
            foreach ($user_ids as $index => $user_id) {
                $data = [
                    'account_id' => current_account_id(),
                    'property_id' => $property_id,
                    'user_id' => $user_id,
                    'designation_id' => $designation_ids[$index] ?? null,
                    'branch_id' => $branch_ids[$index] ?? null,
                    'commission_percentage' => $commission_percentages[$index] ?? null,
                    'commission_amount' => $commission_amounts[$index] ?? null,
                    // 'added_by' => Auth::id(),
                ];

                // Set 'added_by' only when creating a new responsibility
                if (empty($propertyResponsibilityIds[$index])) {
                    $data['added_by'] = Auth::id(); // Only set 'added_by' for new records
                }

                $responsibilityId = $propertyResponsibilityIds[$index] ?? null;
                if ($responsibilityId) {
                    $responsibility = PropertyResponsibility::query()
                        ->where('property_id', $property_id)
                        ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                        ->findOrFail($responsibilityId);
                    $responsibility->update($data);
                } else {
                    $responsibility = PropertyResponsibility::create($data);
                }

                $submitted_ids[] = $responsibility->id; // Track the ID of the responsibility
            }

            // Remove responsibilities that are not in the submitted IDs
            if (!empty($submitted_ids)) {
                PropertyResponsibility::where('property_id', $property_id)
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->whereNotIn('id', $submitted_ids)
                    ->whereNull('deleted_at')  // Ensure we're only soft-deleting active records
                    ->update(['deleted_by' => Auth::id()]); // Set 'deleted_by' to the authenticated user

                // Soft delete the records
                PropertyResponsibility::where('property_id', $property_id)
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->whereNotIn('id', $submitted_ids)
                    ->delete();
            }

            // Check if property_id is provided in the request
            if ($property_id) {
                $property = Property::find($property_id);
                if ($property) {
                    ensureModelBelongsToCurrentAccount($property);
                }

                $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
                $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

                // Get the nearest station IDs and nearest school IDs from the property (these will be comma-separated strings)
                $stationIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_station))));  // Convert to an array
                $schoolIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_school))));    // Convert to an array

                // Fetch the station and school names using the IDs
                $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
                $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

                // Fetch required data for dropdowns
                $users = User::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->select('id', 'name')
                    ->get();
                $designations = Designation::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->select('id', 'title')
                    ->get();
                $branches = Branch::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->select('id', 'name')
                    ->get();

                $PropertyResponsibility = PropertyResponsibility::where('property_id', $property_id)
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->get();

                if ($property) {

                    // // If estate charge exists, update it(for enter amount and auto generate estate charge record)
                    // if ($property->estate_charges_id) {
                    //     $estateCharge = EstateCharge::find($property->estate_charges_id);
                    //     if ($estateCharge) {
                    //         $estateCharge->update(['amount' => $request->estate_charges['amount']]);
                    //     }
                    // } else {
                    //     // If no estate charge exists, create a new one
                    //     $estateCharge = EstateCharge::create([
                    //         'amount' => $request->estate_charges['amount'],
                    //     ]);
                    // }
                    // $property->estate_charges_id = $estateCharge->id; // Associate the new charge

                    // Log the data before updating
                    Log::info('Updating property with ID ' . $property_id, $validatedData);
                    $validatedData['video_url'] = $request->video_url ?: null;
                    // Add a condition to prevent updating the step if it's the final step
                    if ($request->step < $this->getTotalSteps()) {
                        $validatedData['step'] = $request->step; // Update step only if it's not the last step
                    }
                    // $validatedData['step'] = $request->step;
                    $this->persistProperty(function () use ($property, $validatedData) {
                        return $property->update($validatedData);
                    });
                    // session()->forget('property_id');
                    // session()->forget('current_step');
                }
            } else {
                // Create new property only on the first step
                if ($request->step == 1) {
                    if ($response = $this->backIfSaasLimitDenied('property')) {
                        return $response;
                    }

                    $this->abortIfCannotAddProperties(1);
                    // Generate Property Reference Number
                    $PropertyRefNumber = generateReferenceNumber(Property::class, 'prop_ref_no', 'RESISQP');
                    $validatedData['prop_ref_no'] = $PropertyRefNumber;
                    // $validatedData['prop_ref_no'] = $this->generatePropertyRefNumber();
                    Log::info('Creating new pref', $validatedData['prop_ref_no']);
                    Log::info('Creating new property', $validatedData);
                    $property = $this->persistProperty(function () use ($validatedData, $request) {
                        return Property::create(array_merge($validatedData, [
                            'account_id' => current_account_id(),
                            'created_by' => Auth::id(),
                            'step' => $request->step,
                        ]));
                    });
                    // session()->forget('current_step');
                    // $property = Property::create(array_merge($validatedData, ['added_by' => $userId]));
                }
            }

            // Handle the multiple image uploads for photos, floor plans, 360 views, etc.
            $this->handleImageUploads($request, $property);

            // Get total number of steps
            $totalSteps = $this->getTotalSteps();

            // Check if the current step is the last one
            if ($request->step >= $totalSteps) {
                // Final submission handling
                // Flush all session data except specified keys in one line
                //$this->flushSessionExcept(['_token', 'url', '_previous', '_flash', 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d']);
                flash("Property Added/Updated successfully!")->success();
                return redirect()->route('admin.properties.index');
                // return redirect()->route('admin.properties.index')->with('success', 'Property Added/Updated successfully!');
            }
            // If step 6, fetch station names and school names, and return view with data
            if ($request->step == 5) {
                $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
                $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

                // Return the view with stations, schools, and property data
                return view('backend.properties.form_components.step' . ($request->step + 1), compact('property', 'allstations', 'allschools'));
            }
            // Load the next step view
            // return view('backend.properties.form_components.step' . ($request->step + 1));
            // return view('backend.properties.form_components.step' . ($request->step + 1))->withInput();
            return view('backend.properties.form_components.step' . ($request->step + 1), compact('property', 'allstations', 'allschools', 'stations', 'schools', 'users', 'designations', 'branches', 'PropertyResponsibility', 'propertyResponsibilityIds'));
        } else {
            // If no step is present, return a message (optional)
            return response()->json(['message' => 'Invalid step.']);
        }
    }
    public function quickStore(Request $request)
    {
        // Validate data based on the current step
        if ($request->has('step')) {
            // Validate the request data
            $validatedData = $request->validate($this->getValidationRulesQuick($request->step, $request));

            // Get property_id from the request
            $property_id = $request->property_id;
            if ($property_id) {
                $existingProperty = Property::findOrFail($property_id);
                ensureModelBelongsToCurrentAccount($existingProperty);
            }

            // Check if property_id is provided in the request
            if ($property_id) {
                $property = Property::find($property_id);
                if ($property) {
                    ensureModelBelongsToCurrentAccount($property);
                    // Log the data before updating
                    Log::info('Updating property with ID ' . $property_id, $validatedData);
                    //update step
                    $validatedData['quick_step'] = $request->step;
                    $this->persistProperty(function () use ($property, $validatedData) {
                        return $property->update($validatedData);
                    });
                    // session()->forget('property_id');
                }
            } else {
                // Create new property only empty property id
                if (empty($property_id)) {
                    if ($response = $this->backIfSaasLimitDenied('property')) {
                        return $response;
                    }

                    $this->abortIfCannotAddProperties(1);

                    $PropertyRefNumber = generateReferenceNumber(Property::class, 'prop_ref_no', 'RESISQP');

                    $validatedData['quick_step'] = $request->step;
                    // Generate Property Reference Number
                    $validatedData['prop_ref_no'] = $PropertyRefNumber;
                    // Log::info('Creating new pref', $validatedData['prop_ref_no']);
                    Log::info('Creating new property', $validatedData);
                    $property = $this->persistProperty(function () use ($validatedData) {
                        return Property::create(array_merge($validatedData, [
                            'account_id' => current_account_id(),
                            'created_by' => Auth::id(),
                        ]));
                    });
                    // $request->session()->put('property_id', $property->id);
                    // session()->forget('property_id');
                }
            }

            // Get total number of steps
            $totalSteps = $this->getTotalQuickSteps();

            // Check if the current step is the last one
            if ($request->step >= $totalSteps) {
                // Flush all session data except specified keys in one line
                //$this->flushSessionExcept(['_token', 'url', '_previous', '_flash', 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d']);

                // Final submission handling
                return view('backend.properties.quick_form_components.thankyou');
                //return redirect()->route('admin.properties.index')->with('success', 'Property Added/Updated successfully!');
            }

            // Load the next step view
            // return view('backend.properties.form_components.step' . ($request->step + 1));
            // return view('backend.properties.form_components.step' . ($request->step + 1))->withInput();
            return view('backend.properties.quick_form_components.step' . ($request->step + 1), compact('property'));
        } else {
            // If no step is present, return a message (optional)
            return response()->json(['message' => 'Invalid step from quick store.']);
        }
    }


    public function getStepView($step, Request $request)
    {
        // Get property_id from the session or request
        $property_id = $request->session()->get('property_id', $request->property_id);
        $property = Property::find($property_id);
        if ($property) {
            ensureModelBelongsToCurrentAccount($property);
        }

        // Get the total number of steps dynamically
        $totalSteps = $this->getTotalSteps();

        // Check if the step is valid
        if ($step > 0 && $step <= $totalSteps) {

            // If step is 6, fetch the station names and school names
            if ($step == 6) {
                $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
                $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

                // Return the view with the stations and schools
                return view('backend.properties.form_components.step' . $step, compact('property', 'allstations', 'allschools'));
            }

            if ($step == 10) {
                abort_unless($property, 404);

                $users = User::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->select('id', 'name')
                    ->get();
                $designations = Designation::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->select('id', 'title')
                    ->get();
                $branches = Branch::query()
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->select('id', 'name')
                    ->get();
                $PropertyResponsibility = PropertyResponsibility::where('property_id', $property->id)
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->get();

                return view(
                    'backend.properties.form_components.step' . $step,
                    compact('property', 'users', 'designations', 'branches', 'PropertyResponsibility')
                );
            }

            return view('backend.properties.form_components.step' . $step, compact('property')); // Return the corresponding Blade view
        } else {
            // Return a view with an error message if the step is invalid
            return view('backend.properties.form_components.error', ['message' => 'Invalid step.']);
        }
    }

    public function getQuickStepView($step, Request $request)
    {
        // Get property_id from the session or request
        $property_id = $request->property_id;
        $property = Property::find($property_id);
        if ($property) {
            ensureModelBelongsToCurrentAccount($property);
        }

        // Get the total number of steps dynamically
        $totalSteps = $this->getTotalQuickSteps();
        $countries = Country::orderBy('name')->get();
        // $countries = Country::where('status', 1)->orderBy('name')->get();
        // Check if the step is valid
        if ($step > 0 && $step <= $totalSteps) {
            return view('backend.properties.quick_form_components.step' . $step, compact('property', 'countries')); // Return the corresponding Blade view
        } else {
            // Return a view with an error message if the step is invalid
            return view('backend.properties.quick_form_components.error', ['message' => 'Invalid step.']);
        }
    }

    private function getTotalQuickSteps()
    {
        // Specify the directory where your Blade files for steps are located
        $stepsDirectory = resource_path('views/backend/properties/quick_form_components');

        // Get all Blade files in the directory that start with 'step' and count them
        return count(glob($stepsDirectory . '/step*.blade.php'));
    }
    private function getTotalSteps()
    {
        // Specify the directory where your Blade files for steps are located
        $stepsDirectory = resource_path('views/backend/properties/form_components');

        // Get all Blade files in the directory that start with 'step' and count them
        return count(glob($stepsDirectory . '/step*.blade.php'));
    }

    // private function flushSessionExcept(array $exceptKeys)
    // {
    //     $sessionData = session()->only($exceptKeys);
    //     session()->flush();
    //     session()->put($sessionData);
    // }

    // public function store(Request $request)
    // {
    //     // Validate data based on the current step
    //     if ($request->has('step')) {
    //        $validatedData = $this->validate($request, $this->getValidationRules($request->step));

    //         // Store data in session
    //         $request->session()->put($request->except('_token', 'step')); // Store all data except CSRF token and step

    //         // Load the next step view
    //         return view('backend.properties.form_components.step' . ($request->step + 1)); // Load next step
    //     } else {

    //         // Create property with the authenticated user ID
    //         Property::create(array_merge($validatedData, ['added_by' => Auth::id()]));

    //         return redirect()->route('admin.properties.index')->with('success', 'Property Added successfully!');
    //     }

    // }

    public function edit($id)
    {
        $property = Property::findOrFail($id); // Fetch property by ID
        ensureModelBelongsToCurrentAccount($property);
        $this->authorizePortalPropertyEdit($property);

        // Check if the request step is 6
        // if ($property->step == 5) {
        // Fetch all station names and school names
        $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
        $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

        // Get the nearest station IDs and nearest school IDs from the property (these will be comma-separated strings)
        $stationIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_station))));  // Convert to an array
        $schoolIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_school))));    // Convert to an array

        // Fetch the station and school names using the IDs
        $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
        $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

        // Fetch required data for dropdowns
        $users = User::query()
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->select('id', 'name')
            ->get();
        $designations = Designation::query()
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->select('id', 'title')
            ->get();
        $branches = Branch::query()
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->select('id', 'name')
            ->get();

        // Fetch PropertyResponsibility related to the current property
        // $PropertyResponsibility = PropertyResponsibility::where('property_id', $property->id)
        // ->select('id', 'responsibility')
        // ->get();

        $PropertyResponsibility = PropertyResponsibility::where('property_id', $property->id)
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->get();
        $propertyResponsibilityIds = $PropertyResponsibility->pluck('id')->implode(',');
        // Return the edit view with the property data, stations, and schools
        return view('backend.properties.edit', compact('property', 'allstations', 'allschools', 'stations', 'schools', 'users', 'designations', 'branches', 'PropertyResponsibility', 'propertyResponsibilityIds'));
        // }

        // If step is not 6, just return the property edit view
        // return view('backend.properties.edit', compact('property'));

        // $property = Property::findOrFail($id); // Fetch property by ID
        // return view('backend.properties.edit', compact('property'));
    }
    public function view($id)
    {
        $property = Property::findOrFail($id); // Fetch property by ID
        ensureModelBelongsToCurrentAccount($property);
        return view('backend.properties.view', compact('property'));
    }

    public function update(Request $request, $id)
    {
        // Validate and update property
        $validatedData = $request->validate([
            'prop_name' => 'required|string|max:255',
            'line_1' => 'required|string|max:255',
            'line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'property_type' => 'required|string',
            'transaction_type' => 'required|string',
            'specific_property_type' => 'required|string',
            'bedroom' => 'required|string',
            'bathroom' => 'required|string',
            'reception' => 'required|string',
            'service' => 'nullable|string',
            'price' => 'required|numeric',
            'available_from' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        $property = Property::findOrFail($id);
        ensureModelBelongsToCurrentAccount($property);
        $this->authorizePortalPropertyEdit($property);
        $property->update($validatedData); // Update the property

        return redirect()->route('admin.properties.index')->with('success', 'Property updated successfully.');
    }
    public function search(Request $request)
    {
        // Get the search query from the request
        $query = $request->input('query');

        // Search for properties based on multiple criteria
        $properties = Property::query()
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($propertyQuery) => $propertyQuery->forAccount(current_account_id()))
            ->where(function ($propertyQuery) use ($query) {
                $propertyQuery->where('prop_ref_no', 'LIKE', '%' . $query . '%')
                    ->orWhere('prop_name', 'LIKE', '%' . $query . '%')
                    ->orWhere('line_1', 'LIKE', '%' . $query . '%')
                    ->orWhere('line_2', 'LIKE', '%' . $query . '%')
                    ->orWhere('city', 'LIKE', '%' . $query . '%')
                    ->orWhere('country', 'LIKE', '%' . $query . '%')
                    ->orWhere('postcode', 'LIKE', '%' . $query . '%');
            })
            ->limit(10)  // Limit the results to 10
            ->get(['id', 'prop_ref_no', 'prop_name', 'city']);  // Return only necessary fields

        // Return the properties as JSON
        return response()->json($properties);
    }

    public function searchAjax(Request $request)
    {
        $query = $request->input('query');

        $properties = Property::query()
            ->with('countryRelation:id,name') // eager load country
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($propertyQuery) => $propertyQuery->forAccount(current_account_id()))
            ->where(function ($q) use ($query) {
                $q->where('prop_ref_no', 'LIKE', '%' . $query . '%')
                ->orWhere('prop_name', 'LIKE', '%' . $query . '%')
                ->orWhere('line_1', 'LIKE', '%' . $query . '%')
                ->orWhere('line_2', 'LIKE', '%' . $query . '%')
                ->orWhere('city', 'LIKE', '%' . $query . '%')
                ->orWhere('county', 'LIKE', '%' . $query . '%')
                ->orWhere('postcode', 'LIKE', '%' . $query . '%')
                ->orWhereHas('countryRelation', function ($q2) use ($query) {
                    $q2->where('name', 'LIKE', '%' . $query . '%');
                })
                ->orderBy('id', 'desc')
                ;
            })
            ->limit(5)
            ->get(['id', 'prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'county', 'postcode', 'country']);

        return response()->json(
            $properties->map(function ($property) {
                return [
                    'id'            => $property->id,
                    'prop_ref_no'   => $property->prop_ref_no,
                    'prop_name'     => $property->prop_name,
                    'city'          => $property->city,
                    'country'       => optional($property->countryRelation)->name,
                    'display_label' => $property->display_label,
                ];
            })
        );
    }


    public function destroy($id)
    {
        $property = Property::findOrFail($id);
        ensureModelBelongsToCurrentAccount($property);
        // Optionally, check if the property is already deleted
        if ($property->trashed()) {
            return redirect()->route('admin.properties.index')->with('error', 'This property is already deleted.');
        }
        // Use soft delete
        $property->deleted_by = Auth::id(); // Set the user who deleted the property
        $property->save(); // Save changes
        $property->delete(); // Perform the soft delete
        $response = [
            'status' => true,
            'message' => 'Property Deleted successfully!',
        ];

        return response()->json($response);
        //return redirect()->route('admin.properties.index')->with('success', 'Property deleted successfully.');
    }

    public function showSoftDeletedProperties()
    {
        $properties = $this->trashedPropertiesQuery()
            ->orderByDesc('deleted_at')
            ->get();

        return view('backend.properties.deleted', compact('properties'));
    }


    public function restore($id)
    {
        $this->abortIfCannotAddProperties(1);
        $property = $this->trashedPropertiesQuery()->findOrFail($id);
        $this->persistProperty(fn () => $property->restore());

        // $response = [
        //     'status' => true,
        //     'message' => 'Property restored successfully!',
        // ];
        flash('Property restored successfully.')->success();
        return back();
        // return back()->with('success', $response['message']);
        //return redirect()->route('admin.properties.index')->with('success', $response['message']);

        //return redirect()->route('admin.properties.index')->with('success', 'Property restored successfully.');
    }

    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'property_ids' => ['required', 'array', 'min:1'],
            'property_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $propertyIds = collect($validated['property_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $properties = $this->trashedPropertiesQuery()
            ->whereKey($propertyIds)
            ->get();

        abort_unless($properties->count() === $propertyIds->count(), 404);

        $this->abortIfCannotAddProperties($properties->count());

        DB::transaction(function () use ($properties) {
            foreach ($properties as $property) {
                $this->persistProperty(fn () => $property->restore());
            }
        });

        flash($properties->count() . ' properties restored successfully.')->success();

        return back();
    }

    public function forceDelete(Request $request, $id)
    {
        $this->authorizePermanentPropertyDeletion();

        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $property = $this->trashedPropertiesQuery()->findOrFail($id);

        try {
            DB::transaction(fn () => $property->forceDelete());
        } catch (QueryException $exception) {
            Log::warning('Permanent property deletion blocked by linked records.', [
                'property_id' => $property->id,
                'account_id' => $property->account_id,
                'error_code' => $exception->getCode(),
            ]);

            flash('This property cannot be permanently deleted because linked tenancy, repair, accounting, or compliance records still exist. Restore it or remove those dependencies first.')->error();

            return back();
        }

        flash('Property permanently deleted. This action cannot be undone.')->success();

        return back();
    }

    public function bulkForceDelete(Request $request)
    {
        $this->authorizePermanentPropertyDeletion();

        $validated = $request->validate([
            'property_ids' => ['required', 'array', 'min:1'],
            'property_ids.*' => ['required', 'integer', 'distinct'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $propertyIds = collect($validated['property_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $properties = $this->trashedPropertiesQuery()
            ->whereKey($propertyIds)
            ->get();

        abort_unless($properties->count() === $propertyIds->count(), 404);

        try {
            DB::transaction(function () use ($properties) {
                foreach ($properties as $property) {
                    $property->forceDelete();
                }
            });
        } catch (QueryException $exception) {
            Log::warning('Bulk permanent property deletion blocked by linked records.', [
                'property_ids' => $properties->modelKeys(),
                'error_code' => $exception->getCode(),
            ]);

            flash('No properties were permanently deleted. At least one selected property still has linked tenancy, repair, accounting, or compliance records.')->error();

            return back();
        }

        flash($properties->count() . ' properties permanently deleted. This action cannot be undone.')->success();

        return back();
    }

    private function trashedPropertiesQuery()
    {
        return Property::onlyTrashed()
            ->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($query) => $query->forAccount(current_account_id())
            );
    }

    private function authorizePermanentPropertyDeletion(): void
    {
        abort_unless(auth()->user()?->can('delete properties'), 403);
    }

    public function loadForm(Request $request)
    {
        $property = Property::find($request->property_id);
        $formType = $request->form_type;

        if (!$property) {
            return response()->json(['error' => 'Property not found'], 404);
        }

        ensureModelBelongsToCurrentAccount($property);

        if ($formType === 'responsibility') {
            abort_unless($this->hasPropertyManagerAddon(), 403, 'The Property Manager add-on is required for responsibility mapping.');
        }

        $viewPath = "backend.properties.popup_forms.$formType";

        // Check if the form view exists
        if (!view()->exists($viewPath)) {
            return response()->json(['error' => 'Invalid form type'], 400);
        }

        $extraData = []; // <-- This prevents undefined variable errors
        $extraData = $this->getFormTypeExtras($formType, $property, $request->note_id ?? null);
        // ** NEW: if we have a note_id, fetch that note and pass it in **
        // if ($formType === 'notes_tab' && $request->filled('note_id')) {
        //     $note = $property->notes()->findOrFail($request->note_id);
        //     $extraData['note'] = $note;
        // }
        $html = view($viewPath, array_merge(['property' => $property], ['editMode' => true], $extraData))->render();

        // Render the form with additional data
        // $html = view($viewPath, [
        //     'property' => $property,
        //     'editMode' => true,
        //     'stations' => $stations,
        //     'schools' => $schools,
        //     'allstations' => $allstations,
        //     'allschools' => $allschools
        // ])->render();

        // Render the form and return it
        // $html = view($viewPath, ['property' => $property, 'editMode' => true])->render();

        return response()->json(['success' => true, 'form_html' => $html]);
    }


    public function saveForm(Request $request)
    {
        $property = Property::find($request->input('property_id'));
        $formType = $request->input('form_type');
        if (!$property) {
            return response()->json(['error' => 'Property not found'], 404);
        }

        ensureModelBelongsToCurrentAccount($property);

        if ($formType === 'responsibility') {
            abort_unless($this->hasPropertyManagerAddon(), 403, 'The Property Manager add-on is required for responsibility mapping.');
        }

        $extraData = []; // <-- This prevents undefined variable errors

        // Save the form data based on the form type
        switch ($formType) {
            case 'availability_pricing':
                $data = $request->only([
                    'available_from',
                    'local_authority',
                    'tenure',
                    'length_of_lease',
                    'estate_charge',
                    'ground_rent',
                    'service_charge',
                    'miscellaneous_charge',
                    'price',
                    'letting_price',
                    'annual_council_tax',
                    'council_tax_band'
                ]);
                break;
            case 'property_info':
                $data = $request->only([
                    'property_type',
                    'transaction_type',
                    'specific_property_type',
                    'sales_status_description',
                    'letting_status_description'
                ]);
                break;
            case 'property_description':
                $data = $request->only([
                    'sales_status_description',
                    'letting_status_description',
                ]);
                break;
            case 'property_accessibility':
                // $data = $request->only([
                //     'access_arrangement', 'key_highlights', 'nearest_station', 'nearest_school', 'nearest_places', 'useful_information'
                // ]);
                // $extraData = $this->getFormTypeExtras($formType, $property);

                // 1. Pull only the simple fields
                $data = $request->only([
                    'access_arrangement',
                    'key_highlights',
                    'nearest_station',
                    'nearest_school',
                    'useful_information',
                ]);

                // 2. Grab the raw, interleaved array
                $raw = $request->input('nearest_places', []);

                // 3. Merge name+distance pairs into a unified list
                $merged = [];
                foreach ($raw as $item) {
                    // if this entry has a name, start a new pair
                    if (isset($item['name'])) {
                        $merged[] = [
                            'name' => trim($item['name']),
                            'distance' => null,
                        ];
                    }
                    // if it has a distance, attach to the last pair
                    if (isset($item['distance']) && count($merged) > 0) {
                        $merged[count($merged) - 1]['distance'] = $item['distance'];
                    }
                }

                // 4. Filter out any incomplete or blank pairs, then re-index
                $placesList = array_values(array_filter($merged, function ($e) {
                    return $e['name'] !== '' && $e['distance'] !== null;
                }));

                // 5. Validate the cleaned list
                Validator::make(
                    ['nearest_places' => $placesList],
                    [
                        'nearest_places' => 'required|array|min:1',
                        'nearest_places.*.name' => 'required|string',
                        'nearest_places.*.distance' => 'required|numeric|min:0',
                    ]
                )->validate();

                // 6. Build your JSON payload
                $assocPlaces = [];
                foreach ($placesList as $entry) {
                    $assocPlaces[$entry['name']] = $entry['distance'];
                }
                $data['nearest_places'] = json_encode($assocPlaces);

                break;
            case 'property_compliance':
                $data = $request->only([
                    'epc_required',
                    'epc_rating',
                    'gas_safe_acknowledged',
                    'is_gas',
                    'market_on'
                ]);
                break;
            case 'property_media':
                $data = $request->validate([
                    'photos' => 'nullable|string',
                    'floor_plan' => 'nullable|string',
                    'view_360' => 'nullable|url|max:2048',
                    'video_url' => 'nullable|url|max:2048',
                    'instagram_url' => 'nullable|url|max:2048',
                    'youtube_url' => 'nullable|url|max:2048',
                ]);
                $this->ensureUploadsAreAccessible($data['photos'] ?? null);
                $this->ensureUploadsAreAccessible($data['floor_plan'] ?? null);
                break;
            case 'property_features':
                $data = $request->only([
                    'furniture',
                    'kitchen',
                    'heating_cooling',
                    'safety',
                    'other',
                    'bedroom',
                    'bathroom',
                    'reception',
                    'floor',
                    'balcony',
                    'garden',
                    'aspects',
                    'collecting_rent',
                    'square_feet',
                    'square_meter'
                ]);
                break;
            case 'property_services':
                $data = $request->only([
                    'parking',
                    'parking_location',
                    'service',
                    'pets_allow'
                ]);
                if (! in_array($property->property_type, ['lettings', 'both'], true)) {
                    unset($data['service'], $data['pets_allow']);
                }
                break;
            case 'property_status':
                $data = $request->only([
                    'sales_current_status',
                    'letting_current_status',
                    'status_description'
                ]);
                break;
            case 'responsibility':
                $validated = $request->validate([
                    'responsibility_staff' => 'nullable|array',
                    'responsibility_staff.property_manager' => 'nullable|exists:users,id',
                    'responsibility_staff.sales_consultant' => 'nullable|exists:users,id',
                    'responsibility_staff.lettings_consultant' => 'nullable|exists:users,id',
                    'responsibility_staff.sales_manager' => 'nullable|exists:users,id',
                    'responsibility_staff.lettings_manager' => 'nullable|exists:users,id',
                ]);
                $this->ensureStaffUsersAreAccessible(array_values(array_filter(
                    $validated['responsibility_staff'] ?? []
                )));

                $responsibilityTypes = [
                    'property_manager',
                    'sales_consultant',
                    'lettings_consultant',
                    'sales_manager',
                    'lettings_manager',
                ];

                foreach ($responsibilityTypes as $type) {
                    $userId = $validated['responsibility_staff'][$type] ?? null;

                    if (! $userId) {
                        PropertyResponsibility::where('property_id', $property->id)
                            ->where('responsibility_type', $type)
                            ->update(['deleted_by' => Auth::id()]);

                        PropertyResponsibility::where('property_id', $property->id)
                            ->where('responsibility_type', $type)
                            ->delete();

                        continue;
                    }

                    if ($type === 'property_manager') {
                        $this->ensurePropertyManagerCanBeAssigned((int) $userId);
                    }

                    PropertyResponsibility::updateOrCreate(
                        [
                            'property_id' => $property->id,
                            'responsibility_type' => $type,
                        ],
                        [
                            'account_id' => $property->account_id ?: current_account_id(),
                            'property_id' => $property->id,
                            'user_id' => $userId,
                            'status' => 'active',
                            'added_by' => Auth::id(),
                        ]
                    );
                }

                $extraData = $this->getFormTypeExtras($formType, $property);
                $updatedView = view('backend.properties.tabs.responsibility', array_merge([
                    'propertyId' => $property->id,
                    'property' => $property,
                ], $extraData))->render();

                return response()->json([
                    'success' => 'Form updated successfully',
                    'updated_html' => $updatedView,
                    'status' => true,
                    'message' => 'Updated successfully',
                ]);
            case 'notes':
                $data = $request->only([
                    'imp_notes'
                ]);
                break;
            /*case 'notes_tab':
                // $data = $request->only([
                //     'notes'
                // ]);
                // Validate
                $data = $request->validate([
                    'type'    => 'required|string',
                    'content' => 'required|string',
                    'note_id' => 'nullable|exists:notes,id',
                ]);

                if ($data['note_id']) {
                    // Update existing
                    // $note = Notes::where('property_id', $property->id)
                    //             ->findOrFail($data['note_id']);

                    // Update existing note belonging to this property
                    $note = $property->notes()->where('id', $data['note_id'])->firstOrFail();
                    $note->update([
                        'note_type_id'  => $data['note_type_id'],
                        'content' => $data['content'],
                    ]);
                } else {
                    // Create new
                    $note = $property->notes()->create([
                        'note_type_id'  => $data['note_type_id'],
                        'content' => $data['content'],
                    ]);
                }
                break;*/
            default:
                return response()->json(['message' => 'Invalid form type'], 400);
        }

        // Handle different form types dynamically
        // if ($formType === 'availability_pricing') {
        //     $property->available_from = $request->input('available_from');
        //     $property->price = $request->input('price');
        //     $property->letting_price = $request->input('letting_price');
        // } elseif ($formType === 'some_other_form') {
        //     // Handle other form types dynamically
        //     $property->some_field = $request->input('some_field');
        // }

        $property->update($data);

        // 🛠️ Fix: Re-fetch related data like school/station names
        $extraData = $this->getFormTypeExtras($formType, $property);

        // Render updated section
        $updatedView = view("backend.properties.popup_forms.$formType", array_merge(['property' => $property], $extraData))->render();
        // $updatedView = view("backend.properties.popup_forms.$formType", compact('property'))->render();

        return response()->json([
            'success' => 'Form updated successfully',
            'updated_html' => $updatedView,
            'status' => true,
            'message' => 'Updated successfully',
        ]);
    }

    private function getFormTypeExtras($formType, $property, $noteId = null)
    {
        if ($formType === 'property_accessibility') {
            // Fetch all stations and schools
            $allstations = StationName::select('id', 'name')->get();
            $allschools = SchoolName::select('id', 'name')->get();

            // Get the nearest station and school IDs from the property (comma-separated)
            $stationIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_station))));
            $schoolIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->nearest_school))));

            // Fetch names using IDs
            $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
            $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

            return compact('allstations', 'allschools', 'stations', 'schools');
        } elseif ($formType === 'availability_pricing') {
            // $authorities = LocalAuthority::with('group')
            // ->get()
            // ->mapWithKeys(function($auth){
            //     return [$auth->id => $auth->display_name];
            // });
            // return compact('authorities');
            $groups = \App\Models\LocalAuthorityGroup::with([
                'authorities' => function ($q) {
                    $q->orderBy('name');
                }
            ])->orderBy('name')->get();
            return compact('groups');
        } elseif ($formType === 'responsibility') {
            $users = $this->staffUsersForCurrentAccount()
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
            $responsibilities = PropertyResponsibility::with('user')
                ->where('property_id', $property->id)
                ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                ->get();

            return compact('users', 'responsibilities');
        }
        /*elseif ($formType === 'notes_tab') {
            // 1) full list for view mode
            $notes = $property->notes()->with('noteType')->orderBy('updated_at','desc')->get();

            // 2) single note when editing
            $note = null;
            if ($noteId) {
                $note = $property->notes()->with('noteType')->findOrFail($noteId);
            }
            $noteTypes = \App\Models\NoteType::all();
            return compact('notes', 'note', 'noteTypes');
        } */

        return [];
    }

    private function staffUsersForCurrentAccount()
    {
        return User::where(function ($query) {
            $query->where('user_type', 'staff')
                ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'Staff'))
                ->orWhereHas('staff');
        })->when(
            ! auth()->user()?->hasRole('Super Admin'),
            fn ($query) => $query->forAccount(current_account_id())
        );
    }

    private function ensureStaffUsersAreAccessible(array $userIds): void
    {
        if (auth()->user()?->hasRole('Super Admin') || empty($userIds)) {
            return;
        }

        $requestedIds = collect($userIds)->map(fn ($id) => (int) $id)->unique()->values();
        $accessibleIds = $this->staffUsersForCurrentAccount()
            ->whereKey($requestedIds)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id);

        if ($requestedIds->diff($accessibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'responsibility_staff' => ['One or more selected staff members do not belong to your subscriber account.'],
            ]);
        }
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
                'uploads' => ['One or more selected files do not belong to your subscriber account.'],
            ]);
        }
    }

    private function ensureAccountIdsAreAccessible(array $ids, $query, string $field, string $label): void
    {
        if (auth()->user()?->hasRole('Super Admin')) {
            return;
        }

        $requestedIds = collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $accessibleIds = $query
            ->forAccount(current_account_id())
            ->whereKey($requestedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($requestedIds->diff($accessibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => ["One or more selected {$label} do not belong to your subscriber account."],
            ]);
        }
    }

    // // Method to load the tab content for a specific property and tab
    // public function showTabContent($property_id, $tabname)
    // {
    //     // Fetch the property by ID
    //     $property = Property::findOrFail($property_id);

    //     // Determine the content for the tab by loading the appropriate Blade view
    //     $content = $this->getTabContent($tabname, $property);

    //     return response()->json(['content' => $content]);
    // }

    // // A helper method to determine the content of the tab
    // private function getTabContent($tabname, $property)
    // {
    //     // Mapping tab names to view files
    //     switch (strtolower($tabname)) {
    //         case 'property':
    //             return view('backend.properties.tabs.property', compact('property'))->render();
    //         case 'owners':
    //             return view('backend.properties.tabs.owners', compact('property'))->render();
    //         case 'offers':
    //             return view('backend.properties.tabs.offers', compact('property'))->render();
    //         case 'complience':
    //             return view('backend.properties.tabs.complience', compact('property'))->render();
    //         case 'tenancy':
    //             return view('backend.properties.tabs.tenancy', compact('property'))->render();
    //         case 'aps':
    //             return view('backend.properties.tabs.aps', compact('property'))->render();
    //         case 'media':
    //             return view('backend.properties.tabs.media', compact('property'))->render();
    //         case 'teams':
    //             return view('backend.properties.tabs.teams', compact('property'))->render();
    //         case 'contractor':
    //             return view('backend.properties.tabs.contractor', compact('property'))->render();
    //         case 'work offer':
    //             return view('backend.properties.tabs.work_offer', compact('property'))->render();
    //         case 'note':
    //             return view('backend.properties.tabs.note', compact('property'))->render();
    //         default:
    //             return 'Tab content not found';
    //     }
    // }
    // public function showTabContent($property_id, $tabname)
    // {
    //     // Fetch the property data based on the ID
    //     $property = Property::findOrFail($property_id);

    //     // Define the response view and data for the tab
    //     $view = '';
    //     $data = [];

    //     // Use an if-else or switch-case to determine which view to load
    //     switch ($tabname) {
    //         case 'property':
    //             $view = 'backend.properties.tabs.property';
    //             $data = ['property' => $property];
    //             break;

    //         case 'owners':
    //             $view = 'backend.properties.tabs.owners';
    //             $owners = $property->owners; // Assuming a relationship exists
    //             $data = ['owners' => $owners];
    //             break;

    //         case 'offers':
    //             $view = 'backend.properties.tabs.offers';
    //             // $offers = Offer::where('property_id', $property_id)->get(); // Example query
    //             // $data = ['offers' => $offers];
    //             break;

    //         case 'complience':
    //             $view = 'backend.properties.tabs.complience';
    //             $complianceDetails = $property->complianceDetails; // Example model relationship
    //             $data = ['complianceDetails' => $complianceDetails];
    //             break;

    //         case 'tenancy':
    //             $view = 'backend.properties.tabs.tenancy';
    //             $tenancies = $property->tenancies; // Example model relationship
    //             $data = ['tenancies' => $tenancies];
    //             break;

    //         case 'aps':
    //             $view = 'backend.properties.tabs.aps';
    //             $apsDetails = $property->apsDetails; // Example model relationship
    //             $data = ['apsDetails' => $apsDetails];
    //             break;

    //         case 'media':
    //             $view = 'backend.properties.tabs.media';
    //             $media = $property->media; // Example model relationship
    //             $data = ['media' => $media];
    //             break;

    //         case 'teams':
    //             $view = 'backend.properties.tabs.teams';
    //             $teams = $property->teams; // Example model relationship
    //             $data = ['teams' => $teams];
    //             break;

    //         case 'contractor':
    //             $view = 'backend.properties.tabs.contractor';
    //             $contractors = $property->contractors; // Example model relationship
    //             $data = ['contractors' => $contractors];
    //             break;

    //         case 'work-offer':
    //             $view = 'backend.properties.tabs.work-offer';
    //             $workOffers = $property->workOffers; // Example model relationship
    //             $data = ['workOffers' => $workOffers];
    //             break;

    //         case 'note':
    //             $view = 'backend.properties.tabs.note';
    //             $notes = $property->notes; // Example model relationship
    //             $data = ['notes' => $notes];
    //             break;

    //         default:
    //             return response()->json(['error' => 'Invalid tab name'], 404);
    //     }

    //     // Render the appropriate view with the data
    //     return view($view, $data);
    // }

    private function persistProperty(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (UniqueConstraintViolationException $exception) {
            $message = strtolower($exception->getMessage());
            $isPropertyIdentityViolation =
                str_contains($message, 'properties_account_identity_unique')
                || str_contains($message, 'properties_account_identity_huniq')
                || (
                    str_contains($message, 'properties.account_id')
                    && str_contains($message, 'properties.property_identity_hash')
                );

            if (! $isPropertyIdentityViolation) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'line_1' => 'This property address already exists in your account.',
            ]);
        }
    }

    private function getValidationRulesQuick($step, Request $request)
    {
        switch ($step) {
            case 1:
                return [
                    // 'prop_name' => 'required|string|max:255',
                    'line_1' => [
                        'bail',
                        'required',
                        'string',
                        'max:255',
                        new UniquePropertyIdentity(
                            $request->all(),
                            current_account_id(),
                            $request->input('property_id')
                        ),
                    ],
                    'line_2' => 'nullable|string|max:255',
                    'city' => 'required|string|max:100',
                    // 'country' => 'required|string|max:100',
                    'country' => 'required|exists:countries,id',
                    'county' => 'nullable|string|max:50',
                    'currency' => 'nullable|string|max:50',
                    'postcode' => 'nullable|string|max:20',
                    'uprn' => 'nullable|string|max:32',
                ];
            case 2:
                return [
                    // 'line_1' => 'required|string|max:255',
                    // 'line_2' => 'nullable|string|max:255',
                    // 'city' => 'required|string|max:100',
                    // 'country' => 'required|string|max:100',
                    // 'postcode' => 'required|string|max:20',
                    'specific_property_type' => 'required|string',
                    'property_type' => 'required|string',
                ];
            case 3:
                return [
                    // 'specific_property_type' => 'required|string',
                    'bedroom' => 'required|string',
                ];
            case 4:
                return [
                    'bathroom' => 'required|string',

                ];
            case 5:
                return [
                    'reception' => 'required|string',

                ];
            case 6:
                return [
                    'frunishing_type' => 'required|string',
                ];
            case 7:
                return [
                    'parking' => 'required|string',
                    'parking_location' => 'nullable',
                    'garden' => 'required|string',
                    'balcony' => 'required|string',
                ];
            case 8:
                return [
                    'price' => 'numeric',
                    'letting_price' => 'numeric',
                    'management' => 'required|string',
                ];

            default:
                return [];
        }
    }

    public function brochure(Property $property)
    {
        if (! $this->canAccessProperty(auth()->user(), $property)) {
            abort(403, 'Unauthorized to download this brochure.');
        }

        $property->load(['creator.ownedCompany.branches', 'localAuthority']);
        $company = $property->creator?->ownedCompany;
        $branch = $company?->branches?->first();
        $stationIds = collect(explode(',', (string) $property->nearest_station))
            ->map(fn($id) => trim($id))
            ->filter()
            ->values();
        $schoolIds = collect(explode(',', (string) $property->nearest_school))
            ->map(fn($id) => trim($id))
            ->filter()
            ->values();
        $stations = $stationIds->isNotEmpty()
            ? StationName::whereIn('id', $stationIds)->pluck('name')->toArray()
            : [];
        $schools = $schoolIds->isNotEmpty()
            ? SchoolName::whereIn('id', $schoolIds)->pluck('name')->toArray()
            : [];
        $photoIds = collect(explode(',', (string) $property->photos))
            ->map(fn($id) => trim($id))
            ->filter()
            ->take(4)
            ->values();
        $photoPaths = Upload::whereIn('id', $photoIds)
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount($property->account_id ?: current_account_id()))
            ->get()
            ->sortBy(fn($upload) => $photoIds->search((string) $upload->id))
            ->map(function ($upload) {
                if (! empty($upload->external_link)) {
                    return $upload->external_link;
                }

                $path = public_path('storage/' . $upload->file_name);

                return file_exists($path) ? $path : null;
            })
            ->filter()
            ->values();

        $pdf = Pdf::loadView(
            'backend.properties.brochure',
            compact('property', 'company', 'branch', 'photoPaths', 'stations', 'schools'),
            [],
            ['format' => 'A4']
        );

        $filename = 'property-brochure-' . ($property->prop_ref_no ?: $property->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function canAccessProperty($user, Property $property): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ((int) $property->account_id !== (int) current_account_id()) {
            return false;
        }

        if ($user->hasRole('Property Manager')) {
            return true;
        }

        if (is_landlord_plan_user($user)) {
            return true;
        }

        if (($user->hasRole('Staff') || $user->hasRole('Test')) && (int) $property->created_by === (int) $user->id) {
            return true;
        }

        if ($user->hasRole('Estate Agent')) {
            $createdUserIds = $user->createdUsers()->pluck('id')->push($user->id);
            return $createdUserIds->contains($property->created_by);
        }

        if ($user->hasRole('Tenant')) {
            return \App\Models\TenantMember::where('user_id', $user->id)
                ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
                ->where('tenant_members.account_id', current_account_id())
                ->where('tenancies.account_id', current_account_id())
                ->where('tenancies.status', 'Active')
                ->where('tenancies.property_id', $property->id)
                ->exists();
        }

        return false;
    }

    private function getValidationRules($step, Request $request)
    {
        switch ($step) {
            case 1:
                return [
                    'prop_name' => 'required|string|max:255',
                    'line_1' => [
                        'bail',
                        'required',
                        'string',
                        'max:255',
                        new UniquePropertyIdentity(
                            $request->all(),
                            current_account_id(),
                            $request->input('property_id')
                        ),
                    ],
                    'line_2' => 'nullable|string|max:255',
                    'city' => 'required|string|max:100',
                    'country' => 'required|string|max:100',
                    'postcode' => 'required|string|max:20',
                    'uprn' => 'nullable|string|max:32',
                ];
            case 2:
                return [
                    'property_type' => 'required|string',
                    'transaction_type' => 'required|string',
                    'specific_property_type' => 'required|string',
                ];
            case 3:
                return [
                    'bedroom' => 'required|string',
                    'bathroom' => 'required|string',
                    'reception' => 'required|string',
                    'parking' => 'required|boolean',
                    'parking_location' => 'nullable',
                    'balcony' => 'required|boolean',
                    'garden' => 'required|boolean',
                    'service' => 'nullable|string',
                    'collecting_rent' => 'required|boolean',
                    'floor' => 'required|string',
                    'square_feet' => 'nullable|numeric|min:1',
                    'square_meter' => 'nullable|numeric|min:1',
                    'aspects' => 'required|string',
                ];
            case 4:
                return [
                    'sales_current_status' => 'required_if:property_type,sales, both|string',
                    'letting_current_status' => 'required_if:property_type,lettings, both|string',
                    'pets_allow' => 'required',
                    'sales_status_description' => 'nullable|string',
                    'letting_status_description' => 'nullable|string',
                    'available_from' => 'required|date',
                    'market_on' => 'required',
                    // 'market_on' => 'required|array',
                    // 'market_on.*' => 'in:resisquare,rightmove,zoopla,onthemarket',
                ];
            case 5:
                return [
                    'furniture' => 'array|nullable',
                    // 'furniture.*' => 'in:Furnished,Unfurnished,Flexible',
                    'kitchen' => 'array|nullable',
                    // 'kitchen.*' => 'in:Undercounter refrigerator without freezer,Dishwasher,Gas oven,Gas hob,Washing machine,Dryer,Electric hob,Electric oven,Washer,Washer Dryer,Undercounter refrigerator with freezer,Tall refrigerator with freezer',
                    'heating_cooling' => 'array|nullable',
                    // 'heating_cooling.*' => 'in:Air conditioning,Underfloor heating,Electric,Gas,Central heating,Comfort cooling,Portable heater',
                    'safety' => 'array|nullable',
                    // 'safety.*' => 'in:External CCTV Intruder alarm system,Smoke alarm,Carbon monoxide detector,Window locks,Security key lock',
                    'other' => 'array|nullable',
                    // 'other.*' => 'in:Roof Garden,Business Centre,Concierge,Lift,Pets Allowed,Pets Allowed With Licence,TV,Fireplace,Wood flooring,Double glazing,Not suitable for wheelchair users,Gym,None',
                ];
            case 6:
                return [
                    'access_arrangement' => 'required|string',
                    'key_highlights' => 'required|string',
                    'nearest_station' => 'required',
                    'nearest_school' => 'required',
                    // 'nearest_religious_places' => 'required|array',
                    'useful_information' => 'required|string',
                ];
            case 7:
                return [
                    // 'price' => 'required|numeric',
                    'letting_price' => 'nullable|numeric',
                    'ground_rent' => 'nullable|numeric',
                    'service_charge' => 'nullable|numeric',
                    'annual_council_tax' => 'nullable|numeric',
                    'council_tax_band' => 'nullable|string|max:50',
                    'local_authority' => 'nullable|string|max:50',
                    'estate_charge' => 'nullable|numeric|max:50',
                    'miscellaneous_charge' => 'nullable|numeric|max:50',
                    // 'estate_charges.amount' => 'nullable|numeric|max:50',
                    'tenure' => 'required',
                    'length_of_lease' => 'nullable|integer',
                ];
            case 8:
                return [
                    'epc_rating' => 'required',
                    'is_gas' => 'required',
                    'gas_safe_acknowledged' => 'nullable',
                ];
            case 9:
                return [
                    // Validate that 'photos' is a comma-separated list of integers (file IDs)
                    'photos' => 'nullable|string',  // The input is a string of IDs
                    'photos.*' => 'nullable|integer|exists:uploads,id', // Validate each ID

                    // Validate that 'floor_plan' is a comma-separated list of integers (file IDs)
                    'floor_plan' => 'nullable|string',  // The input is a string of IDs
                    'floor_plan.*' => 'nullable|integer|exists:uploads,id', // Validate each ID

                    // Validate that 'view_360' is a comma-separated list of integers (file IDs)
                    'view_360' => 'nullable|string',  // The input is a string of IDs, we’ll split it into an array later
                    'view_360.*' => 'nullable|integer|exists:uploads,id', // Validate each ID

                    // 'photos.*' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048', // For multiple photos
                    // 'floor_plan.*' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048', // For the floor plan
                    // 'view_360.*' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048', // For 360 view
                    'video_url' => 'nullable|url|max:255', // For the video URL
                ];
            case 10:
                return [
                    'user_id.*' => 'required|exists:users,id',
                    'designation_id.*' => 'required|exists:designations,id',
                    'branch_id.*' => 'required|exists:branches,id',
                    'commission_percentage.*' => 'required|numeric|min:0|max:100',
                    'commission_amount.*' => 'required|numeric|min:0',
                ];
            default:
                return [];
        }
    }

    private function handleImageUploads(Request $request, $property)
    {
        // Handle photos upload
        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
            $photoPaths = [];

            foreach ($photos as $photo) {
                $photoPath = $photo->store('property_photos', 'public');  // Save to public disk
                $photoPaths[] = $photoPath;
            }

            // Store the paths as JSON in the photos column
            $property->photos = json_encode($photoPaths);
            $property->save();
        }

        // Handle floor_plan photos upload
        if ($request->hasFile('floor_plan')) {
            $floor_planphotos = $request->file('floor_plan');
            $floor_planphotoPaths = [];

            foreach ($floor_planphotos as $photo) {
                $floor_planphotoPath = $photo->store('property_floor_plans', 'public');  // Save to public disk
                $floor_planphotoPaths[] = $floor_planphotoPath;
            }

            // Store the paths as JSON in the floor_plan column
            $property->floor_plan = json_encode($floor_planphotoPaths);
            $property->save();
        }

        // Handle view_360 photos upload
        if ($request->hasFile('view_360')) {
            $view_360photos = $request->file('view_360');
            $view_360photoPaths = [];

            foreach ($view_360photos as $photo) {
                $photoPath = $photo->store('property_360_views', 'public');  // Save to public disk
                $view_360photoPaths[] = $photoPath;
            }

            // Store the paths as JSON in the view_360 column
            $property->view_360 = json_encode($view_360photoPaths);
            $property->save();
        }
    }

    // // Generate a unique property reference number
    // private function generatePropertyRefNumber()
    // {
    //     // Find the last inserted property
    //     $lastProperty = Property::orderBy('id', 'desc')->first();

    //     // Extract and increment the numeric part
    //     if ($lastProperty && preg_match('/RESISQP(\d+)/', $lastProperty->prop_ref_no, $matches)) {
    //         $number = (int)$matches[1] + 1;
    //     } else {
    //         $number = 1; // Start from 1 if no property exists
    //     }

    //     // Format the new reference number (e.g., RESISQP0000001)
    //     return 'RESISQP' . str_pad($number, 7, '0', STR_PAD_LEFT);
    // }


    /**
     * AJAX method to list properties for a select2 dropdown.
     */
    // This method is used to fetch properties based on a search term.
    // It returns a JSON response with the properties that match the search criteria.
    // The properties are filtered by their reference number, name, or address line 1.
    // The results are limited to 10 properties and formatted for use with select2.
    public function ajaxList(Request $request)
    {
        $term = $request->input('q');

        $query = Property::query();
        $this->scopePropertyQuery($query);

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('prop_ref_no', 'like', "%$term%")
                    ->orWhere('prop_name', 'like', "%$term%")
                    ->orWhere('line_1', 'like', "%$term%");
            });
        }

        $properties = $query
            ->select('id', 'prop_ref_no', 'prop_name', 'line_1', 'city')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $results = $properties->map(function ($prop) {
            return [
                'id' => $prop->id,
                'text' => "{$prop->prop_ref_no} - {$prop->prop_name}, {$prop->line_1}, {$prop->city}",
            ];
        });

        return response()->json(['results' => $results]);
    }

    private function scopePropertyQuery($query)
    {
        $user = auth()->user();
        $accountId = current_account_id();

        if (! $user?->hasRole('Super Admin')) {
            $query->forAccount($accountId);
        }

        if ($user && $accountId) {
            $portalAccessService = app(PortalAccessService::class);

            if ($portalAccessService->isPortalUser($user, $accountId)) {
                $query->whereIn('id', $portalAccessService->accessiblePropertyIds($user, $accountId));
            }
        }

        return $query;
    }

    private function authorizePortalPropertyEdit(Property $property): void
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

        $participant = $portalAccessService->getParticipant($user, $property);

        abort_unless($participant, 403, 'You do not have access to this property.');
        abort_if(in_array($participant->participant_type, ['tenant', 'contractor'], true), 403, 'You cannot edit this property.');
        abort_unless($portalAccessService->canAccessProperty($user, $property, 'full'), 403, 'You cannot edit this property.');
    }

    private function ensurePropertyManagerCanBeAssigned(int $userId): void
    {
        if (! $userId || $this->bypassesSaasPlanLimits()) {
            return;
        }

        $account = current_account();

        abort_unless($account, 403, 'No active SaaS subscription was found for this account.');

        $alreadyIncluded = AccountUser::query()
            ->where('account_id', $account->id)
            ->where('user_id', $userId)
            ->where('member_type', 'property_manager')
            ->where('status', 'active')
            ->exists();

        if (! $alreadyIncluded) {
            $this->abortIfSaasLimitDenied('property_manager');
        }

        AccountUser::updateOrCreate(
            [
                'account_id' => $account->id,
                'user_id' => $userId,
            ],
            [
                'member_type' => 'property_manager',
                'access_level' => 'edit',
                'can_login' => true,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]
        );
    }

    private function statementFilters(Request $request): array
    {
        $preset = $request->query('preset', 'this_month');
        $from = $request->query('date_from');
        $to = $request->query('date_to');
        $today = now();

        switch ($preset) {
            case 'last_month':
                $start = $today->copy()->subMonthNoOverflow()->startOfMonth();
                $end = $today->copy()->subMonthNoOverflow()->endOfMonth();
                break;
            case 'ytd':
                $start = $today->copy()->startOfYear();
                $end = $today;
                break;
            case 'custom':
                $start = $from ? \Carbon\Carbon::parse($from) : $today->copy()->startOfMonth();
                $end = $to ? \Carbon\Carbon::parse($to) : null;
                break;
            case 'this_month':
            default:
                $start = $today->copy()->startOfMonth();
                $end = $today;
                break;
        }

        return [
            'preset' => $preset,
            'date_from' => $start->toDateString(),
            'date_to' => $end?->toDateString(),
        ];
    }

    public function getAllTabs(Property $property)
    {
        $user = auth()->user();
        $accountId = current_account_id();
        $portalAccessService = app(PortalAccessService::class);
        $isPortal = $accountId && $portalAccessService->isPortalUser($user, $accountId);

        ensureModelBelongsToCurrentAccount($property);
        $this->assertPropertyVisibleToUser($user, $property, $isPortal, $portalAccessService);

        $html = [];
        foreach ($this->tabsForUser($user, $property, $isPortal, $portalAccessService) as $tab) {
            $key = strtolower($tab['name']);
            $html[$key] = $this->getTabContent($key, $property->id, $property);
        }

        return $html;
    }

    public function bulkAction(Request $request)
    {
        $user = auth()->user();
        $accountId = current_account_id();
        $portalAccessService = app(PortalAccessService::class);
        abort_if($accountId && $portalAccessService->isPortalUser($user, $accountId), 403);
        abort_unless($user->can('delete properties'), 403);

        $request->validate([
            'action' => 'required|string|in:archive,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:properties,id',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        $query = Property::whereIn('id', $ids)->where('account_id', $accountId);
        $affected = 0;

        switch ($action) {
            case 'archive':
                $affected = $query->count();
                $query->each(function ($property) {
                    $this->persistProperty(function () use ($property) {
                        $property->deleted_by = auth()->id();
                        $property->save();
                        $property->delete();
                    });
                });
                break;
            case 'delete':
                $affected = $query->count();
                $query->each(function ($property) {
                    $this->persistProperty(function () use ($property) {
                        $property->forceDelete();
                    });
                });
                break;
        }

        return response()->json(['affected' => $affected]);
    }

    private function shouldUseLandlordWizard(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('Landlord')) {
            return false;
        }

        return ! $user->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent']);
    }

    private function propertyCreateRoute(): string
    {
        return $this->shouldUseLandlordWizard()
            ? 'admin.properties.index'
            : 'admin.properties.quick';
    }


}
