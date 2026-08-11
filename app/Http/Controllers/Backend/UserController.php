<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use App\Mail\MailManager;
use App\Models\Company;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\EmailTemplate;
// use App\Models\BankDetails;
use App\Models\Nationality;
use App\Models\NoteType;
// use App\Models\UserCategory;
use App\Models\AccountUser;
use App\Models\OwnerGroup;
use App\Models\PropertyParticipant;
use App\Models\Property;
use App\Models\User;
use App\Models\UserCategory;
use App\Services\Accounting\StatementService;
use App\Services\Saas\AccountLimitService;
use App\Services\Saas\PortalAccessService;
// use App\Http\Controllers\Backend\NotesController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController
{
    use EnforcesSaasPlanLimits;

    public function profile()
    {
        $authUser = auth()->user()->load([
            'ownedCompany.branches',
            'ownedCompany.ownerTransfers.oldOwner',
            'ownedCompany.ownerTransfers.newOwner',
            'ownedCompany.ownerTransfers.transferredBy',
        ]);
        // $countryName = Country::find($authUser->country_id)?->name ?? 'N/A';
        // Use cached countries to find the user's country
        $countryName = Country::allCached()->firstWhere('id', $authUser->country_id)->name ?? 'N/A';
        $transferUsers = User::where('id', '!=', $authUser->id)
            ->where(function ($query) {
                $query->whereIn('user_type', ['agent', 'estate_agent'])
                    ->orWhereHas('roles', fn($roleQuery) => $roleQuery->whereIn('name', ['Agent', 'Estate Agent']));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('backend.users.profile.show', compact('authUser', 'countryName', 'transferUsers'));
    }

    public function profileEdit()
    {
        // Fetch the authenticated user

        $user = User::with('country', 'details', 'ownedCompany')->find(auth()->id());
        // $categories = UserCategory::all();
        $countries = Country::allCached();
        $canUseCompanyProfile = $this->canManageCompanyProfile($user);
        $canUseInvoiceBranding = $this->canUseInvoiceBrandingFeature();
        $company = $canUseCompanyProfile ? $this->ownedCompanyFor($user) : null;

        return view('backend.users.profile.edit', compact('user', 'countries', 'company', 'canUseCompanyProfile', 'canUseInvoiceBranding'));
        // return view('backend.users.profile.edit', compact('user', 'categories', 'countries'));
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();
        $canUseCompanyProfile = $this->canManageCompanyProfile($user);
        $canUseInvoiceBranding = $this->canUseInvoiceBrandingFeature();

        if (! $canUseCompanyProfile && $this->requestHasCompanyProfileInput($request)) {
            flash('Your current plan does not include estate agency company profile access.')->error();

            return back()->withInput();
        }

        if (! $canUseInvoiceBranding && ($request->hasFile('company_logo') || $request->hasFile('company_stamp'))) {
            flash('Your current plan does not include invoice branding.')->error();

            return back()->withInput();
        }

        $rules = [
            'title' => 'required|string|max:10',
            'first_name' => 'required|string|max:55',
            'middle_name' => 'nullable|string|max:55',
            'last_name' => 'required|string|max:55',
            'emails' => 'required|array|min:1',
            'emails.*' => 'nullable|email|max:255',
            'primary_email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phones' => 'required|array|min:1',
            'phones.*' => 'nullable|string|max:20',
            'primary_phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:15',
            'city' => 'required|string|max:55',
            // 'country' => 'required|string|max:55',
            'country_id' => 'nullable|exists:countries,id',
            'profile_picture' => 'nullable|image|max:2048', // 2MB max
            // 'category_id' => 'required|exists:users_categories,id',
            // 'role' => 'required|exists:roles,name',
        ];

        if ($canUseCompanyProfile) {
            $rules = array_merge($rules, [
                'company.name' => 'nullable|string|max:255',
                'company.registration_number' => 'nullable|string|max:255',
                'company.registered_address' => 'nullable|string',
                'company.communication_address' => 'nullable|string',
                'company.emails' => 'nullable|array',
                'company.emails.*' => 'nullable|email|max:255',
                'company.phones' => 'nullable|array',
                'company.phones.*' => 'nullable|string|max:50',
                'company.vat_number' => 'nullable|string|max:255',
                'company.website' => 'nullable|url|max:255',
                'company.social_media' => 'nullable|array',
                'company.social_media.*' => 'nullable|url|max:255',
                'company.services' => 'nullable|array',
                'company.services.*' => 'nullable|in:lettings,sales,property_management',
            ]);

            if ($canUseInvoiceBranding) {
                $rules = array_merge($rules, [
                    'company_logo' => 'nullable|image|max:4096',
                    'company_stamp' => 'nullable|image|max:4096',
                ]);
            }
        }

        $validatedData = $request->validate($rules);
        $contactEmails = array_values(array_unique(array_filter($validatedData['emails'] ?? [])));
        $contactPhones = array_values(array_unique(array_filter($validatedData['phones'] ?? [])));

        if (! in_array($validatedData['primary_email'], $contactEmails, true)) {
            return back()->withErrors(['primary_email' => 'The primary email must be one of the entered emails.'])->withInput();
        }

        if (! in_array($validatedData['primary_phone'], $contactPhones, true)) {
            return back()->withErrors(['primary_phone' => 'The primary phone must be one of the entered phone numbers.'])->withInput();
        }

        // Handle profile picture removal
        if ($request->has('remove_profile_picture') && $user->profile_picture) {
            if (Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = null;
        }

        // Handle file upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');

            // Delete old picture if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $user->profile_picture = $path;
        }

        $fullName = trim($request->input('first_name').' '.$request->input('middle_name').' '.$request->input('last_name'));

        DB::transaction(function () use ($request, $user, $validatedData, $fullName, $contactEmails, $contactPhones, $canUseCompanyProfile) {
            $user->update([
                'title' => $validatedData['title'],
                'first_name' => $validatedData['first_name'],
                'middle_name' => $validatedData['middle_name'],
                'last_name' => $validatedData['last_name'],
                'name' => $fullName,
                'phone' => $validatedData['primary_phone'],
                'email' => $validatedData['primary_email'],
                'address_line_1' => $validatedData['address_line_1'],
                'address_line_2' => $validatedData['address_line_2'],
                'postcode' => $validatedData['postcode'],
                'city' => $validatedData['city'],
                // 'country' => $validatedData['country'],
                'country_id' => $validatedData['country_id'] ?? null,
                // 'category_id' => $validatedData['category_id'],
                'updated_by' => auth()->id(),
                'profile_picture' => $user->profile_picture, // set new path if uploaded
            ]);

            $user->details()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'emails' => $contactEmails,
                    'primary_email' => $validatedData['primary_email'],
                    'phones' => $contactPhones,
                    'primary_phone' => $validatedData['primary_phone'],
                ]
            );

            if ($canUseCompanyProfile) {
                $this->syncOwnedCompany($request, $user);
            }
        });

        // Sync new role (removes old ones and assigns the new one)
        // $user->syncRoles([$validatedData['role']]);
        flash('Profile updated successfully!')->success();

        return redirect()->route('admin.users.profile.show');
    }

    private function canManageCompanyProfile(User $user): bool
    {
        if (! $this->bypassesSaasPlanLimits() && $this->saasLimitError('company_profile') !== null) {
            return false;
        }

        $manageOwnCompanyPermissionExists = \Spatie\Permission\Models\Permission::where('name', 'manage own company')
            ->where('guard_name', 'web')
            ->exists();

        return in_array($user->user_type, ['agent', 'estate_agent'], true)
            || $user->hasAnyRole(['Agent', 'Estate Agent', 'Super Admin'])
            || $user->ownedCompany()->exists()
            || ($manageOwnCompanyPermissionExists && $user->hasEffectivePermission('manage own company'));
    }

    private function ownedCompanyFor(User $user): Company
    {
        return $user->ownedCompany()->firstOrCreate(
            ['owner_user_id' => $user->id],
            [
                'account_id' => current_account_id(),
                'name' => $user->company?->name ?: ($user->name ? $user->name . ' Company' : 'My Company'),
                'created_by' => $user->id,
            ]
        );
    }

    private function syncOwnedCompany(Request $request, User $user): void
    {
        $company = $this->ownedCompanyFor($user);
        $companyInput = $request->input('company', []);
        $canUseInvoiceBranding = $this->canUseInvoiceBrandingFeature();

        if ($request->hasFile('company_logo') && $canUseInvoiceBranding) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $companyInput['logo_path'] = $request->file('company_logo')->store('company_logos', 'public');
        }

        if ($request->hasFile('company_stamp') && $canUseInvoiceBranding) {
            if ($company->stamp_path && Storage::disk('public')->exists($company->stamp_path)) {
                Storage::disk('public')->delete($company->stamp_path);
            }
            $companyInput['stamp_path'] = $request->file('company_stamp')->store('company_stamps', 'public');
        }

        $company->update([
            'account_id' => $company->account_id ?: current_account_id(),
            'name' => $companyInput['name'] ?? $company->name,
            'registration_number' => $companyInput['registration_number'] ?? null,
            'registered_address' => $companyInput['registered_address'] ?? null,
            'communication_address' => $companyInput['communication_address'] ?? null,
            'emails' => array_values(array_filter($companyInput['emails'] ?? [])),
            'phones' => array_values(array_filter($companyInput['phones'] ?? [])),
            'logo_path' => $companyInput['logo_path'] ?? $company->logo_path,
            'stamp_path' => $companyInput['stamp_path'] ?? $company->stamp_path,
            'vat_number' => $companyInput['vat_number'] ?? null,
            'website' => $companyInput['website'] ?? null,
            'social_media' => array_filter($companyInput['social_media'] ?? []),
            'services' => array_values($companyInput['services'] ?? []),
            'updated_by' => $user->id,
        ]);

    }

    public function profilePasswordUpdate(Request $request)
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Check if the current password is correct
        if (! Hash::check($validatedData['current_password'], $user->password)) {
            flash('Current password is incorrect.')->error();

            return back();
        }

        // Prevent password reuse
        if (Hash::check($validatedData['new_password'], $user->password)) {
            flash('New password cannot be the same as your current password.')->error();

            return back();
        }

        // Update the password
        $user->update([
            'password' => Hash::make($validatedData['new_password']),
        ]);

        flash('Password updated successfully!')->success();

        return redirect()->route('admin.users.profile.show');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch categories for your filter dropdown
        // $categories = UserCategory::all();
        $roles = Role::whereNotIn('name', ['Staff', 'Super Admin'])->get();

        // Build base users query, eager‑loading all relationships
        $usersQuery = User::with([
            // 'category',
            'roles',
            'details',
            'tenancies',
            'repairIssues',
            'tenantMembers',
            'documents',
        ]);
        $this->scopeContactsToCurrentUser($usersQuery);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply a category filter if provided
        // if ($request->filled('category')) {
        //     $usersQuery->where('category_id', $request->category);
        // }

        // 🔍 Apply role filter if provided
        if ($request->filled('role')) {
            $usersQuery->role($request->role); // Spatie's `role()` scope
        }

        // Tenants only see themselves
        if (auth()->user()->hasRole('Tenant')) {
            $usersQuery->where('id', auth()->id());
        }

        // Fetch all users (newest first)
        // $users = $usersQuery->orderBy('id', 'desc')->exclude('user_type', 'staff')->get();
        // $users = $usersQuery->orderBy('id', 'desc')->whereDoesntHave('roles', function ($query) {
        //     $query->whereIn('name', ['Staff', 'Super Admin']);
        // })->get();

        // Exclude users with user_type 'staff'
        // $users = $users->where('user_type', '!=', 'staff');
        // $users = $users->where('user_type', '!=', 'super_admin');

        $users = $usersQuery->orderBy('id', 'desc')
            ->where(function ($query) {
                $query->whereNull('user_type')
                    ->orWhereNotIn('user_type', ['staff', 'super_admin']);
            })
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['Staff', 'Super Admin']);
            })
            ->paginate(15);

        // If AJAX request for user list only (search/pagination)
        if ($request->ajax() && $request->has('list_only')) {
            // If a highlight_id is given, jump to the page that contains it
            if ($request->filled('highlight_id')) {
                $highlightId = (int) $request->highlight_id;
                $perPage = 15;

                // Rebuild a fresh query with the same filters to find the position
                $positionQuery = User::orderBy('id', 'desc')
                    ->where(function ($q) {
                        $q->whereNull('user_type')
                          ->orWhereNotIn('user_type', ['staff', 'super_admin']);
                    })
                    ->whereDoesntHave('roles', function ($q) {
                        $q->whereIn('name', ['Staff', 'Super Admin']);
                    });
                $this->scopeContactsToCurrentUser($positionQuery);

                if ($request->filled('search')) {
                    $search = $request->search;
                    $positionQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
                }

                $position = $positionQuery->pluck('id')->search($highlightId);

                if ($position !== false) {
                    $page  = (int) floor($position / $perPage) + 1;
                    $users = $positionQuery->paginate($perPage, ['*'], 'page', $page);
                }
            }
            return response()->json([
                'html' => view('backend.users.partials.user-list', compact('users'))->render(),
            ]);
        }

        // If no users at all, redirect to quick-create
        if ($users->isEmpty()) {
            flash("You don't have any users yet!")->error();

            return redirect()->route('admin.users.create');
        }

        // Decide which user/tab to show
        $userId  = $request->query('user_id');
        $tabName = $request->query('tabname', 'Contact');
        $role    = $request->query('role', '');

        // If no user_id in URL, redirect to first user (preserving role filter)
        if (!$userId) {
            $firstUser = $users->first();
            return redirect()->route('admin.users.index', array_filter([
                'user_id' => $firstUser->id,
                'tabname' => $tabName,
                'role'    => $role,
            ]));
        }

        // Try to find the requested user
        $user = User::with([
            'roles',
            'details',
            'tenancies',
            'repairIssues',
            'tenantMembers',
            'documents',
        ])->find($userId);

        if (!$user) {
            // Invalid/deleted user_id — redirect to first user (preserving role filter)
            $firstUser = $users->first();
            return redirect()->route('admin.users.index', array_filter([
                'user_id' => $firstUser->id,
                'tabname' => $tabName,
                'role'    => $role,
            ]));
        }

        if (auth()->user()->hasRole('Landlord') && (int) $user->created_by !== (int) auth()->id()) {
            $firstUser = $users->first();

            return redirect()->route('admin.users.index', array_filter([
                'user_id' => $firstUser->id,
                'tabname' => $tabName,
                'role' => $role,
            ]));
        }

        $this->ensureUserAccessible($user);

        // Define your tab list
        $tabs = [
            ['name' => 'Contact'],
            ['name' => 'Appointments'],
            ['name' => 'Link'],
            ['name' => 'Bank'],
            ['name' => 'Contact Owner'],
            ['name' => 'Letters'],
            ['name' => 'Compliance'],
            ['name' => 'Documents'],
            ['name' => 'Notes'],
            ['name' => 'Statement'],
        ];

        if (strtolower($tabName) === 'statement' && $request->query('format') === 'csv') {
            $filters = $this->statementFilters($request);
            $statement = app(StatementService::class)
                ->contactStatement($user->id, $user->company_id ?? null, $filters['date_from'], $filters['date_to']);
            $statement['summary']['balance_due'] = $statement['closing'];

            return $this->streamStatementCsv($user, $statement);
        }

        $content = $this->getTabContent($request, $tabName, $userId, $user); // Dynamically get content for the tab and property

        // Check if the request is via AJAX (this handles dynamic content loading)
        if ($request->ajax()) {
            return response()->json(['content' => $content, 'tabName' => $tabName]);
        }

        return view('backend.users.index', compact('users', 'roles', 'tabs', 'tabName', 'userId', 'user', 'content'));
        // return view('backend.users.index', compact('users', 'categories','tabs', 'tabName', 'userId', 'user', 'content'));
    }

    private function getTabContent(Request $request, $tabname, $userId, $user)
    {
        switch (strtolower($tabname)) {
            case 'contact':
                return view('backend.users.tabs.user_details', compact('userId', 'user'))->render();

            case 'appointments':
                return view('backend.users.tabs.appointments', compact('userId', 'user'))->render();

            case 'link':
                $propertyIds = [];

                if (! empty($user->selected_properties)) {
                    $decoded = is_array($user->selected_properties)
                        ? $user->selected_properties
                        : json_decode($user->selected_properties, true);

                    if (is_array($decoded)) {
                        $propertyIds = $decoded;
                    }
                }

                $activeOwnerPropertyIds = OwnerGroup::query()
                    ->activeForUser((int) $user->id)
                    ->pluck('property_id')
                    ->all();

                $propertyIds = array_values(array_unique(array_merge(
                    $propertyIds,
                    $activeOwnerPropertyIds
                )));

                $properties = ! empty($propertyIds)
                    ? Property::query()
                        ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                        ->with('countryRelation:id,name')
                        ->whereIn('id', $propertyIds)
                        ->get()
                    : collect(); // empty collection if no IDs

                return view('backend.users.tabs.linked', compact('userId', 'user', 'properties'))->render();

            case 'bank':
                // Fetch bank details related to the specific user by user ID
                $bankDetails = $user->bankDetails()->orderByDesc('is_primary')->orderBy('updated_at', 'desc')->get();
                // Ensure it's an empty collection if no bank details are found
                if ($bankDetails->isEmpty()) {
                    $bankDetails = collect();  // Make sure it's an empty collection, not null
                }

                return view('backend.users.tabs.bank_details', compact('userId', 'user', 'bankDetails'))->render();

            case 'user owner':
                $user->load('creator.roles'); // Eager load role

                return view('backend.users.tabs.user_owner', compact('userId', 'user'))->render();

            case 'letters':
                return view('backend.users.tabs.letters', compact('userId', 'user'))->render();

            case 'compliance':
                // load all nationalities keyed by id→name
                $nationalities = Nationality::orderBy('name')->pluck('name', 'id');
                // load all users for the “checked by” dropdown
                $usersQuery = User::orderBy('name');
                $this->scopeUsersToCurrentAccount($usersQuery);
                $users = $usersQuery->pluck('name', 'id');
                // eager-load the staff member who did the check
                $user->load('details.userCheckedBy');

                return view('backend.users.tabs.compliance', compact('userId', 'user', 'users', 'nationalities'))->render();

            case 'documents':
                $documents = $user->documents()
                    ->with('documentType')
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->orderByDesc('updated_at')
                    ->paginate(5);
                $documentTypes = DocumentType::all();

                return view('backend.users.tabs.documents', compact('userId', 'user', 'documents', 'documentTypes'))->render();

            case 'notes':
                // Fetch the notes related to the specific user by user ID
                $notes = $user->notes()
                    ->with('noteType')
                    ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                    ->orderByDesc('updated_at')
                    ->paginate(5);
                $noteTypes = NoteType::all();

                return view('backend.users.tabs.notes', compact('userId', 'user', 'notes', 'noteTypes'))->render();

            case 'statement':
                $filters = $this->statementFilters($request);
                $statement = app(StatementService::class)
                    ->contactStatement($user->id, $user->company_id ?? null, $filters['date_from'], $filters['date_to']);
                $statement['summary']['balance_due'] = $statement['closing'];

                return view('backend.users.tabs.statement', [
                    'user' => $user,
                    'filters' => $filters,
                    'statement' => $statement,
                ])->render();

            default:
                return 'Tab content not found';
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(User $user)
    {
        abort_unless(auth()->user()?->can('create contacts'), 403);

        $roles = Role::whereNotIn('name', ['Staff', 'Super Admin'])->get();

        return view('backend.users.create', compact('user', 'roles'));
        // $categories = UserCategory::all();
        // return view('backend.users.create', compact('user', 'categories'));
        // return view('backend.users.create'); // Return the create user view
    }

    public function userStore(Request $request)
    {
        abort_unless($request->user()?->can('create contacts'), 403);

        // Validate data based on the current step
        if ($request->has('step')) {

            // ✅ track whether we just created a new user
            $isNewUser = false;

            // Validate the request data
            $validatedData = $request->validate($this->getValidationRulesQuick($request->step, $request->user_id ?? null));

            // Get user_id from the request
            $user_id = $request->user_id;

            // Check if first name, middle name, and last name are present
            $fullName = trim($request->first_name.' '.$request->middle_name.' '.$request->last_name);

            // Store full name if it's not empty                                    
            if (! empty($fullName)) {
                $validatedData['name'] = $fullName;
            }

            // Check if user_id is provided in the request
            if ($user_id) {
                $user = User::find($user_id);
                if ($user) {
                    // Log the data before updating
                    Log::info('Updating user with ID '.$user_id, $validatedData);

                    // Merge new selected properties if provided
                    if ($request->has('selected_properties')) {
                        $validatedData['selected_properties'] = $request->selected_properties;
                    }
                    // Add a condition to prevent updating the step if it's the final step
                    if ($request->step < $this->getTotalQuickSteps()) {
                        $validatedData['quick_step'] = $request->step; // Update step only if it's not the last step
                    }

                    $user->update($validatedData);

                    // ✅ Mark as new only if email is present
                    $isNewUser = ! empty($user->email);
                }
            } else {
                // Create new user only empty user id
                if (empty($user_id)) {
                    $validatedData['quick_step'] = $request->step;
                    Log::info('Creating new user', $validatedData);
                    $user = User::create(array_merge($validatedData, ['added_by' => Auth::id()]));
                    $this->syncCurrentAccountMembership($user, $request->input('role_ids', []));
                }
            }

            // Attach roles
            if ($request->filled('role_ids')) {
                // Fetch the names of each selected role
                $roles = Role::whereIn('id', $request->role_ids)->pluck('name')->toArray();

                // Sync the user’s roles (removes any roles not in this array)
                $user->syncRoles($roles);
            }

            if (isset($user)) {
                $this->syncCurrentAccountMembership($user, $request->input('role_ids', []), $request);
            }

            // Get total number of steps
            $totalSteps = $this->getTotalQuickSteps();

            // ✅ Only if it's the final step AND the user was just created
            if ($request->step >= $totalSteps && $isNewUser) {
                if ($user->hasRole('Tenant')) {
                    Log::info('Sending tenant welcome email to user ID '.$user->id);
                    $this->sendTenantWelcomeEmail($user);
                } else {
                    Log::info('Sending password reset email to user ID '.$user->id);
                    $this->sendPasswordResetMail($user);
                }
            }

            // Check if the current step is the last one
            if ($request->step >= $totalSteps) {
                // Final submission handling
                flash('User Added/Updated successfully!')->success();

                return view('backend.users.user_form.thankyou');
            }

            // Prepare data for the next step view
            $nextStep = $request->step + 1;
            $viewData = compact('user');

            // If step 2 is next, load countries
            if ($nextStep === 2) {
                $viewData['countries'] = Country::allCached();
            }

            return view('backend.users.user_form.step'.$nextStep, $viewData);

            // return view('backend.users.user_form.step' . ($request->step + 1), compact('user'));
        } else {
            // If no step is present, return a message (optional)
            return response()->json(['message' => 'Invalid step from quick store.']);
        }
    }

    private function getValidationRulesQuick($step, $userId = null)
    {
        switch ($step) {
            case 1:
                return [
                    // 'category_id' => 'required',
                    // 'role_id' => 'required|exists:roles,id',
                    'role_ids' => 'required|array|min:1',
                    'role_ids.*' => 'integer|exists:roles,id',
                ];
            case 2:
                $emailRule = 'required|email|max:55|unique:users,email';
                if ($userId) {
                    $emailRule .= ',' . $userId; // ignore current user when editing
                }
                return [
                    'first_name' => 'required|string|max:55',
                    'middle_name' => 'nullable|string|max:55',
                    'last_name' => 'required|string|max:55',
                    'phone' => 'required|string|max:20',
                    'email' => $emailRule,
                    'address_line_1' => 'required|string|max:255',
                    'address_line_2' => 'nullable|string|max:255',
                    'postcode' => 'required|string|max:15',
                    'city' => 'required|string|max:55',
                    // 'country' => 'required|string|max:55',
                    'country_id' => 'nullable|exists:countries,id',
                ];
            case 3:
                return [
                    'selected_properties' => 'nullable',
                ];

            default:
                return [];
        }
    }

    private function getTotalQuickSteps()
    {
        // Specify the directory where your Blade files for steps are located
        $stepsDirectory = resource_path('views/backend/users/user_form');

        // Get all Blade files in the directory that start with 'step' and count them
        return count(glob($stepsDirectory.'/step*.blade.php'));
    }

    public function searchProperties(Request $request)
    {
        // Check if we are passing specific property IDs
        $ids = $request->input('ids');

        // If IDs are provided, fetch properties by IDs
        if ($ids) {
            $properties = Property::query()
                ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
                ->whereIn('id', $ids)
                ->get(['id', 'prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country', 'postcode', 'specific_property_type', 'available_from']);  // Return only necessary fields
        } else {
            // If no IDs are passed, search properties based on the query (default behavior)
            $query = $request->input('query');
            $properties = Property::query()
                ->when(! auth()->user()?->hasRole('Super Admin'), fn ($propertyQuery) => $propertyQuery->forAccount(current_account_id()))
                ->where(function ($propertyQuery) use ($query) {
                    $propertyQuery->where('prop_ref_no', 'LIKE', '%'.$query.'%')
                        ->orWhere('prop_name', 'LIKE', '%'.$query.'%')
                        ->orWhere('line_1', 'LIKE', '%'.$query.'%')
                        ->orWhere('line_2', 'LIKE', '%'.$query.'%')
                        ->orWhere('city', 'LIKE', '%'.$query.'%')
                        ->orWhere('country', 'LIKE', '%'.$query.'%')
                        ->orWhere('postcode', 'LIKE', '%'.$query.'%');
                })
                ->limit(10)
                ->get(['id', 'prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country', 'postcode', 'specific_property_type', 'available_from']);
        }

        // Return the properties as JSON response
        return response()->json($properties->map(function ($property) {
            return [
                'id' => $property->id,
                'address' => trim($property->line_1.' '.$property->line_2.', '.$property->city.', '.$property->postcode) ?: 'N/A',
                'type' => trim($property->specific_property_type) ?: 'N/A',
                'availability' => trim($property->available_from) ?: 'N/A',
                'prop_ref_no' => trim($property->prop_ref_no) ?: 'N/A',
                'prop_name' => trim($property->prop_name) ?: 'N/A',
            ];
        }));
    }

    // public function searchProperties(Request $request)
    // {
    //     // Get the search query from the request
    //     $query = $request->input('query');
    //     $properties = null;

    //     if($query){
    //         // Search for properties based on multiple fields
    //         $properties = Property::where('prop_ref_no', 'LIKE', '%' . $query . '%')
    //             ->orWhere('prop_name', 'LIKE', '%' . $query . '%')
    //             ->orWhere('line_1', 'LIKE', '%' . $query . '%')
    //             ->orWhere('line_2', 'LIKE', '%' . $query . '%')
    //             ->orWhere('city', 'LIKE', '%' . $query . '%')
    //             ->orWhere('country', 'LIKE', '%' . $query . '%')
    //             ->orWhere('postcode', 'LIKE', '%' . $query . '%')
    //             ->limit(10)  // Limit the results to 10
    //             ->get(['id', 'prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country', 'postcode', 'specific_property_type', 'available_from', 'property_type', 'price', 'letting_price']);
    //     }

    //     // Return the properties as JSON response
    //     return view('backend.users.user_form.property_search_results', compact('properties'));
    // }

    public function getQuickStepView($step, Request $request)
    {
        abort_unless($request->user()?->can('create contacts'), 403);

        // Get user_id from the session or request
        $user_id = $request->user_id;
        $user = User::find($user_id);
        // $categories = UserCategory::all();
        $roles = Role::whereNotIn('name', ['Staff', 'Super Admin'])->get();
        $countries = Country::allCached();
        $selectedProperties = $selectedProperties = json_decode($user->selected_properties, true);
        // Get the total number of steps dynamically
        $totalSteps = $this->getTotalQuickSteps();

        // Check if the step is valid
        if ($step > 0 && $step <= $totalSteps) {
            return view('backend.users.user_form.step'.$step, compact('user', 'roles', 'selectedProperties', 'countries')); // Return the corresponding Blade view
            // return view('backend.users.user_form.step' . $step, compact('user','categories', 'selectedProperties')); // Return the corresponding Blade view
        } else {
            // Return a view with an error message if the step is invalid
            return view('backend.users.user_form.error', ['message' => 'Invalid step.']);
        }
    }

    public function quicklyStoreUser(Request $request)
    {
        abort_unless($request->user()?->can('create contacts'), 403);

        // Validate incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'role' => 'nullable|exists:roles,name',
        ]);

        // Create a new user
        $user = User::create([
            // 'category_id'   => $request->category_id ?? 9,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'created_by' => Auth::id(),
            'password' => Hash::make('password'),
        ]);

        // Assign default role if not provided
        $role = $request->input('role', 'User'); // Use a sensible fallback
        $user->assignRole($role);
        $this->syncCurrentAccountMembership($user, [$role]);

        // Return the user data as a JSON response
        return response()->json([
            'success' => true,
            'user' => $user,
        ]);

    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            // 'category_id' => 'required|exists:user_categories,id',
            'first_name' => 'required|string|max:55',
            'middle_name' => 'nullable|string|max:55',
            'last_name' => 'required|string|max:55',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:55',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:15',
            'city' => 'required|string|max:55',
            'country' => 'required|string|max:55',
            'status' => 'required|in:0,1',
            // 'role' => 'required|exists:roles,name',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        // Concatenate first, middle, and last names to create name
        $fullName = trim($request->first_name.' '.$request->middle_name.' '.$request->last_name);
        // $Category_id = $request->category_id;
        // Store the user
        $user = User::create([
            // 'category_id' => $Category_id,
            'first_name' => $validatedData['first_name'],
            'middle_name' => $validatedData['middle_name'],
            'last_name' => $validatedData['last_name'],
            'name' => $fullName,
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'address_line_1' => $validatedData['address_line_1'],
            'address_line_2' => $validatedData['address_line_2'],
            'postcode' => $validatedData['postcode'],
            'city' => $validatedData['city'],
            'country' => $validatedData['country'],
            'status' => $validatedData['status'],
            'updated_by' => Auth::user()->id,
        ]);
        // $user->assignRole($validatedData['role']);
        // Attach roles
        if ($request->filled('role_ids')) {
            // Fetch the names of each selected role
            $roles = Role::whereIn('id', $request->role_ids)->pluck('name')->toArray();

            // Sync the user’s roles (removes any roles not in this array)
            $user->syncRoles($roles);
        }
        $this->syncCurrentAccountMembership($user, $request->input('role_ids', []));
        // Redirect or return a response
        flash('User Added Successfully!')->success();

        return redirect()->route('admin.users.index');
        // return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    /**
     * Redirect to the user index with the user highlighted/selected.
     */
    public function show($id)
    {
        // The index page handles user detail via ?user_id= query param
        return redirect()->route('admin.users.index', ['user_id' => $id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id); // Fetch the user by ID
        $this->ensureUserAccessible($user);

        $roles = Role::whereNotIn('name', ['Staff', 'Super Admin'])->get(); // Fetch roles excluding Staff and Super Admin
        //  $categories = UserCategory::all(); // Fetch all categories
        $selectedProperties = json_decode($user->selected_properties, true);

        return view('backend.users.edit', compact('user', 'roles', 'selectedProperties'));
        //  return view('backend.users.edit', compact('user', 'categories', 'selectedProperties'));
    }

    public function update(Request $request, $id)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            // 'category_id' => 'required|exists:user_categories,id',
            'first_name' => 'required|string|max:55',
            'middle_name' => 'nullable|string|max:55',
            'last_name' => 'required|string|max:55',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:55',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:15',
            'city' => 'required|string|max:55',
            'country' => 'required|string|max:55',
            'status' => 'required|in:0,1',
        ]);

        // Find the user to be updated
        $user = User::findOrFail($id);
        $this->ensureUserAccessible($user);

        // Concatenate first, middle, and last names to create name
        $fullName = trim($request->first_name.' '.$request->middle_name.' '.$request->last_name);

        // Attach roles
        if ($request->filled('role_ids')) {
            // Fetch the names of each selected role
            $roles = Role::whereIn('id', $request->role_ids)->pluck('name')->toArray();

            // Sync the user’s roles (removes any roles not in this array)
            $user->syncRoles($roles);
        }

        // $category_id = $request->category_id;
        // Update the user
        $user->update([
            // 'category_id' => $category_id,
            'first_name' => $validatedData['first_name'],
            'middle_name' => $validatedData['middle_name'],
            'last_name' => $validatedData['last_name'],
            'name' => $fullName,
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'address_line_1' => $validatedData['address_line_1'],
            'address_line_2' => $validatedData['address_line_2'],
            'postcode' => $validatedData['postcode'],
            'city' => $validatedData['city'],
            'country' => $validatedData['country'],
            'status' => $validatedData['status'],
            'updated_by' => Auth::user()->id,
        ]);

        // Redirect or return a response
        flash('User Updated Successfully!')->success();

        return redirect()->route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        // Find the user to be deleted
        $user = User::findOrFail($id);
        $this->ensureUserAccessible($user);

        // Delete the user
        $user->delete();
        $response = [
            'status' => true,
            'notification' => 'User Deleted successfully!',
        ];

        return response()->json($response);

        // flash("User deleted successfully!")->success();
        // return redirect()->route('admin.users.index');
    }

    public function loadForm(Request $request)
    {
        $user = User::with('details.user')->find($request->user_id);
        $formType = $request->form_type;

        if (! $user) {
            return response()->json(['error' => 'user not found'], 404);
        }

        $viewPath = "backend.users.popup_forms.$formType";

        // Check if the form view exists
        if (! view()->exists($viewPath)) {
            return response()->json(['error' => 'Invalid form type'], 400);
        }

        $extraData = []; // <-- This prevents undefined variable errors
        $extraData = $this->getFormTypeExtras($formType, $user, $request->note_id ?? null, $request->bank_detail_id ?? null, $request);
        // ** NEW: if we have a note_id, fetch that note and pass it in **
        // if ($formType === 'notes_tab' && $request->filled('note_id')) {
        //     $note = $user->notes()->findOrFail($request->note_id);
        //     $extraData['note'] = $note;
        // }
        $html = view($viewPath, array_merge(['user' => $user], ['editMode' => true], $extraData))->render();

        // Render the form with additional data
        // $html = view($viewPath, [
        //     'user' => $user,
        //     'editMode' => true,
        //     'stations' => $stations,
        //     'schools' => $schools,
        //     'allstations' => $allstations,
        //     'allschools' => $allschools
        // ])->render();

        // Render the form and return it
        // $html = view($viewPath, ['user' => $user, 'editMode' => true])->render();

        return response()->json(['success' => true, 'form_html' => $html]);
    }

    public function saveForm(Request $request)
    {
        $user = User::find($request->input('user_id'));
        $formType = $request->input('form_type');
        if (! $user) {
            return response()->json(['error' => 'user not found'], 404);
        }

        // Tenants can only edit their own record
        if (auth()->user()->hasRole('Tenant') && $user->id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $extraData = []; // <-- This prevents undefined variable errors

        // Save the form data based on the form type
        switch ($formType) {
            case 'user_detail':

                // **Sync the user’s roles** if provided
                if ($request->filled('role_ids')) {
                    $roleNames = Role::whereIn('id', $request->role_ids)
                        ->pluck('name')
                        ->toArray();
                    $user->syncRoles($roleNames);
                }

                $data = $request->only([
                    // 'category_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'address_line_1',
                    'address_line_2',
                    'city',
                    'postcode',
                    'country',
                ]);

                // 2) Prepare detail‐specific data
                $detailData = [
                    'correspondence_address' => $request->input('correspondence_address', null),
                    'other' => $request->input('other', null),

                    'allow_email' => $request->boolean('allow_email', false),
                    'allow_post' => $request->boolean('allow_post', false),
                    'allow_text' => $request->boolean('allow_text', false),
                    'allow_call' => $request->boolean('allow_call', false),

                    'occupation' => $request->input('occupation', null),
                    'business_name' => $request->input('business_name', null),
                    'registered_address' => $request->input('registered_address', null),
                    'vat_number' => $request->input('vat_number', null),

                    // Eloquent will cast these arrays to JSON
                    'emails' => array_values(array_filter($request->input('emails', []))),
                    'phones' => array_values(array_filter($request->input('phones', []))),
                ];

                // 3) Create or update UserDetail
                $user->details()->updateOrCreate(
                    ['user_id' => $user->id],
                    $detailData
                );

                break;
            case 'bank_detail':

                // Forward the request to the controller
                $bankDetailController = app(BankDetailController::class);
                $bankDetailController->store($request);
                $data = []; // <-- Prevents undefined variable error
                break;
            case 'compliance':
                $data = $request->only([]);

                // 2) Prepare detail‐specific data
                $detailData = [
                    'nationality_id' => $request->input('nationality_id', null),
                    'visa_expiry' => $request->input('visa_expiry', null),
                    'passport_no' => $request->input('passport_no', null),
                    'nrl_number' => $request->input('nrl_number', null),

                    'right_to_rent_check' => $request->boolean('right_to_rent_check', false),
                    'checked_by_user' => $request->input('checked_by_user', null),
                    'checked_by_external' => $request->input('checked_by_external', null),
                ];

                // 3) Create or update UserDetail
                $user->details()->updateOrCreate(
                    ['user_id' => $user->id],
                    $detailData
                );
                break;
            case 'notes':
                $data = $request->only([
                    'imp_notes',
                ]);
                break;
            default:
                return response()->json(['message' => 'Invalid form type'], 400);
        }

        if (! empty($data)) {
            $user->update($data);
        }
        // $user->update($data);

        // 🛠️ Fix: Re-fetch related data like school/station names
        $extraData = $this->getFormTypeExtras($formType, $user);

        // Render updated section
        $updatedView = view("backend.users.popup_forms.$formType", array_merge(['user' => $user], $extraData))->render();

        return response()->json([
            'success' => 'Form updated successfully',
            'updated_html' => $updatedView,
            'status' => true,
            'message' => 'Updated successfully',
        ]);
    }

    private function getFormTypeExtras($formType, $user, $noteId = null, $bankId = null, $request = null)
    {
        if ($formType === 'user_detail') {
            // Fetch categories for your filter dropdown
            // $categories = UserCategory::all();

            // Fetch full Role models (with id & name), not just names
            $roles = Role::whereNotIn('name', ['Staff', 'Super Admin'])->get();

            // return compact('categories');
            return compact('roles');
        } elseif ($formType === 'compliance') {

            $nationalities = Nationality::orderBy('name')->pluck('name', 'id');
            $users = User::orderBy('name')->pluck('name', 'id');

            return compact('nationalities', 'users');

            // }
            // elseif ($formType === 'notes_tab') {

            // Prepare the Request object for NotesController
            // $requestData = new Request([
            //     'noteable_type' => get_class($user),  // e.g. App\Models\User
            //     'noteable_id'   => $user->id,
            //     'note_id'       => $noteId,  // null if no noteId
            // ]);

            // $notesController = new NotesController();
            // $response = $notesController->listNotes($requestData);

            // $data = $response->getData(); // TRUE returns an array, not an object
            // $notes = $data->notes ?? collect();
            // $note = $data->note ?? null;
            // // 3) all available note types
            // $noteTypes = NoteType::all();
            // return compact('notes','note', 'noteTypes');

            // 1) full list for view mode
            // $notes = $user->notes()->with('noteType')->orderBy('updated_at','desc')->get();
            /*$notesQuery = $user->notes()->with('noteType')->orderByDesc('updated_at');

            // Filter by note type
            if ($request->filled('note_type_id')) {
                $notesQuery->where('note_type_id', $request->note_type_id);
            }

            // Filter by content
            if ($request->filled('search')) {
                $notesQuery->where('content', 'like', '%' . $request->search . '%');
            }

            // Filter by date range
            if ($request->filled('from_date')) {
                $notesQuery->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $notesQuery->whereDate('created_at', '<=', $request->to_date);
            }

            $notes = $notesQuery->paginate(10); // Use pagination instead of get()

            // 2) single note when editing
            $note = null;
            if ($noteId) {
                $note = $user->notes()->with('noteType')->findOrFail($noteId);
            }
            $noteTypes = NoteType::all();
            return compact('notes', 'note', 'noteTypes');*/
        } elseif ($formType === 'bank_detail') {
            // 1) full list for view mode
            $bankDetails = $user->bankDetails()->orderByDesc('is_primary')->orderBy('updated_at', 'desc')->get();

            $bankDetail = null;
            if ($bankId) {
                $bankDetail = $user->bankDetails()
                    ->findOrFail($bankId);
            }

            return compact('bankDetails', 'bankDetail');
        }

        return [];
    }

    /**
     * AJAX endpoint to list users for a select dropdown.
     */
    public function ajaxList(Request $request)
    {
        $term = $request->input('q');

        $query = User::query();
        $this->scopeUsersToCurrentAccount($query);

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%$term%")
                    ->orWhere('email', 'like', "%$term%");
            });
        }

        $users = $query
            ->select('id', 'name', 'email')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $results = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'text' => "{$user->name} - {$user->email}",
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX endpoint to search staff users for Select2 (compliance checked_by_user field).
     * Returns 6 by default, searches on 2+ characters.
     */
    public function staffAjaxList(Request $request)
    {
        $term = $request->input('q', '');

        $query = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Staff', 'Super Admin', 'Property Manager']);
        });
        $this->scopeUsersToCurrentAccount($query);

        if (strlen($term) >= 2) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%$term%")
                  ->orWhere('email', 'like', "%$term%");
            });
        }

        $users = $query->select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(6)
            ->get();

        return response()->json([
            'results' => $users->map(fn($u) => [
                'id'   => $u->id,
                'text' => $u->name . ' (' . $u->email . ')',
            ]),
        ]);
    }

    private function sendPasswordResetMail(User $user): void
    {
        try {
            $resetLink = $user->createResetLink();
            $template = EmailTemplate::getByIdentifier('password_reset');

            $placeholders = [
                'user_name' => $user->name ?? $user->email,
                'user_email' => $user->email,
                'reset_link' => $resetLink,
                'crm_name' => config('app.name'),
                'admin_email' => config('mail.from.address'),
            ];

            if ($template) {
                $renderedHtml = $template->replace($placeholders, ['reset_link']);
                $subject = render_template($template->subject, $placeholders);
            } else {
                $subject = 'Set your password for '.config('app.name');
                $renderedHtml = "<p>Hi {$placeholders['user_name']},</p>"
                    .'<p>Welcome to '.e(config('app.name')).'. Please set your password:</p>'
                    ."<p><a href='{$resetLink}'>Set your password</a></p>";
            }

            Mail::to($user->email)->send(new MailManager([
                'subject' => $subject,
                'content' => $renderedHtml,
            ]));

            Log::info("Password reset email sent to {$user->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email: {$e->getMessage()}", [
                'email' => $user->email,
                'user_id' => $user->id,
            ]);
        }
    }

    private function sendTenantWelcomeEmail(User $user): void
    {
        try {
            // Generate a password and save it
            $plainPassword = \Illuminate\Support\Str::random(10);
            $user->update(['password' => \Illuminate\Support\Facades\Hash::make($plainPassword)]);
            $resetLink = $user->createResetLink();

            $template = \App\Models\EmailTemplate::getByIdentifier('tenant_account_created');

            $placeholders = [
                'tenant_name'      => $user->name ?? $user->email,
                'tenant_email'     => $user->email,
                'tenant_password'  => $plainPassword,
                'property_name'    => '—',
                'property_address' => '—',
                'move_in_date'     => '—',
                'rent'             => '—',
                'reset_link'       => $resetLink,
                'login_url'        => url('/admin/login'),
                'crm_name'         => config('app.name'),
                'admin_email'      => config('mail.from.address'),
            ];

            if ($template) {
                $renderedHtml = $template->replace($placeholders, ['reset_link', 'login_url']);
                $subject = render_template($template->subject, $placeholders);
            } else {
                $subject = 'Welcome to ' . config('app.name') . ' — Your Tenant Account';
                $renderedHtml = "<p>Hi {$placeholders['tenant_name']},</p>"
                    . "<p>Your account has been created.</p>"
                    . "<p>Email: {$placeholders['tenant_email']} | Password: <strong>{$placeholders['tenant_password']}</strong></p>"
                    . "<p><a href='{$placeholders['login_url']}'>Login</a> | <a href='{$resetLink}'>Change Password</a></p>";
            }

            Mail::to($user->email)->send(new \App\Mail\MailManager([
                'subject'     => $subject,
                'content'     => $renderedHtml,
                'attachments' => [],
            ]));

            Log::info("Tenant welcome email sent to {$user->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send tenant welcome email: {$e->getMessage()}", [
                'email'   => $user->email,
                'user_id' => $user->id,
            ]);
        }
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
                $start = $from ? Carbon::parse($from) : $today->copy()->startOfMonth();
                $end = $to ? Carbon::parse($to) : null;
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
                                    
    private function streamStatementCsv(User $user, array $statement)
    {
        return response()->streamDownload(function () use ($user, $statement) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ["Statement for {$user->name}", "{$statement['from']} - {$statement['to']}"]);
            fputcsv($out, []);
            fputcsv($out, ['Beginning Balance', number_format($statement['opening'], 2)]);
            fputcsv($out, ['Invoiced', number_format($statement['summary']['invoiced'] ?? 0, 2)]);
            fputcsv($out, ['Paid', number_format($statement['summary']['paid'] ?? 0, 2)]);
            fputcsv($out, ['Balance Due', number_format($statement['summary']['balance_due'] ?? $statement['closing'], 2)]);
            fputcsv($out, []);
            fputcsv($out, ['Date', 'Details', 'Debit', 'Credit', 'Delta', 'Running']);
            fputcsv($out, ['', 'Opening Balance', '', '', '', number_format($statement['opening'], 2)]);
            foreach ($statement['lines'] as $line) {
                fputcsv($out, [
                    $line['date'],
                    trim(($line['memo'] ?? '').' '.($line['account_code'] ?? '').' '.($line['account_name'] ?? '')),
                    number_format($line['debit'], 2),
                    number_format($line['credit'], 2),
                    number_format($line['delta'], 2),
                    number_format($line['running'], 2),
                ]);
            }
            fclose($out);
        }, 'contact-statement.csv', ['Content-Type' => 'text/csv']);
    }

    private function scopeUsersToCurrentAccount($query)
    {
        if (! auth()->user()?->hasRole('Super Admin')) {
            $query->whereHas('accountUsers', function ($accountUserQuery) {
                $accountUserQuery->where('account_id', current_account_id())
                    ->where('status', 'active');
            });
        }

        return $query;
    }

    private function scopeContactsToCurrentUser($query)
    {
        $this->scopeUsersToCurrentAccount($query);

        if (auth()->user()?->hasRole('Landlord')) {
            $query->where('users.created_by', auth()->id());
        }

        return $query;
    }

    private function ensureUserAccessible(User $user): void
    {
        if (auth()->user()?->hasRole('Super Admin') || (int) $user->id === (int) auth()->id()) {
            return;
        }

        $allowed = $user->accountUsers()
            ->where('account_id', current_account_id())
            ->where('status', 'active')
            ->exists();

        // TODO: Tenant/contact portal access should also honour property_participants.
        abort_unless($allowed, 403);
    }

    private function syncCurrentAccountMembership(User $user, array $roles = [], ?Request $request = null): void
    {
        $accountId = current_account_id();

        if (! $accountId || auth()->user()?->hasRole('Super Admin')) {
            return;
        }

        $roleNames = Role::whereIn('id', collect($roles)->filter(fn ($role) => is_numeric($role))->all())
            ->pluck('name')
            ->merge(collect($roles)->filter(fn ($role) => is_string($role)))
            ->map(fn ($role) => strtolower((string) $role));

        if ($roleNames->isEmpty()) {
            $roleNames = $user->getRoleNames()->map(fn ($role) => strtolower((string) $role));
        }

        [$memberType, $participantType] = $this->portalTypesFromRoles($roleNames);
        $accessLevel = $request?->input('portal_access_level', $request?->input('access_level', 'view')) ?: 'view';
        $accessLevel = in_array($accessLevel, ['view', 'edit', 'full'], true) ? $accessLevel : 'view';
        $wantsLogin = $request?->boolean('can_login', true) ?? true;
        $canLogin = $wantsLogin;
        $account = current_account();

        if ($account) {
            $canLogin = $wantsLogin && app(AccountLimitService::class)->canUseContactLogin($account);
        }

        AccountUser::updateOrCreate(
            [
                'account_id' => $accountId,
                'user_id' => $user->id,
            ],
            [
                'member_type' => $memberType,
                'access_level' => 'view',
                'can_login' => $canLogin,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]
        );

        $propertyIds = $this->selectedPortalPropertyIds($request, $user);

        if (! $canLogin) {
            PropertyParticipant::query()
                ->where('account_id', $accountId)
                ->where('user_id', $user->id)
                ->update(['status' => 'inactive']);

            if ($request && $wantsLogin) {
                flash('Your current plan does not include contact portal login.')->warning();
            }

            return;
        }

        if (! $account) {
            return;
        }

        if (empty($propertyIds)) {
            PropertyParticipant::query()
                ->where('account_id', $accountId)
                ->where('user_id', $user->id)
                ->update(['status' => 'inactive']);

            return;
        }

        $validPropertyIds = Property::query()
            ->where('account_id', $accountId)
            ->whereIn('id', $propertyIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        PropertyParticipant::query()
            ->where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->whereNotIn('property_id', $validPropertyIds)
            ->update(['status' => 'inactive']);

        PropertyParticipant::query()
            ->where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->whereIn('property_id', $validPropertyIds)
            ->where('participant_type', '!=', $participantType)
            ->update(['status' => 'inactive']);

        $portalAccessService = app(PortalAccessService::class);

        foreach ($validPropertyIds as $propertyId) {
            $property = Property::find($propertyId);

            if (! $property) {
                continue;
            }

            $portalAccessService->grantPropertyAccess(
                $account,
                $property,
                $user,
                $participantType,
                $accessLevel,
                $request?->boolean('can_view_finance', false) ?? false,
                $request?->boolean('can_view_documents', false) ?? false,
                $request?->boolean('can_upload_documents', false) ?? false,
                auth()->user()
            );
        }
    }

    private function portalTypesFromRoles($roleNames): array
    {
        if ($roleNames->contains('tenant')) {
            return ['tenant', 'tenant'];
        }

        if ($roleNames->contains('contractor')) {
            return ['contractor', 'contractor'];
        }

        if ($roleNames->contains('property manager') || $roleNames->contains('property_manager')) {
            return ['property_manager', 'property_manager'];
        }

        if ($roleNames->contains('landlord')) {
            return ['landlord', 'landlord'];
        }

        if ($roleNames->contains('owner') || $roleNames->contains('owner contact') || $roleNames->contains('owner_contact')) {
            return ['owner_contact', 'owner'];
        }

        return ['contact', 'owner'];
    }

    private function selectedPortalPropertyIds(?Request $request, User $user): array
    {
        $selected = $request?->input('portal_property_ids', $request?->input('selected_properties', $user->selected_properties));

        if (is_string($selected)) {
            $decoded = json_decode($selected, true);
            $selected = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $selected);
        }

        if (! is_array($selected)) {
            return [];
        }

        return collect($selected)
            ->map(fn ($id) => is_array($id) ? ($id['id'] ?? null) : $id)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function canUseInvoiceBrandingFeature(): bool
    {
        return $this->saasLimitError('invoice_branding') === null;
    }

    private function requestHasCompanyProfileInput(Request $request): bool
    {
        return $request->has('company')
            || $request->hasFile('company_logo')
            || $request->hasFile('company_stamp');
    }
}
