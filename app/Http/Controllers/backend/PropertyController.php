<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PropertyController
{
    // public function index(Request $request)
    // {
    //     $properties = Property::all(); // Fetch all properties
    //     // Get property ID and tabname from the query parameters
    //     $propertyId = $request->query('property_id');
    //     $tabName = $request->query('tabname', 'property'); // Default to 'property' if no tab is specified

    //     // Get tabs for properties
    //     $tabs = [
    //         ['name' => 'Property'],
    //         ['name' => 'Owners'],
    //         ['name' => 'Offers'],
    //         ['name' => 'Complience'],
    //         ['name' => 'Tenancy'],
    //         ['name' => 'APS'],
    //         ['name' => 'Media'],
    //         ['name' => 'Teams'],
    //         ['name' => 'Contractor'],
    //         ['name' => 'Work Offer'],
    //         ['name' => 'Note']
    //     ];

    //     return view('backend.properties.index', compact('properties', 'tabs', 'propertyId', 'tabName'));
    // }
    public function index(Request $request)
{
    // Fetch all properties
    $properties = Property::all();

    // Get property_id and tabname from query parameters
    $propertyId = $request->query('property_id');
    $tabName = $request->query('tabname', 'property'); // Default to 'property' if no tab is specified

    // Check if the property_id is provided, otherwise, select the first property or handle it gracefully
    $property = $propertyId ? Property::findOrFail($propertyId) : $properties->first(); // Use the first property if none is selected

    // Get tabs for properties (you can customize the tabs as per your needs)

    $tabs = [
        ['name' => 'Property'],
        ['name' => 'Owners'],
        ['name' => 'Offers'],
        ['name' => 'Complience'],
        ['name' => 'Tenancy'],
        ['name' => 'APS'],
        ['name' => 'Media'],
        ['name' => 'Teams'],
        ['name' => 'Contractor'],
        ['name' => 'Work Offer'],
        ['name' => 'Note']
    ];

    // Validate if the tabName exists in the tabs array. If not, set it to 'Property'
    // $validTabNames = array_column($tabs, 'name');
    // if (!in_array($tabName, $validTabNames)) {
    //     $tabName = 'Property'; // Default to Property if the provided tabName is invalid
    // }

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
            // Pass only the selected property details
            return view('backend.properties.tabs.property', compact('propertyId', 'tabname', 'property'))->render();
        case 'owners':
            return view('backend.properties.tabs.owners', compact('propertyId'))->render();
        case 'offers':
            return view('backend.properties.tabs.offers', compact('propertyId'))->render();
        case 'complience':
            return view('backend.properties.tabs.complience', compact('propertyId'))->render();
        case 'tenancy':
            return view('backend.properties.tabs.tenancy', compact('propertyId'))->render();
        case 'aps':
            return view('backend.properties.tabs.aps', compact('propertyId'))->render();
        case 'media':
            return view('backend.properties.tabs.media', compact('propertyId'))->render();
        case 'teams':
            return view('backend.properties.tabs.teams', compact('propertyId'))->render();
        case 'contractor':
            return view('backend.properties.tabs.contractor', compact('propertyId'))->render();
        case 'work offer':
            return view('backend.properties.tabs.work_offer', compact('propertyId'))->render();
        case 'note':
            return view('backend.properties.tabs.note', compact('propertyId'))->render();
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
        return view('backend.properties.quick'); // Return the create property view
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
            $property_id =  $request->property_id;
            // Check if property_id is provided in the request
            if ($property_id) {
                $property = Property::find($property_id);
                if ($property) {
                    // Log the data before updating
                    Log::info('Updating property with ID ' . $property_id, $validatedData);
                    $validatedData['video_url'] = $request->video_url ?: null;
                    $validatedData['step'] = $request->step;
                    $property->update($validatedData);
                    // session()->forget('property_id');
                    // session()->forget('current_step');
                }
            } else {
                // Create new property only on the first step
                if ($request->step == 1) {
                    // Log the data before creation
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

            // Load the next step view
            // return view('backend.properties.form_components.step' . ($request->step + 1));
            // return view('backend.properties.form_components.step' . ($request->step + 1))->withInput();
            return view('backend.properties.form_components.step' . ($request->step + 1), compact('property'));
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
                    $validatedData['quick_step'] = $request->step;
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

        // Check if the step is valid
        if ($step > 0 && $step <= $totalSteps) {
            return view('backend.properties.quick_form_components.step' . $step, compact('property')); // Return the corresponding Blade view
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
        return view('backend.properties.edit', compact('property'));
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
                    'country' => 'required|string|max:100',
                    'postcode' => 'required|string|max:20',
                ];
            case 2:
                return [
                    // 'line_1' => 'required|string|max:255',
                    // 'line_2' => 'nullable|string|max:255',
                    // 'city' => 'required|string|max:100',
                    // 'country' => 'required|string|max:100',
                    // 'postcode' => 'required|string|max:20',
                    'specific_property_type' => 'required|string',
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
                    'garden' => 'required|string',
                    'balcony' => 'required|string',
                ];
            case 8:
                return [
                    'price' => 'required|numeric',
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
                    'current_status' => 'required|string',
                    'status_description' => 'nullable|string',
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
                    // 'price' => 'required|numeric',
                    'letting_price' => 'nullable|numeric',
                    'ground_rent' => 'nullable|numeric',
                    'service_charge' => 'nullable|numeric',
                    'annual_council_tax' => 'nullable|numeric',
                    'council_tax_band' => 'nullable|string|max:50',
                    'tenure' => 'required',
                    'length_of_lease' => 'nullable|integer',
                ];
            case 7:
                return [
                    'epc_rating' => 'required',
                    'is_gas' => 'required   ',
                ];
            case 8:
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
            case 9:
                return [
                    'designation' => 'required',
                    'branch' => 'required',
                    'commission_percentage' => 'required',
                    'commission_amount' => 'required',
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
}
