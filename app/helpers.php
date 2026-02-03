<?php
// app/helpers.php
use Carbon\Carbon;
use App\Models\User;
use App\Models\Upload;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

if (!function_exists('getPoundSymbol')) {
    function getPoundSymbol()
    {
        return '£';
    }
}

if (!function_exists('flashValidationErrors')) {
    function flashValidationErrors(ValidationException $e)
    {
        foreach ($e->errors() as $fieldErrors) {
            foreach ($fieldErrors as $message) {
                flash()->error($message);
            }
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
        $settings = Cache::remember('business_settings', 86400, function () {
            return BusinessSetting::all();
        });

        if ($lang == false) {
            $setting = $settings->where('type', $key)->first();
        } else {
            $setting = $settings->where('type', $key)->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        }
        return $setting == null ? $default : $setting->value;
    }
}

// if (!function_exists('overWriteEnvFile')) {
//     function overWriteEnvFile($type, $val)
//     {
//         $path = base_path('.env');
//         if (file_exists($path)) {
//             $val = '"'.trim($val).'"';
//             if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
//                 file_put_contents($path, str_replace(
//                     $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
//                 ));
//             }
//             else{
//                 file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
//             }
//         }
//     }
// }

if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        $asset = Cache::rememberForever('uploaded_asset_' . $id, function () use ($id) {
            return Upload::find($id);
        });

        if ($asset != null) {
            return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
        }
        return static_asset('asset/img/placeholder.jpg');
    }
}
if (!function_exists('my_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function my_asset($path, $secure = null)
    {
        if (env('FILESYSTEM_DRIVER') == 's3') {
            return Storage::disk('s3')->url($path);
        } else {
            // return app('url')->asset('public/' . $path, $secure);

            if (env('ENVIRONMENT') == 'Production') {
                return app('url')->asset('public/' . $path, $secure);
            } else {
                return app('url')->asset('storage/' . $path, $secure);
            }
        }
    }
}

if (!function_exists('static_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function static_asset($path, $secure = null)
    {
        // return app('url')->asset('public/' . $path, $secure);

        if (env('ENVIRONMENT') == 'Production') {
            return app('url')->asset('public/' . $path, $secure);
        } else {
            return app('url')->asset($path, $secure);
        }
    }
}

if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        $root = '//' . $_SERVER['HTTP_HOST'];

        if (env('ENVIRONMENT') == 'Production') {
            $root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        }

        return $root;
    }
}

if (!function_exists('getFileBaseURL')) {
    function getFileBaseURL()
    {
        if (env('FILESYSTEM_DRIVER') == 's3') {
            return env('AWS_URL') . '/';
        } else {
            return getBaseURL() . '/storage/';
        }
    }
}

// highlights the selected navigation on admin panel
if (!function_exists('areActiveRoutes')) {
    function areActiveRoutes(array $routes, $output = 'active')
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route)
                return $output;
        }
    }
}

if (!function_exists('current_user')) {
    /**
     * Get the currently authenticated user.
     *
     * @return User|null
     */
    function current_user(): ?User
    {
        return Auth::user();  // This is the best way to get the authenticated user
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format date to dd/mm/yyyy.
     *
     * @param  string  $date
     * @return string
     */
    function formatDate($date)
    {
        // Check if the date is not null or empty
        if ($date) {
            return Carbon::parse($date)->format('d/m/Y');
        }
        return null;  // Return null if no date is provided
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Format date & time to dd/mm/yyyy H:i A.
     *
     * @param  string  $dateTime
     * @return string|null
     */
    function formatDateTime($dateTime)
    {
        // Check if the dateTime is not null or empty
        if ($dateTime) {
            return Carbon::parse($dateTime)->format('d/m/Y h:i A');
        }
        return null;  // Return null if no date is provided
    }
}

if (!function_exists('booleanToYesNo')) {
    /**
     * Convert 0 or 1 to 'No' or 'Yes'.
     *
     * @param  int  $value
     * @return string
     */
    function booleanToYesNo($value)
    {
        return $value == 1 ? 'Yes' : 'No';
    }
}

if (!function_exists('booleanToString')) {
    /**
     * Convert a boolean (0 or 1) to custom string values.
     *
     * @param  int|bool  $value
     * @param  string  $trueText
     * @param  string  $falseText
     * @return string
     */
    function booleanToString($value, $trueText = 'Yes', $falseText = 'No')
    {
        return $value ? $trueText : $falseText;
    }
}


if (!function_exists('jsonDecodeAndPrint')) {
    /**
     * Decode a JSON string and return its values as a string.
     *
     * @param  string  $json
     * @param  string  $separator  The separator between items when printing (default is a comma)
     * @return string
     */
/*    function jsonDecodeAndPrint($json, $separator = ', ')
    {
        // Decode the JSON string into an array
        $decoded = json_decode($json, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';  // Return error message if decoding fails
        }

        // Return the values as a string with the given separator
        return implode($separator, $decoded);
    }*/
    
        /**
         * Decode a JSON string and return its values as a string.
         *
         * @param  mixed  $json  The JSON data, either as a string or an array.
         * @param  string  $separator  The separator between items when printing (default is a comma)
         * @return string
         */
        function jsonDecodeAndPrint($json, $separator = ', ')
        {
            // If $json is already an array, just use it
            if (is_array($json)) {
                $decoded = $json;
            } else {
                // Decode the JSON string into an array
                $decoded = json_decode($json, true);
            }
    
            // Check for JSON errors if it's a string
            if (!is_array($json) && json_last_error() !== JSON_ERROR_NONE) {
                return '';  // Return error message if decoding fails
            }
    
            // Return the values as a string with the given separator
            return implode($separator, $decoded);
        }
    
    
}

if (!function_exists('todayDate')) {
    /**
     * Get today's date in YYYY-MM-DD format.
     *
     * @return string
     */
    function todayDate()
    {
        return (new \DateTime())->format('d-m-Y');
    }
}

if (!function_exists('tomorrowDate')) {
    /**
     * Get tomorrow's date in YYYY-MM-DD format.
     *
     * @return string
     */
    function tomorrowDate()
    {
        return Carbon::tomorrow()->toDateString();
    }
}

if (!function_exists('convert_to_boolean')) {
    /**
     * Convert 'Yes'/'No' values to boolean true/false.
     *
     * @param string $value
     * @return bool
     */
    function convert_to_boolean($value)
    {
        return strtolower($value) === 'yes' ? true : false;
    }
}

if (!function_exists('beautify_string')) {
    /**
     * Replace hyphens, underscores, or special characters with spaces and convert to title case.
     *
     * @param string $value
     * @return string
     */
    function beautify_string($value)
    {
        // Replace hyphens and underscores with spaces
        $value = preg_replace('/[-_]+/', ' ', $value);

        // Convert to title case
        return ucwords($value);
    }
}

if (!function_exists('convert_to_uppercase')) {
    /**
     * Convert a string to uppercase.
     *
     * @param string $value
     * @return string
     */
    function convert_to_uppercase($value)
    {
        return strtoupper($value);
    }
}

if (!function_exists('convert_to_lowercase')) {
    /**
     * Convert a string to lowercase.
     *
     * @param string $value
     * @return string
     */
    function convert_to_lowercase($value)
    {
        return strtolower($value);
    }
}

if (!function_exists('capitalize_words')) {
    /**
     * Capitalize the first letter of each word in a string.
     *
     * @param string $value
     * @return string
     */
    function capitalize_words($value)
    {
        return ucwords(strtolower($value));
    }
}

if (!function_exists('capitalize_first_letter')) {
    /**
     * Capitalize the first letter of a string.
     *
     * @param string $value
     * @return string
     */
    function capitalize_first_letter($value)
    {
        return ucfirst(strtolower($value));
    }
}

if (!function_exists('searchProperties')) {
    function searchProperties(Request $request)
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
        return response()->json($properties->map(function ($property) {
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
}

if (!function_exists('getPropertyDetails')) {
    /**
     * Fetch specific property details by property ID and multiple column names.
     *
     * @param  int   $propertyId
     * @param  array $columns
     * @return string
     */
    function getPropertyDetails($propertyId, $columns = [])
    {
        // Log property_id to confirm it's being passed correctly
        Log::info('Fetching details for Property ID:', ['property_id' => $propertyId]);

        // Find the property by ID
        $property = Property::find($propertyId);

        // Log the property to confirm it was fetched
        Log::info('Property Details:', ['property' => $property]);

        if ($property) {
            // Initialize an empty array to store column values
            $propertyDetails = [];

            // Iterate over the given columns and get their values
            foreach ($columns as $column) {
                // Check if the column exists in the property model
                if (isset($property->$column)) {
                    // If the column exists, add its value to the array
                    $propertyDetails[] = trim($property->$column);
                } else {
                    // Log if the column doesn't exist or is null
                    Log::warning('Column does not exist in the Property model or is null:', ['column' => $column, 'property_id' => $propertyId]);
                }
            }

            // Log the column values before concatenation
            Log::info('Property Details (Trimmed):', ['property_details' => $propertyDetails]);

            // Concatenate the non-empty values with a space
            return implode(', ', array_filter($propertyDetails));
        }

        // If property is not found, log the issue and return a default message
        Log::warning('Property not found for ID:', ['property_id' => $propertyId]);
        return 'Property not found';
    }
}

if (!function_exists('getRepairCategoryDetails')) {
    /**
     * Fetch repair category details by ID.
     *
     * @param int $categoryId
     * @return string
     */
    function getRepairCategoryDetails($categoryId)
    {
        // Log the category ID for debugging
        Log::info('Fetching details for Repair Category ID:', ['category_id' => $categoryId]);

        // Find the repair category
        $category = \App\Models\RepairCategory::find($categoryId);

        // Log the category details
        Log::info('Repair Category Details:', ['category' => $category]);

        // Return the category name or a default message if not found
        if ($category) {
            return $category->name;  // Ensure that 'name' is the correct column
        }

        Log::warning('Repair Category not found for ID:', ['category_id' => $categoryId]);
        return 'Category not found';
    }
}

if (!function_exists('getFormattedRepairNavigation')) {
    /**
     * Convert stored repair_navigation (category ids) into human-readable category names.
     *
     * @param string $navigationString
     * @return string
     */
    function getFormattedRepairNavigation($navigationString)
    {
        // Decode the repair_navigation JSON string
        $categories = json_decode($navigationString, true);

        // Initialize an empty array to store category names
        $categoryNames = [];

        // Loop through each level in the decoded categories array
        foreach ($categories as $level => $categoryId) {
            // Fetch the category name by ID
            $category = \App\Models\RepairCategory::find($categoryId);

            // If category exists, append the name; otherwise, append the ID
            if ($category) {
                $categoryNames[] = $category->name;
            } else {
                $categoryNames[] = "Unknown Category (ID: $categoryId)";
            }
        }

        // Join the category names with " > " separator and return
        return implode(' > ', $categoryNames);
    }
}

if (!function_exists('get_users_by_property_and_category')) {
    /**
     * Fetch users by property ID and category ID.
     *
     * @param int $propertyId
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    /*function get_users_by_property_and_category($propertyId, $categoryId)
    {
        return Cache::rememberForever("users_{$propertyId}_{$categoryId}", function () use ($propertyId, $categoryId) {
            return User::where('category_id', $categoryId)
                ->whereRaw('JSON_CONTAINS(selected_properties, ?)', [$propertyId])
                ->get(['id', 'name', 'address_line_1', 'address_line_2', 'postcode', 'city', 'country', 'email', 'phone'])
                ->map(function ($user) {
                    return array_merge($user->toArray(), [
                        'full_address' => implode(', ', array_filter([
                            $user->address_line_1,
                            $user->address_line_2,
                            $user->postcode,
                            $user->city,
                            $user->country
                        ]))
                    ]);
                });
        });
    }*/

    /**
     * Fetch users by property ID and role ID.
     *
     * @param int $propertyId
     * @param int $roleId
     * @return \Illuminate\Support\Collection
     */
    function get_users_by_property_and_role($propertyId, $roleId)
    {
        // Get the role name from its ID using Spatie Role model
        $role = Role::findById($roleId);

        return Cache::rememberForever("users_{$propertyId}_role_{$roleId}", function () use ($propertyId, $role) {
            return User::role($role->name)
                ->whereRaw('JSON_CONTAINS(selected_properties, ?)', [json_encode($propertyId)])
                ->get(['id', 'name', 'address_line_1', 'address_line_2', 'postcode', 'city', 'country', 'email', 'phone'])
                ->map(function ($user) {
                    return array_merge($user->toArray(), [
                        'full_address' => implode(', ', array_filter([
                            $user->address_line_1,
                            $user->address_line_2,
                            $user->postcode,
                            $user->city,
                            $user->country
                        ]))
                    ]);
                });
        });
    }
}

if (!function_exists('get_user_address_name_by_id')) {
    /**
     * Fetch a user's full address by its ID.
     *
     * @param int $userId
     * @return string
     */
    function get_user_address_name_by_id($userId)
    {
        return Cache::rememberForever("users_{$userId}", function () use ($userId) {
            $user = User::find($userId, [
                'name', 'address_line_1', 'address_line_2', 'postcode', 'city', 'country', 'email', 'phone'
            ]);

            if (!$user) {
                return 'N/A';
            }

            // Filter out empty address fields
            $addressParts = array_filter([
                $user->address_line_1,
                $user->address_line_2,
                $user->postcode,
                $user->city,
                $user->country
            ]);

            // If all address fields are empty, set a default message
            $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'Address not available';

            // Handle empty email and phone separately
            $email = !empty($user->email) ? "Email: {$user->email}" : '';
            $phone = !empty($user->phone) ? "Phone: {$user->phone}" : '';

            // Ensure email or phone is displayed, otherwise show a default message
            $userInfo = trim($email . '<br>' . $phone);
            if (empty($userInfo)) {
                $userInfo = 'User details not available';
            }

            // Return formatted user details as a string
            return "<strong>{$user->name}</strong><br>{$fullAddress}<br>{$userInfo}";
        });
    }

}


if (!function_exists('get_tenants_by_property')) {
    /**
     * Fetch tenants by property ID using Eloquent relationships and include property address.
     *
     * @param int $propertyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function get_tenants_by_property($propertyId)
    {
        return Cache::rememberForever("tenants_{$propertyId}", function () use ($propertyId) {
            $property = Property::where('id', $propertyId)->first(['line_1', 'line_2', 'postcode', 'city', 'country']);

            // Define a default message if address is missing
            $defaultAddress = 'No address available';

            // Check if all address fields are empty
            $propertyAddress = null;
            if ($property) {
                $addressParts = array_filter([
                    $property->line_1 ?? '',
                    $property->line_2 ?? '',
                    $property->postcode ?? '',
                    $property->city ?? '',
                    $property->country ?? ''
                ]);

                // If all address fields are empty, use default message
                $propertyAddress = empty($addressParts) ? $defaultAddress : implode(', ', $addressParts);
            } else {
                $propertyAddress = $defaultAddress;  // If no property is found
            }

            return User::whereHas('tenantMembers.tenancy', function ($query) use ($propertyId) {
                $query->where('property_id', $propertyId);
            })->get(['id', 'name', 'email', 'phone'])->map(function ($tenant) use ($propertyAddress) {
                return array_merge($tenant->toArray(), [
                    'full_address' => $propertyAddress
                ]);
            });
        });
    }
}

if (!function_exists('generateReferenceNumber')) {
    /**
     * Generate a unique reference number for a given database table.
     *
     * This function retrieves the last inserted record from the specified table and column,
     * extracts the numerical part of the reference number, increments it, and returns a new
     * reference number formatted with a fixed prefix and zero-padded number.
     *
     * @param string $modelClass The Eloquent model class (e.g., Order::class).
     * @param string $column The column name where the reference number is stored.
     * @param string $prefix The prefix to use for the reference number.
     * @return string The newly generated reference number.
     */
    function generateReferenceNumber($modelClass, $column, $prefix)
    {
        return DB::transaction(function () use ($modelClass, $column, $prefix) {
            $lastEntry = $modelClass::orderBy('id', 'desc')->lockForUpdate()->first();

            if ($lastEntry && preg_match('/' . preg_quote($prefix) . '(\d+)/', $lastEntry->$column, $matches)) {
                $number = (int) $matches[1] + 1;
            } else {
                $number = 1;
            }

            return $prefix . str_pad($number, 7, '0', STR_PAD_LEFT);
        });
    }
}

if (!function_exists('getInvoiceStatusBadge')) {
    function getInvoiceStatusBadge($statusId)
    {
        return [
            1 => 'bg-warning',  // Pending
            2 => 'bg-success',  // Paid
            3 => 'bg-danger',   // Overdue
            4 => 'bg-secondary' // Cancelled
        ][$statusId] ?? 'bg-dark';
    }
}

if (!function_exists('getInvoiceStatusText')) {
    function getInvoiceStatusText($statusId)
    {
        return [
            1 => 'Pending',
            2 => 'Paid',
            3 => 'Partially Paid',
            4 => 'Overdue',
            5 => 'Cancelled'
        ][$statusId] ?? 'Unknown';
    }
}

if (!function_exists('getInvoiceStatusDetails')) {
    function getInvoiceStatusDetails($statusId)
    {
        $statuses = [
            1 => ['text' => 'Pending',         'badge' => 'bg-warning'],
            2 => ['text' => 'Paid',            'badge' => 'bg-success'],
            3 => ['text' => 'Partially Paid',  'badge' => 'bg-info'],
            4 => ['text' => 'Overdue',         'badge' => 'bg-danger'],
            5 => ['text' => 'Cancelled',       'badge' => 'bg-secondary'],
        ];

        return $statuses[$statusId] ?? ['text' => 'Unknown', 'badge' => 'bg-dark'];
    }
}


if (!function_exists('get_property_address_by_id')) {
    /**
     * Fetch the address of a property by its ID and cache the result.
     *
     * @param int $propertyId
     * @return string
     */
    function get_property_address_by_id($propertyId)
    {
        return Cache::rememberForever("property_address_{$propertyId}", function () use ($propertyId) {
            $property = Property::where('id', $propertyId)->first(['line_1', 'line_2', 'postcode', 'city', 'country']);

            // Define a default message if address is missing
            $defaultAddress = 'No address available';

            // Check if all address fields are empty
            if ($property) {
                $addressParts = array_filter([
                    $property->line_1 ?? '',
                    $property->line_2 ?? '',
                    $property->postcode ?? '',
                    $property->city ?? '',
                    $property->country ?? ''
                ]);

                // If all address fields are empty, use default message
                return empty($addressParts) ? $defaultAddress : implode(', ', $addressParts);
            }

            return $defaultAddress;  // If no property is found
        });
    }
}

if (!function_exists('attachmentViewer')) {
    function attachmentViewer($fileUrl, $title = 'View Attachment', $buttonClass = 'btn btn-primary', $modalSize = 'lg', $customWidth = null, $customHeight = null)
    {
        if (!$fileUrl) {
            return '';
        }

        $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $pdfExtensions = ['pdf'];

        $modalId = 'attachmentModal_' . md5($fileUrl);

        // Define Bootstrap modal size classes
        $modalSizeClass = '';
        if ($modalSize === 'sm') {
            $modalSizeClass = 'modal-sm';
        } elseif ($modalSize === 'lg') {
            $modalSizeClass = 'modal-lg';
        } elseif ($modalSize === 'xl') {
            $modalSizeClass = 'modal-xl';
        }

        // Generate the button
        $button = '<button type="button" class="' . $buttonClass . '" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">' . $title . '</button>';

        // Generate the modal
        $modal = '<div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . '_label" aria-hidden="true">
            <div class="modal-dialog ' . $modalSizeClass . ' modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="' . $modalId . '_label">Attachment Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center" style="' . ($customWidth ? 'max-width:' . $customWidth . ';' : '') . ($customHeight ? 'height:' . $customHeight . '; overflow:auto;' : '') . '">';

        // Check file type and render accordingly
        if (in_array(strtolower($fileExtension), $imageExtensions)) {
            $modal .= '<img src="' . $fileUrl . '" class="img-fluid w-100"  style="height: auto !important;" alt="Attachment">';
        } elseif (in_array(strtolower($fileExtension), $pdfExtensions)) {
            $modal .= '<iframe src="' . $fileUrl . '" width="100%" height="500px" style="border: none;"></iframe>';
        } else {
            $modal .= '<p>Unable to preview this file type. <a href="' . $fileUrl . '" target="_blank">Download File</a></p>';
        }

        $modal .= '</div>
                </div>
            </div>
        </div>';

        return $button . $modal;
    }
}

if (!function_exists('safeAssignRoles')) {
    /**
     * Assign multiple roles with automatic creation and fallback
     * @param Model $model
     * @param string|array|\Spatie\Permission\Contracts\Role $roles
     */
    function safeAssignRoles(Model $model, $roles)
    {
        // Convert single role to array for consistent processing
        $roles = is_array($roles) ? $roles : [$roles];
        
        // Prepare role objects first
        $roleObjects = [];
        foreach ($roles as $role) {
            $roleObjects[] = is_string($role) ? Role::firstOrCreate(['name' => $role]) : $role;
        }

        DB::transaction(function () use ($model, $roleObjects) {
            try {
                // Attempt native assignment for all roles at once
                $model->assignRole($roleObjects);
            } catch (\Exception $e) {
                // Fallback: Handle all roles in bulk
                $existingRoles = $model->roles()->pluck('id')->toArray();
                $newRoles = [];

                foreach ($roleObjects as $role) {
                    if (!in_array($role->id, $existingRoles)) {
                        $newRoles[] = [
                            'role_id' => $role->id,
                            'model_type' => get_class($model),
                            'model_id' => $model->id,
                        ];
                    }
                }

                if (!empty($newRoles)) {
                    DB::table('model_has_roles')->insert($newRoles);
                    $model->unsetRelation('roles');
                }
            }
        });
    }

        // email template data
    if (!function_exists('get_email_template_data')) {
        function get_email_template_data($identifier, $colmn_name = null)
        {
            $value = EmailTemplate::where('identifier', $identifier)->first()->$colmn_name;
            return $value;
        }
    }
    
    if (!function_exists('render_template')) {
        /**
         * Render template HTML/text by replacing [[key]] placeholders with values.
         *
         * @param string $templateHtml
         * @param array $placeholders (associative: 'key' => 'value')
         * @param array $rawKeys Optional list of keys that should NOT be escaped (e.g. links)
         * @return string
         */
        function render_template(string $templateHtml, array $placeholders = [], array $rawKeys = []): string
        {
            $search = $replace = [];

            foreach ($placeholders as $key => $value) {
                $search[] = '[[' . $key . ']]';

                if (in_array($key, $rawKeys, true)) {
                    // raw insertion (not escaped)
                    $replace[] = $value;
                } else {
                    $replace[] = e($value);
                }
            }

            return str_replace($search, $replace, $templateHtml);
        }
    }

    if (!function_exists('booleanBadge')) {
        /**
         * Returns a badge HTML for boolean values.
         *
         * @param bool $value
         * @param string $trueText
         * @param string $falseText
         * @return string
         */
        function booleanBadge($value, $trueText = 'Yes', $falseText = 'No')
        {
            return $value
                ? '<span class="badge bg-success">' . e($trueText) . '</span>'
                : '<span class="badge bg-secondary">' . e($falseText) . '</span>';
        }
    }

    if (! function_exists('generateDocumentNumber')) {
        function generateDocumentNumber(string $docType = 'refund', string $prefix = 'RFND', $branchId = null): string
        {
            return DB::transaction(function () use ($docType, $prefix, $branchId) {
                $row = DB::table('document_sequences')
                    ->where('document_type', $docType)
                    ->where(function($q) use ($branchId) {
                        if (is_null($branchId)) {
                            $q->whereNull('branch_id');
                        } else {
                            $q->where('branch_id', $branchId);
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $row) {
                    DB::table('document_sequences')->insert([
                        'document_type' => $docType,
                        'prefix' => $prefix,
                        'next_number' => 2,
                        'branch_id' => $branchId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $num = 1;
                } else {
                    $num = $row->next_number;
                    DB::table('document_sequences')->where('id', $row->id)
                        ->update(['next_number' => DB::raw('next_number + 1'), 'updated_at' => now()]);
                    if (is_null($prefix)) {
                        $prefix = $row->prefix;
                    }
                }

                return ($prefix ? $prefix . '-' : '') . str_pad($num, 6, '0', STR_PAD_LEFT);
            });
        }
    }

}

/*
{!! attachmentViewer(uploaded_asset($quoteAttachment), 'View Quote', 'btn btn-primary', 'lg') !!}
{!! attachmentViewer(uploaded_asset($quoteAttachment), 'View Quote', 'btn btn-primary', 'sm') !!}
{!! attachmentViewer(uploaded_asset($quoteAttachment), 'View Quote', 'btn btn-primary', 'xl') !!}
{!! attachmentViewer(uploaded_asset($quoteAttachment), 'View Quote', 'btn btn-primary', '', '600px', '400px') !!}
*/