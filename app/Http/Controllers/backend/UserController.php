<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use App\Models\Country;
use App\Models\NoteType;
use App\Models\Property;
// use App\Models\BankDetails;
use App\Models\Nationality;
use App\Models\DocumentType;
// use App\Models\UserCategory;
use App\Models\UserCategory;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Mail\MailManager;
// use App\Http\Controllers\Backend\NotesController;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Backend\NotesController;
use App\Http\Controllers\Backend\BankDetailController;

class UserController
{
    public function profile()
    {
        $authUser = auth()->user();
        // $countryName = Country::find($authUser->country_id)?->name ?? 'N/A';
        // Use cached countries to find the user's country
        $countryName = Country::allCached()->firstWhere('id', $authUser->country_id)->name ?? 'N/A';
        return view('backend.users.profile.show', compact('authUser', 'countryName'));
    }
    
    public function profileEdit()
    {
        // Fetch the authenticated user

        $user = User::with('country')->find(auth()->id());
        // $categories = UserCategory::all();
        $countries = Country::allCached();
        return view('backend.users.profile.edit', compact('user', 'countries'));
        // return view('backend.users.profile.edit', compact('user', 'categories', 'countries'));
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'title' => 'required|string|max:10',
            'first_name' => 'required|string|max:55',
            'middle_name' => 'nullable|string|max:55',
            'last_name' => 'required|string|max:55',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:55|unique:users,email,' . $user->id,
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:15',
            'city' => 'required|string|max:55',
            // 'country' => 'required|string|max:55',
            'country_id' => 'nullable|exists:countries,id',
            'profile_picture' => 'nullable|image|max:2048', // 2MB max
            // 'category_id' => 'required|exists:users_categories,id',
            // 'role' => 'required|exists:roles,name',
        ]);

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
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');

            // Delete old picture if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $user->profile_picture = $path;
        }
        
        $fullName = trim($request->input('first_name') . ' ' . $request->input('middle_name') . ' ' . $request->input('last_name'));

        $user->update([
            'title' => $validatedData['title'],
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
            // 'country' => $validatedData['country'],
            'country_id' => $validatedData['country_id'] ?? null,
            // 'category_id' => $validatedData['category_id'],
            'updated_by' => auth()->id(),
            'profile_picture' => $user->profile_picture, // set new path if uploaded
        ]);
            
        // Sync new role (removes old ones and assigns the new one)
        // $user->syncRoles([$validatedData['role']]);
        flash('Profile updated successfully!')->success();
        return redirect()->route('admin.users.profile.show');
    }

    public function profilePasswordUpdate(Request $request)
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Check if the current password is correct
        if (!Hash::check($validatedData['current_password'], $user->password)) {
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

        // Apply a category filter if provided
        // if ($request->filled('category')) {
        //     $usersQuery->where('category_id', $request->category);
        // }

        // 🔍 Apply role filter if provided
        if ($request->filled('role')) {
            $usersQuery->role($request->role); // Spatie's `role()` scope
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
            ->get();


        // If no users at all, redirect to quick-create
        if ($users->isEmpty()) {
            flash("You don't have any users yet!")->error();
            return redirect()->route('admin.users.create');
        }

        // Decide which user/tab to show
        $userId = $request->query('user_id');
        $tabName   = $request->query('tabname', 'Contact');

        // Try to find the requested user or fall back to the most recent
        $user = $userId ? $users->firstWhere('id', $userId) : null;

        if (! $user) {
            $user = $users->first();
            $userId = $user->id;
        }

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
        ];

        $content = $this->getTabContent($tabName, $userId, $user); // Dynamically get content for the tab and property

        // Check if the request is via AJAX (this handles dynamic content loading)
        if ($request->ajax()) {
            return response()->json(['content' => $content, 'tabName' => $tabName]);
        }

        return view('backend.users.index', compact('users', 'roles','tabs', 'tabName', 'userId', 'user', 'content'));
        // return view('backend.users.index', compact('users', 'categories','tabs', 'tabName', 'userId', 'user', 'content'));
    }

    private function getTabContent($tabname, $userId, $user)
    {
        switch (strtolower($tabname)) {
            case 'contact':
                return view('backend.users.tabs.user_details', compact('userId', 'user'))->render();
            
            case 'appointments':
                return view('backend.users.tabs.appointments', compact('userId', 'user'))->render();                
            
            case 'link':
                $propertyIds = [];

                if (!empty($user->selected_properties)) {
                    $decoded = is_array($user->selected_properties)
                        ? $user->selected_properties
                        : json_decode($user->selected_properties, true);

                    if (is_array($decoded)) {
                        $propertyIds = $decoded;
                    }
                }

                $properties = !empty($propertyIds)
                    ? Property::whereIn('id', $propertyIds)->get()
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
                $users = User::orderBy('name')->pluck('name', 'id');
                
                return view('backend.users.tabs.compliance', compact('userId', 'user', 'users', 'nationalities'))->render();
    
            case 'documents':
                $documents = $user->documents()->with('documentType')->orderByDesc('updated_at')->paginate(5);

                // Ensure it's an empty collection if no documents are found
                if ($documents->isEmpty()) {
                    $documents = collect();  // Make sure it's an empty collection, not null
                }
                $documentTypes = DocumentType::all();
                // return 1;
                return view('backend.users.tabs.documents', compact('userId', 'user', 'documents', 'documentTypes'))->render();
    
            case 'notes':
                // Fetch the notes related to the specific user by user ID
                $notes = $user->notes()->with('noteType')->orderByDesc('updated_at')->paginate(5);

                // Ensure it's an empty collection if no notes are found
                if ($notes->isEmpty()) {
                    $notes = collect();  // Make sure it's an empty collection, not null
                }
                $noteTypes = NoteType::all();
                return view('backend.users.tabs.notes', compact('userId', 'user', 'notes', 'noteTypes'))->render();
                // return view('backend.users.tabs.notes', compact('userId', 'user'))->render();
    
            default:
                return 'Tab content not found';
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(User $user)
    {
        $roles = Role::whereNotIn('name', ['Staff', 'Super Admin'])->get();
        return view('backend.users.create', compact('user', 'roles'));
        // $categories = UserCategory::all();
        // return view('backend.users.create', compact('user', 'categories'));
        // return view('backend.users.create'); // Return the create user view
    }

    public function userStore(Request $request)
    {
        // Validate data based on the current step
        if ($request->has('step')) {

            // ✅ track whether we just created a new user
            $isNewUser = false;

            // Validate the request data
            $validatedData = $request->validate($this->getValidationRulesQuick($request->step));

            // Get user_id from the request
            $user_id = $request->user_id;

            // Check if first name, middle name, and last name are present
            $fullName = trim($request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name);

            // Store full name if it's not empty
            if (!empty($fullName)) {
                $validatedData['name'] = $fullName;
            }

            // Check if user_id is provided in the request
            if ($user_id) {
                $user = User::find($user_id);
                if ($user) {
                    // Log the data before updating
                    Log::info('Updating user with ID ' . $user_id, $validatedData);
                    
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
                    $isNewUser = !empty($user->email);
                }
            } else {
                // Create new user only empty user id
                if (empty($user_id)) {
                    $validatedData['quick_step'] = $request->step;
                    Log::info('Creating new user', $validatedData);
                    $user = User::create(array_merge($validatedData, ['added_by' => Auth::id()]));
                }
            }
            
            // Attach roles
            if ($request->filled('role_ids')) {
                // Fetch the names of each selected role
                $roles = Role::whereIn('id', $request->role_ids)->pluck('name')->toArray();
                
                // Sync the user’s roles (removes any roles not in this array)
                $user->syncRoles($roles);
            }
            
            // Get total number of steps
            $totalSteps = $this->getTotalQuickSteps();

            // ✅ Only if it's the final step AND the user was just created
            if ($request->step >= $totalSteps && $isNewUser) {
                Log::info('Sending password reset email to user ID ' . $user->id);
                $this->sendPasswordResetMail($user);
            }
            
            // Check if the current step is the last one
            if ($request->step >= $totalSteps) {
                // Final submission handling
                flash("User Added/Updated successfully!")->success();
                return view('backend.users.user_form.thankyou');
            }

            // Prepare data for the next step view
            $nextStep = $request->step + 1;
            $viewData = compact('user');

            // If step 2 is next, load countries
            if ($nextStep === 2) {
                $viewData['countries'] = Country::allCached();
            }

            return view('backend.users.user_form.step' . $nextStep, $viewData);
            
            // return view('backend.users.user_form.step' . ($request->step + 1), compact('user'));
        } else {
            // If no step is present, return a message (optional)
            return response()->json(['message' => 'Invalid step from quick store.']);
        }
    }

    private function getValidationRulesQuick($step)
    {
        switch ($step) {
            case 1:
                return [
                    // 'category_id' => 'required',
                    // 'role_id' => 'required|exists:roles,id',
                    'role_ids'   => 'required|array|min:1',
                    'role_ids.*' => 'integer|exists:roles,id',
                ];
            case 2:
                return [
                    'first_name' => 'required|string|max:55',
                    'middle_name' => 'nullable|string|max:55',
                    'last_name' => 'required|string|max:55',
                    'phone' => 'required|string|max:20',
                    'email' => 'required|email|max:55',
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
        return count(glob($stepsDirectory . '/step*.blade.php'));
    }

    public function searchProperties(Request $request)
    {
        // Check if we are passing specific property IDs
        $ids = $request->input('ids');

        // If IDs are provided, fetch properties by IDs
        if ($ids) {
            $properties = Property::whereIn('id', $ids)
                ->get(['id', 'prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country', 'postcode', 'specific_property_type', 'available_from']);  // Return only necessary fields
        } else {
            // If no IDs are passed, search properties based on the query (default behavior)
            $query = $request->input('query');
            $properties = Property::where('prop_ref_no', 'LIKE', '%' . $query . '%')
                ->orWhere('prop_name', 'LIKE', '%' . $query . '%')
                ->orWhere('line_1', 'LIKE', '%' . $query . '%')
                ->orWhere('line_2', 'LIKE', '%' . $query . '%')
                ->orWhere('city', 'LIKE', '%' . $query . '%')
                ->orWhere('country', 'LIKE', '%' . $query . '%')
                ->orWhere('postcode', 'LIKE', '%' . $query . '%')
                ->limit(10)
                ->get(['id', 'prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country', 'postcode', 'specific_property_type', 'available_from']);
        }

        // Return the properties as JSON response
        return response()->json($properties->map(function($property) {
            return [
                'id' => $property->id,
                'address' => trim($property->line_1 . ' ' . $property->line_2 . ', ' . $property->city . ', ' . $property->postcode) ?: 'N/A',
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
            return view('backend.users.user_form.step' . $step, compact('user','roles', 'selectedProperties', 'countries')); // Return the corresponding Blade view
            // return view('backend.users.user_form.step' . $step, compact('user','categories', 'selectedProperties')); // Return the corresponding Blade view
        } else {
            // Return a view with an error message if the step is invalid
            return view('backend.users.user_form.error', ['message' => 'Invalid step.']);
        }
    }

    public function quicklyStoreUser(Request $request)
    {
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
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'phone'         => $validated['phone'],
            'created_by'    => Auth::id(),
            'password'      => Hash::make('password'),
        ]);

        // Assign default role if not provided
        $role = $request->input('role', 'User'); // Use a sensible fallback
        $user->assignRole($role);
        
        // Return the user data as a JSON response
        return response()->json([
            'success' => true,
            'user' => $user
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
            'role_ids'   => 'required|array|min:1',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        // Concatenate first, middle, and last names to create name
        $fullName = trim($request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name);
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
        // Redirect or return a response
        flash("User Added Successfully!")->success();
        return redirect()->route('admin.users.index');
        // return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */

     public function edit($id)
     {
         $user = User::findOrFail($id); // Fetch the user by ID
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

        // Concatenate first, middle, and last names to create name
        $fullName = trim($request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name);
            
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
        flash("User Updated Successfully!")->success();
        return redirect()->route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        // Find the user to be deleted
        $user = User::findOrFail($id);

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
    
        if (!$user) {
            return response()->json(['error' => 'user not found'], 404);
        }
    
        $viewPath = "backend.users.popup_forms.$formType";
    
        // Check if the form view exists
        if (!view()->exists($viewPath)) {
            return response()->json(['error' => 'Invalid form type'], 400);
        }

        $extraData = []; // <-- This prevents undefined variable errors
        $extraData = $this->getFormTypeExtras($formType, $user, $request, $request->note_id ?? null, $request->bank_detail_id ?? null);
        // ** NEW: if we have a note_id, fetch that note and pass it in **
        // if ($formType === 'notes_tab' && $request->filled('note_id')) {
        //     $note = $user->notes()->findOrFail($request->note_id);
        //     $extraData['note'] = $note;
        // }
        $html = view($viewPath, array_merge(['user' => $user],['editMode' => true], $extraData))->render();

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
        if (!$user) {
            return response()->json(['error' => 'user not found'], 404);
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
                    'other'                  => $request->input('other', null),

                    'allow_email' => $request->boolean('allow_email', false),
                    'allow_post'  => $request->boolean('allow_post',  false),
                    'allow_text'  => $request->boolean('allow_text',  false),
                    'allow_call'  => $request->boolean('allow_call',  false),

                    'occupation'         => $request->input('occupation', null),
                    'business_name'      => $request->input('business_name', null),
                    'registered_address' => $request->input('registered_address', null),
                    'vat_number'         => $request->input('vat_number', null),

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
                    'imp_notes'
                ]);
                break;
            default:
                return response()->json(['message' => 'Invalid form type'], 400);
        }

        if (!empty($data)) {
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
            'message'  => 'Updated successfully',
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
        }elseif ($formType === 'compliance') {

            $nationalities = Nationality::orderBy('name')->pluck('name', 'id');
            $users = User::orderBy('name')->pluck('name', 'id');
            return compact('nationalities','users');

        //}
        //elseif ($formType === 'notes_tab') {
            
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
        }elseif ($formType === 'bank_detail') {
            // 1) full list for view mode
            $bankDetails = $user->bankDetails()->orderByDesc('is_primary')->orderBy('updated_at','desc')->get();

            
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


    private function sendPasswordResetMail(User $user): void
    {
        try {
            $resetLink = $user->createResetLink();
            $template = EmailTemplate::getByIdentifier('password_reset');

            $placeholders = [
                'user_name'   => $user->name ?? $user->email,
                'user_email'  => $user->email,
                'reset_link'  => $resetLink,
                'crm_name'    => config('app.name'),
                'admin_email' => config('mail.from.address'),
            ];

            if ($template) {
                $renderedHtml = $template->replace($placeholders, ['reset_link']);
                $subject = render_template($template->subject, $placeholders);
            } else {
                $subject = 'Set your password for ' . config('app.name');
                $renderedHtml = "<p>Hi {$placeholders['user_name']},</p>"
                    . "<p>Welcome to " . e(config('app.name')) . ". Please set your password:</p>"
                    . "<p><a href='{$resetLink}'>Set your password</a></p>";
            }

            Mail::to($user->email)->send(new MailManager([
                'subject' => $subject,
                'content' => $renderedHtml,
            ]));

            Log::info("Password reset email sent to {$user->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email: {$e->getMessage()}", [
                'email'   => $user->email,
                'user_id' => $user->id,
            ]);
        }
    }

}
