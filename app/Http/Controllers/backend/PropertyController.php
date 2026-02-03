<?php

namespace App\Http\Controllers\Backend;

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
use App\Models\Designation;
use App\Models\StationName;
// use App\Models\EstateCharge;
use App\Models\EstateCharge;
use Dom\Document;
use Illuminate\Http\Request;
use App\Models\ComplianceType;
use App\Models\LocalAuthority;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PropertyResponsibility;
use Illuminate\Support\Facades\Validator;

class PropertyController
{
    public function index(Request $request)
    {
        // Fetch all properties
        // $properties = Property::all();
        // Fetch all properties in descending order
        // $properties = Property::orderBy('id', 'desc')->get();

        // Get logged-in user
        $user = auth()->user();

        // Fetch properties based on role
        if ($user->hasRole('Property Manager') || $user->hasRole('Super Admin')) {
            // Property managers see all properties
            $properties = Property::orderBy('id', 'desc')->get();
        } elseif ($user->hasRole('Landlord') || $user->hasRole('Staff') || $user->hasRole('Test')  ) {
            // Landlords see only properties they created
            $properties = Property::where('created_by', $user->id)
                ->orderBy('id', 'desc')
                ->get();
        } elseif ($user->hasRole('Estate Agent')) {
            // Estate agents see properties created by them or their sub-users
            $createdUserIds = User::where('created_by', $user->id)->pluck('id');
            $properties = Property::whereIn('created_by', $createdUserIds->push($user->id))
                ->orderBy('id', 'desc')
                ->get();
        } else {
            // Default: no access
            $properties = collect(); // empty collection
        }
        
        // Redirect to 'quick' if there are no properties
        if ($properties->isEmpty()) {
            flash("You don't have any properties yet!")->error();
            return redirect()->route('admin.properties.quick');
        }

        // Get property_id and tabname from query parameters
        $propertyId = $request->query('property_id');
        $tabName = $request->query('tabname', 'property'); // Default to 'property' if no tab is specified

        // Check if the property_id is provided, otherwise, select the first property or handle it gracefully
        // $property = $propertyId ? Property::findOrFail($propertyId) : $properties->first(); // Use the first property if none is selected
        $property = $propertyId ? Property::find($propertyId) : null; // Use null if no property is selected

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
            // Check if user can access this property
            $user = auth()->user();

            $isAuthorized = $user->hasRole('Super Admin') || 
                $user->hasRole('Property Manager') || 
                ($user->hasRole('Landlord') && $property->created_by === $user->id) || 
                ($user->hasRole('Estate Agent') && ($property->created_by === $user->id || $user->createdUsers()->pluck('id')->contains($property->created_by))) ||
                ($user->hasRole('Staff') || $user->hasRole('Test') && $property->created_by === $user->id);

            if (! $isAuthorized) {
                abort(403, 'Unauthorized to view this property.');
            }
        } else {
            // If property not found, fallback
            $property = $properties->first();
            $propertyId = $property->id;
        }

        $availableTabs = [
            'view properties'     => 'Property',
            'view property owners'       => 'Owners',
            'manage property compliance'   => 'Compliance',
            'view property media'        => 'Media',
            'view property offers'       => 'Offers',
            'view property tenancy'      => 'Tenancy',
            'view property aps'          => 'APS',
            'view property teams'        => 'Teams',
            'view property documents'    => 'Documents',
            // 'view property contractor'    => 'Contractor',
            // 'view property work offer'    => 'Work Offer',
            'view property notes'        => 'Notes',
            'view property appointments' => 'Appointments',
        ];

        $tabs = [];

        foreach ($availableTabs as $permission => $name) {
            if ($user->can($permission)) {
                $tabs[] = ['name' => $name];
            }
        }

        // Get tabs for properties (you can customize the tabs as per your needs)
        // $tabs = [
        //     ['name' => 'Property'],
        //     ['name' => 'Owners'],
        //     ['name' => 'Compliance'],
        //     ['name' => 'Media'],
        //     ['name' => 'Offers'],
        //     ['name' => 'Tenancy'],
        //     ['name' => 'APS'],
        //     ['name' => 'Teams'],
        //     ['name' => 'Documents'],
        //     // ['name' => 'Contractor'],
        //     // ['name' => 'Work Offer'],
        //     ['name' => 'Notes'],
        //     ['name' => 'Appointments']
        // ];

        // Retrieve the content for the selected tab and property
        $content = $this->getTabContent($tabName, $propertyId, $property); // Dynamically get content for the tab and property

        // Check if the request is via AJAX (this handles dynamic content loading)
        if ($request->ajax()) {
            // If the request is via AJAX, return only the content
            return response()->json(['content' => $content]);
        }

        // Pass data to the view
        return view('backend.properties.index', compact('properties', 'tabs', 'propertyId', 'tabName', 'content', 'property'));
    }

    private function getTabContent($tabname, $propertyId, $property)
    {
        switch (strtolower($tabname)) {
            case 'property':

                // Fetch all station names and school names
                $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
                $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

                // Get the nearest station IDs and nearest school IDs from the property (these will be comma-separated strings)
                $stationIds = explode(',', $property->nearest_station);  // Convert to an array
                $schoolIds = explode(',', $property->nearest_school);    // Convert to an array

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
                $ownerGroups = OwnerGroup::with(['ownerGroupUsers.user', 'property'])
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

            case 'aps':
                return view('backend.properties.tabs.aps', compact('propertyId', 'property'))->render();
            case 'media':
                return view('backend.properties.tabs.media', compact('propertyId', 'property'))->render();
            case 'teams':
                return view('backend.properties.tabs.teams', compact('propertyId'))->render();
            case 'documents':

                $documents = $property->documents()->with('documentType')->orderByDesc('updated_at')->paginate(5);

                // Ensure it's an empty collection if no documents are found
                if ($documents->isEmpty()) {
                    $documents = collect();  // Make sure it's an empty collection, not null
                }
                $documentTypes = DocumentType::all();
                return view('backend.properties.tabs.documents', compact('propertyId', 'property', 'documentTypes', 'documents'))->render();
            // case 'contractor':
            //     return view('backend.properties.tabs.contractor', compact('propertyId'))->render();
            // case 'work offer':
            //     return view('backend.properties.tabs.work_offer', compact('propertyId'))->render();
            case 'notes':
                // Fetch the notes related to the specific property by property ID
                // $notes = Notes::where('property_id', $propertyId)->orderBy('updated_at', 'desc')->get();
                $notes = $property->notes()->with('noteType')->orderByDesc('updated_at')->paginate(5);

                // Ensure it's an empty collection if no notes are found
                if ($notes->isEmpty()) {
                    $notes = collect();  // Make sure it's an empty collection, not null
                }
                $noteTypes = NoteType::all();
                // Return the view and pass the notes data (null or the notes collection)
                return view('backend.properties.tabs.notes', compact('propertyId', 'property', 'notes', 'noteTypes'))->render();

            case 'appointments':
                $query = $property->events()->with(['diaryOwner', 'onBehalfOf'])->orderBy('start_datetime', 'desc');

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

            default:
                return 'Tab content not found';
        }
    }

    // Show the form for creating a new property.
    public function create()
    {
        return view('backend.properties.create'); // Return the create property view
    }

    // show quick form
    public function quick()
    {
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
            $validatedData = $request->validate($this->getValidationRules($request->step));

            // Convert market_on to JSON if it's an array
            // if ($request->has('market_on') && is_array($request->market_on)) {
            //     $validatedData['market_on'] = json_encode($request->market_on);  // Serialize the array to JSON
            // }

            // Store all data in session excluding token and step
            //$request->session()->put($request->except('_token', 'step'));

            //$userId = $request->session()->get('user_id'); // Retrieve the user ID from the session

            // Get property_id from the session or request
            $property_id = $request->property_id;

            // Collect responsibility data from the form
            $propertyResponsibilityIds = $request->input('PropertyResponsibility_id', []);
            $user_ids = $request->input('user_id', []);
            $designation_ids = $request->input('designation_id', []);
            $branch_ids = $request->input('branch_id', []);
            $commission_percentages = $request->input('commission_percentage', []);
            $commission_amounts = $request->input('commission_amount', []);

            $submitted_ids = []; // Track IDs of processed responsibilities

            // Iterate through the responsibilities and update or create them
            foreach ($user_ids as $index => $user_id) {
                $data = [
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

                // Update or create the responsibility
                $responsibility = PropertyResponsibility::updateOrCreate(
                    ['id' => $propertyResponsibilityIds[$index] ?? null], // Match by ID if provided
                    $data
                );

                $submitted_ids[] = $responsibility->id; // Track the ID of the responsibility
            }

            // Remove responsibilities that are not in the submitted IDs
            if (!empty($submitted_ids)) {
                PropertyResponsibility::where('property_id', $property_id)
                    ->whereNotIn('id', $submitted_ids)
                    ->whereNull('deleted_at')  // Ensure we're only soft-deleting active records
                    ->update(['deleted_by' => Auth::id()]); // Set 'deleted_by' to the authenticated user

                // Soft delete the records
                PropertyResponsibility::where('property_id', $property_id)
                    ->whereNotIn('id', $submitted_ids)
                    ->delete();
            }

            // Check if property_id is provided in the request
            if ($property_id) {
                $property = Property::find($property_id);

                $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
                $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

                // Get the nearest station IDs and nearest school IDs from the property (these will be comma-separated strings)
                $stationIds = explode(',', $property->nearest_station);  // Convert to an array
                $schoolIds = explode(',', $property->nearest_school);    // Convert to an array

                // Fetch the station and school names using the IDs
                $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
                $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

                // Fetch required data for dropdowns
                $users = User::select('id', 'name')->get(); // Fetch all users
                $designations = Designation::select('id', 'title')->get(); // Fetch all designations
                $branches = Branch::select('id', 'name')->get(); // Fetch all branches

                $PropertyResponsibility = PropertyResponsibility::where('property_id', $property_id)->get();

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
                    $property->update($validatedData);
                    // session()->forget('property_id');
                    // session()->forget('current_step');
                }
            } else {
                // Create new property only on the first step
                if ($request->step == 1) {
                    // Log the data before creation
                    // Generate Property Reference Number
                    $PropertyRefNumber = generateReferenceNumber(Property::class, 'prop_ref_no', 'RESISQP');
                    $validatedData['prop_ref_no'] = $PropertyRefNumber;
                    // $validatedData['prop_ref_no'] = $this->generatePropertyRefNumber();
                    Log::info('Creating new pref', $validatedData['prop_ref_no']);
                    Log::info('Creating new property', $validatedData);
                    $property = Property::create(array_merge($validatedData, ['added_by' => Auth::id(), 'step' => $request->step]));
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
            $validatedData = $request->validate($this->getValidationRulesQuick($request->step));

            // Get property_id from the request
            $property_id = $request->property_id;

            // Check if property_id is provided in the request
            if ($property_id) {
                $property = Property::find($property_id);
                if ($property) {
                    // Log the data before updating
                    Log::info('Updating property with ID ' . $property_id, $validatedData);
                    //update step
                    $validatedData['quick_step'] = $request->step;
                    $property->update($validatedData);
                    // session()->forget('property_id');
                }
            } else {
                // Create new property only empty property id
                if (empty($property_id)) {
                    $PropertyRefNumber = generateReferenceNumber(Property::class, 'prop_ref_no', 'RESISQP');

                    $validatedData['quick_step'] = $request->step;
                    // Generate Property Reference Number
                    $validatedData['prop_ref_no'] = $PropertyRefNumber;
                    // Log::info('Creating new pref', $validatedData['prop_ref_no']);
                    Log::info('Creating new property', $validatedData);
                    $property = Property::create(array_merge($validatedData, ['added_by' => Auth::id()]));
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

        // Check if the request step is 6
        // if ($property->step == 5) {
        // Fetch all station names and school names
        $allstations = StationName::select('id', 'name')->get();  // Fetch all station names
        $allschools = SchoolName::select('id', 'name')->get();    // Fetch all school names

        // Get the nearest station IDs and nearest school IDs from the property (these will be comma-separated strings)
        $stationIds = explode(',', $property->nearest_station);  // Convert to an array
        $schoolIds = explode(',', $property->nearest_school);    // Convert to an array

        // Fetch the station and school names using the IDs
        $stations = StationName::whereIn('id', $stationIds)->pluck('name', 'id');
        $schools = SchoolName::whereIn('id', $schoolIds)->pluck('name', 'id');

        // Fetch required data for dropdowns
        $users = User::select('id', 'name')->get(); // Fetch all users
        $designations = Designation::select('id', 'title')->get(); // Fetch all designations
        $branches = Branch::select('id', 'name')->get(); // Fetch all branches

        // Fetch PropertyResponsibility related to the current property
        // $PropertyResponsibility = PropertyResponsibility::where('property_id', $property->id)
        // ->select('id', 'responsibility')
        // ->get();

        $PropertyResponsibility = PropertyResponsibility::where('property_id', $property->id)->get();
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
        $property->update($validatedData); // Update the property

        return redirect()->route('admin.properties.index')->with('success', 'Property updated successfully.');
    }
    public function search(Request $request)
    {
        // Get the search query from the request
        $query = $request->input('query');

        // Search for properties based on multiple criteria
        $properties = Property::where('prop_ref_no', 'LIKE', '%' . $query . '%')
            ->orWhere('prop_name', 'LIKE', '%' . $query . '%')
            ->orWhere('line_1', 'LIKE', '%' . $query . '%')
            ->orWhere('line_2', 'LIKE', '%' . $query . '%')
            ->orWhere('city', 'LIKE', '%' . $query . '%')
            ->orWhere('country', 'LIKE', '%' . $query . '%')
            ->orWhere('postcode', 'LIKE', '%' . $query . '%')
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
        $properties = Property::onlyTrashed()->get(); // Fetch only soft-deleted properties
        // flash("You don't have permission for deleting this!")->error();
        return view('backend.properties.deleted', compact('properties'));
    }


    public function restore($id)
    {
        $property = Property::withTrashed()->findOrFail($id);
        $property->restore();

        // $response = [
        //     'status' => true,
        //     'message' => 'Property restored successfully!',
        // ];
        flash("Restored successfully")->success();
        return back();
        // return back()->with('success', $response['message']);
        //return redirect()->route('admin.properties.index')->with('success', $response['message']);

        //return redirect()->route('admin.properties.index')->with('success', 'Property restored successfully.');
    }

    public function bulkRestore(Request $request)
    {
        $propertyIds = explode(',', $request->input('property_ids')); // Convert the string to an array
        Property::withTrashed()->whereIn('id', $propertyIds)->restore();

        return redirect()->route('admin.properties.index')->with('success', 'Selected properties restored successfully.');
    }

    public function loadForm(Request $request)
    {
        $property = Property::find($request->property_id);
        $formType = $request->form_type;

        if (!$property) {
            return response()->json(['error' => 'Property not found'], 404);
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
                $data = $request->only([
                    'photos',
                    'floor_plan',
                    'view_360',
                    'video_url',
                    'instagram_url',
                    'youtube_url'
                ]);
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
                break;
            case 'property_status':
                $data = $request->only([
                    'sales_current_status',
                    'letting_current_status',
                    'status_description'
                ]);
                break;
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
            $stationIds = explode(',', $property->nearest_station);
            $schoolIds = explode(',', $property->nearest_school);

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

    private function getValidationRulesQuick($step)
    {
        switch ($step) {
            case 1:
                return [
                    // 'prop_name' => 'required|string|max:255',
                    'line_1' => 'required|string|max:255',
                    'line_2' => 'nullable|string|max:255',
                    'city' => 'required|string|max:100',
                    // 'country' => 'required|string|max:100',
                    'country' => 'required|exists:countries,id',
                    'county' => 'nullable|string|max:50',
                    'currency' => 'nullable|string|max:50',
                    'postcode' => 'nullable|string|max:20',
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

    private function getValidationRules($step)
    {
        switch ($step) {
            case 1:
                return [
                    'prop_name' => 'required|string|max:255',
                    'line_1' => 'required|string|max:255',
                    'line_2' => 'nullable|string|max:255',
                    'city' => 'required|string|max:100',
                    'country' => 'required|string|max:100',
                    'postcode' => 'required|string|max:20',
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
                    'service' => 'required|string',
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


}
