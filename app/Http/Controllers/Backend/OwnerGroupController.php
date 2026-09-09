<?php

namespace App\Http\Controllers\Backend;

use App\Models\OwnerGroup;
use App\Models\OwnerGroupUser;
use App\Models\User;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OwnerGroupController
{
    /**
     * Display a listing of the OwnerGroup.
     */
    public function index()
    {
        $ownerGroups = $this->ownerGroupsForCurrentAccount()
            ->with('property')
            ->get();

        return view('backend.owner_groups.index', compact('ownerGroups'));
    }

    /**
     * Show the form for creating a new OwnerGroup.
     */
    public function create()
    {
        // $users = User::all();
        // Fetch users where category_id is 1
        // $users = User::where('category_id', 1)->get();

        $users = $this->ownersForForm();
        $properties = $this->propertiesForCurrentAccount()->get();

        return view('backend.owner_groups.create', compact('users', 'properties'));
    }

    public function createGroup()
    {
        // $users = User::all();
        // Fetch users where category_id is 1
        // $users = User::where('category_id', 1)->get();
        $users = $this->ownersForCurrentAccount()->get();
        $properties = $this->propertiesForCurrentAccount()->get();
        return view('backend.owner_groups.create-group', compact('users', 'properties'));
    }

    /**
     * Store a newly created OwnerGroup in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'property_id' => 'required|exists:properties,id',
            'purchased_date' => 'required|date',
            'sold_date' => 'nullable|date',
            'archived_date' => 'nullable|date',
            'status' => 'required|string|max:255',
        ]);

        $this->ensurePropertyIsAccessible((int) $validatedData['property_id']);
        $this->ensureOwnersAreAccessible([(int) $validatedData['user_id']]);

        $actorId = Auth::id();
        $ownerGroup = OwnerGroup::create([
            'property_id' => $validatedData['property_id'],
            'purchased_date' => $validatedData['purchased_date'],
            'sold_date' => $validatedData['sold_date'] ?? null,
            'archived_date' => $validatedData['archived_date'] ?? null,
            'status' => $validatedData['status'],
            'added_by' => $actorId,
        ]);

        OwnerGroupUser::create([
            'owner_group_id' => $ownerGroup->id,
            'user_id' => $validatedData['user_id'],
            'is_main' => 1,
            'added_by' => $actorId,
        ]);

        flash('Owner Group created successfully!')->success();

        return redirect()->route('admin.owner-groups.index');
    }

    public function storeGroup(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'user_id' => 'required|array|min:1', // Ensure at least one user is selected
            'user_id.*' => 'exists:users,id', // Validate each user ID exists
            'is_main' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::in($request->input('user_id', [])),
            ], // Ensure the main user is one of the selected users
            'purchased_date' => 'required|date',
            'sold_date' => 'nullable|date|after_or_equal:purchased_date', // Optional, must be after purchased date
            'archived_date' => 'nullable|date|after_or_equal:purchased_date', // Optional, must be after purchased date
            'status' => 'required|in:active,inactive,archived',
        ]);

        $this->ensurePropertyIsAccessible((int) $validated['property_id']);
        $this->ensureOwnersAreAccessible($validated['user_id']);

        if ($validated['status'] === 'active' && $request->filled('archived_date')) {
            return response()->json([
                'status' => false,
                'errors' => [
                    'archived_date' => ['Archived date cannot be set when status is active.']
                ],
                'message' => 'Invalid data provided.',
            ]);
        }


        // Check if the status is 'active'
        if ($request->status === 'active') {
            // Check if there's an existing active group for the same property
            $existingActiveGroup = OwnerGroup::where('property_id', $request->property_id)
                                            ->where('status', 'active')
                                            ->first();

            if ($existingActiveGroup) {
                // Ask user for confirmation to archive the existing group
                if (!$request->has('confirm_archive') || $request->confirm_archive !== 'yes') {
                    // Return a response indicating the active group exists and ask for confirmation
                    return response()->json([
                        'status' => false,
                        'notification' => 'There is an active group for this property. Do you want to archive the existing one and activate the new group?',
                    ]);
                }

                // Archive the existing active group
                $existingActiveGroup->update([
                    'status' => 'archived',
                    'archived_date' => now(),
                ]);
            }
        }

        // Get the current logged-in user
        $actorId = Auth::id();

        // Create a new OwnerGroup record
        $ownerGroup = OwnerGroup::create([
            'property_id' => $validated['property_id'],
            'purchased_date' => $validated['purchased_date'],
            'sold_date' => $request->sold_date, // Optional field
            'archived_date' => $request->archived_date, // Optional field
            'status' => $validated['status'],
            'added_by' => $actorId,
        ]);

        // Loop through selected users and create OwnerGroupUser records
        foreach ($validated['user_id'] as $ownerId) {
            // Debugging the user ID and is_main value
            // dd($userId, $validated['is_main']); // This will help confirm the values you're comparing

            // Ensure type matching by casting to integer
            $isMain = (intval($ownerId) === intval($validated['is_main'])) ? 1 : 0; // Set `is_main` for the selected main user
            // dd($isMain);

            OwnerGroupUser::create([
                'owner_group_id' => $ownerGroup->id,
                'user_id' => $ownerId,
                'is_main' => $isMain, // Assign 1 if it's the main user, 0 otherwise
                'added_by' => $actorId,
            ]);
        }

        // Return a successful response
        return response()->json([
            'status' => true,
            'notification' => 'Owner group added successfully!',
        ]);
        // Flash a success message
        // flash("Owner Group created successfully!")->success();

        // return back();
    }


    /**
     * Display the specified OwnerGroup.
     */
    public function show($id)
    {
        $ownerGroup = $this->ownerGroupsForCurrentAccount()
            ->with('property', 'estateCharges')
            ->findOrFail($id);

        \Illuminate\Support\Facades\Gate::authorize('view', $ownerGroup);

        return view('backend.owner_groups.show', compact('ownerGroup'));
    }

    /**
     * Show the form for editing the specified OwnerGroup.
     */
    public function edit(Request $request, $id)
    {
        $ownerGroup = $this->ownerGroupsForCurrentAccount()
            ->with('ownerGroupUsers.user')
            ->findOrFail($id);
        $users = $this->ownersForForm();
        $selectedUsers = $ownerGroup->ownerGroupUsers->pluck('user_id')->toArray();
        $properties = $this->propertiesForCurrentAccount()->get();

        if ($request->ajax()) {
            return view('backend.owner_groups.edit-group', compact('ownerGroup', 'users', 'selectedUsers'));
        }

        return view('backend.owner_groups.edit', compact('ownerGroup', 'users', 'selectedUsers', 'properties'));
    }
    // public function edit($id)
    // {
    //     $ownerGroup = OwnerGroup::with('ownerGroupUsers.user')->findOrFail($id);
    //     return view('backend.owner_groups.edit', compact('ownerGroup'));
    // }

    // public function edit($id)
    // {
    //     $ownerGroup = OwnerGroup::findOrFail($id);
    //     $users = User::all();
    //     $properties = Property::all();
    //     return view('backend.owner_groups.edit', compact('ownerGroup', 'users', 'properties'));
    // }

    public function updateGroup(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'user_id' => 'required|array|min:1', // Ensure at least one user is selected
            'user_id.*' => 'exists:users,id', // Validate each user ID exists
            'is_main' => [
                'required',
                'integer',
                Rule::in($request->input('user_id', [])),
            ], // Ensure the main user is one of the selected users
            'purchased_date' => 'required|date',
            'sold_date' => 'nullable|date|after_or_equal:purchased_date', // Optional, must be after purchased date
            'archived_date' => 'nullable|date', // Optional, must be after purchased date
            'status' => 'required|in:active,inactive,archived',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'notification' => 'Validation failed. Please check the inputs and try again.',
                'errors' => $validator->errors(),
            ]);
        }

        $this->ensurePropertyIsAccessible((int) $request->property_id);
        $this->ensureOwnersAreAccessible($request->input('user_id', []));
        
        // Add custom logic
        $validator->after(function ($validator) use ($request) {
            $archivedDate = $request->archived_date;
            $purchasedDate = $request->purchased_date;

            if ($request->status === 'active' && $request->filled('archived_date')) {
                $validator->errors()->add('archived_date', 'Archived date cannot be set when status is active.');
            }

            if ($request->status === 'archived') {
                if (!$request->filled('archived_date')) {
                    $validator->errors()->add('archived_date', 'Archived date is required when status is archived.');
                } elseif ($purchasedDate && $archivedDate && strtotime($archivedDate) < strtotime($purchasedDate)) {
                    $validator->errors()->add('archived_date', 'Archived date must be after or equal to purchased date.');
                }
            }

            // Optional: Also validate date logic if status is inactive but date is provided
            if ($request->status === 'inactive' && $archivedDate && $purchasedDate && strtotime($archivedDate) < strtotime($purchasedDate)) {
                $validator->errors()->add('archived_date', 'Archived date must be after or equal to purchased date.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'notification' => 'Validation failed. Please check the inputs and try again.',
                'errors' => $validator->errors(),
            ]);
        }
        
        // Get the current logged-in user
        $userId = Auth::id();

        // Find the existing OwnerGroup record by ID
        $ownerGroup = $this->ownerGroupsForCurrentAccount()
            ->findOrFail($request->route('id'));

        // Get validated data
        $validated = $validator->validated();

        // **Case 1: Active to Archived**
        if ($ownerGroup->status === 'active' && $validated['status'] === 'archived') {

            // Ensure there is at least one other owner group with the same property_id
            $ownerGroupsCount = OwnerGroup::where('property_id', $ownerGroup->property_id)
                ->count();

            if ($ownerGroupsCount <= 1) {
                return response()->json([
                    'status' => false,
                    'notification' => 'Cannot archive the last owner group for this property. At least one group must remain.',
                ]);
            }

            // Ask for confirmation if the user intends to archive the active owner group
            if (!$request->has('confirm_archive') || $request->confirm_archive !== 'yes') {
                return response()->json([
                    'status' => false,
                    'notification' => 'Are you sure you want to archive this active owner group? Confirm to proceed.',
                ]);
            }

            // Check if there is another archived owner group for the same property_id
            $recentArchivedGroup = OwnerGroup::where('property_id', $ownerGroup->property_id)
                ->where('status', 'archived')
                ->latest() // Get the most recent archived group
                ->first();

            if ($recentArchivedGroup) {
                // Update the recent archived group to active
                $recentArchivedGroup->update([
                    'status' => 'active',
                    'archived_date' => null, // Clear archived date as it's now active
                ]);
            }

            $validated['status'] = 'archived';
            $validated['archived_date'] = now();
        }


        // **Case 2: Archived to Active**
        if ($ownerGroup->status === 'archived' && $validated['status'] === 'active') {
            $existingActiveGroup = OwnerGroup::where('property_id', $ownerGroup->property_id)
                ->where('status', 'active')
                ->first();

            if ($existingActiveGroup) {
                // Ask user for confirmation to archive the currently active group
                if (!$request->has('confirm_archive') || $request->confirm_archive !== 'yes') {
                    return response()->json([
                        'status' => false,
                        'notification' => 'An active owner group already exists. Do you want to archive it and activate this one?',
                    ]);
                }

                // Archive the currently active group
                $existingActiveGroup->update([
                    'status' => 'archived',
                    'archived_date' => now(),
                ]);
            }

            // Clear archived date for the group being activated
            $validated['archived_date'] = null;
        }

        // Update the OwnerGroup record
        $ownerGroup->update([
            'property_id' => $validated['property_id'],
            'purchased_date' => $validated['purchased_date'],
            'sold_date' => $validated['sold_date'],
            'archived_date' => $validated['archived_date'],
            'status' => $validated['status'],
            'updated_by' => $userId,
        ]);

        // Get the existing users in the owner group
        $existingUsers = $ownerGroup->ownerGroupUsers->pluck('user_id')->toArray();

        // Determine which users are to be kept (new + existing ones in the request)
        $newUsers = $validated['user_id'];

        // Find users that need to be removed (existing but not in the request)
        $usersToRemove = array_diff($existingUsers, $newUsers);

        // Force delete users that are in the group but not in the request
        if (!empty($usersToRemove)) {
            OwnerGroupUser::where('owner_group_id', $ownerGroup->id)
                ->whereIn('user_id', $usersToRemove)
                ->forceDelete(); //permanently delete
                // ->delete(); //soft delete if used
        }

        // Reset the `is_main` flag for all users in the group
        OwnerGroupUser::where('owner_group_id', $ownerGroup->id)
            ->update([
                'is_main' => 0,
                'updated_by' => $userId,
            ]);

        // Merge the existing and new users
        $mergedUsers = array_unique(array_merge($existingUsers, $newUsers));

        // Loop through the merged users and either update or insert
        foreach ($mergedUsers as $userId) {
            // Determine if the current user should be marked as 'is_main'
            $isMain = (intval($userId) === intval($validated['is_main'])) ? 1 : 0;

            // If the user already exists, update it
            if (in_array($userId, $existingUsers)) {
                OwnerGroupUser::where('owner_group_id', $ownerGroup->id)
                    ->where('user_id', $userId)
                    ->update([
                        'is_main' => $isMain,
                    ]);
            } else {
                // Otherwise, create a new user association
                OwnerGroupUser::create([
                    'owner_group_id' => $ownerGroup->id,
                    'user_id' => $userId,
                    'is_main' => $isMain,
                    'added_by' => $userId,
                ]);
            }
        }
        return response()->json([
            'status' => true,
            'notification' => 'Owner Group updated successfully!',
        ]);

        // Flash a success message
        // flash("Owner Group updated successfully!")->success();
        // return back();
    }



    /**
     * Update the specified OwnerGroup in storage.
     */
    public function update(Request $request, $ownerGroup)
    {
        $ownerGroup = $this->ownerGroupsForCurrentAccount()
            ->with('ownerGroupUsers')
            ->findOrFail($ownerGroup);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'property_id' => 'required|exists:properties,id',
            'purchased_date' => 'required|date',
            'sold_date' => 'nullable|date',
            'archived_date' => 'nullable|date',
            'status' => 'required|string|max:255',
        ]);

        $this->ensurePropertyIsAccessible((int) $validated['property_id']);
        $this->ensureOwnersAreAccessible([(int) $validated['user_id']]);

        $actorId = Auth::id();
        $ownerGroup->update([
            'property_id' => $validated['property_id'],
            'purchased_date' => $validated['purchased_date'],
            'sold_date' => $validated['sold_date'] ?? null,
            'archived_date' => $validated['archived_date'] ?? null,
            'status' => $validated['status'],
            'updated_by' => $actorId,
        ]);

        OwnerGroupUser::where('owner_group_id', $ownerGroup->id)->forceDelete();
        OwnerGroupUser::create([
            'owner_group_id' => $ownerGroup->id,
            'user_id' => $validated['user_id'],
            'is_main' => 1,
            'added_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        flash('Owner Group updated successfully!')->success();

        return redirect()->route('admin.owner-groups.index');
    }


    public function deleteGroup($id)
    {
        // Get the current logged-in user
        $userId = Auth::id();

        // Find the OwnerGroup record by ID or fail if not found
        $ownerGroup = $this->ownerGroupsForCurrentAccount()->findOrFail($id);

        // Soft delete the related OwnerGroupUser records (soft delete instead of permanent delete)
        foreach ($ownerGroup->ownerGroupUsers as $user) {
            // Soft delete each associated user
            $user->deleted_by = $userId; // Log who deleted the user
            $user->save(); // Save to update the deleted_by field
            $user->delete(); // Soft delete the user
        }

        // Soft delete the OwnerGroup itself
        $ownerGroup->deleted_by = $userId; // Log who deleted the OwnerGroup
        $ownerGroup->save(); // Save to update the deleted_by field
        $ownerGroup->delete(); // Soft delete the OwnerGroup

        // Flash a success message
        flash("Owner Group and associated users deleted successfully!")->success();

        // Redirect back to the previous page (or any other page as needed)
        return back();
    }



    public function updateMain(Request $request, $id)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'owner_group_id' => 'required|integer',
        ]);

        // Find the OwnerGroup based on the provided ID (use $id from the route)
        $ownerGroup = $this->ownerGroupsForCurrentAccount()->find($id);

        if (!$ownerGroup) {
            return response()->json([
                'status' => false,
                'notification' => 'Owner Group not found.',
            ]);
        }

        $actorId = Auth::id();
        $selectedOwnerId = (int) $validated['user_id'];
        $ownerGroupId = (int) $validated['owner_group_id'];

        // Check if the owner_group_id from the form matches the one in the URL (for additional safety)
        if ($ownerGroupId != $ownerGroup->id) {
            return response()->json([
                'status' => false,
                'notification' => 'Owner Group ID mismatch.',
            ]);
        }

        $selectedOwnerExists = OwnerGroupUser::where('owner_group_id', $ownerGroup->id)
            ->where('user_id', $selectedOwnerId)
            ->exists();

        if (! $selectedOwnerExists) {
            return response()->json([
                'status' => false,
                'notification' => 'The selected owner does not belong to this owner group.',
            ], 422);
        }

        // Reset all other users in this owner group to not be main
        $updateMain = OwnerGroupUser::where('owner_group_id', $ownerGroup->id)
            ->update([
                'is_main' => 0,
                'updated_by' => $actorId,
            ]);

        if ($updateMain === false) {
            return response()->json([
                'status' => false,
                'notification' => 'Failed to reset other users to non-main.',
            ]);
        }

        // Set the selected user as the main user
        $userUpdated = OwnerGroupUser::where('owner_group_id', $ownerGroup->id)
            ->where('user_id', $selectedOwnerId)
            ->update([
                'is_main' => 1,
                'updated_by' => $actorId,
            ]);

        // Check if the user was successfully updated
        if ($userUpdated) {
            return response()->json([
                'status' => true,
                'notification' => 'Owner Group Main user updated successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'notification' => 'Failed to update the main user!',
            ]);
        }
    }

    private function ownersForCurrentAccount()
    {
        return User::role('Owner')
            ->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($query) => $query->forAccount(current_account_id())
            )
            ->orderBy('name');
    }

    private function ownersForForm()
    {
        $owners = $this->ownersForCurrentAccount()->get();
        $actor = auth()->user();

        if ($actor && ! $owners->contains(fn ($owner) => (int) $owner->id === (int) $actor->id)) {
            $owners = $owners->prepend($actor);
        }

        return $owners;
    }

    private function propertiesForCurrentAccount()
    {
        return Property::query()
            ->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($query) => $query->forAccount(current_account_id())
            );
    }

    private function ownerGroupsForCurrentAccount()
    {
        return OwnerGroup::query()
            ->when(
                ! auth()->user()?->hasRole('Super Admin'),
                fn ($query) => $query->whereHas(
                    'property',
                    fn ($propertyQuery) => $propertyQuery->forAccount(current_account_id())
                )
            );
    }

    private function ensurePropertyIsAccessible(int $propertyId): Property
    {
        $property = Property::findOrFail($propertyId);
        ensureModelBelongsToCurrentAccount($property);

        return $property;
    }

    private function ensureOwnersAreAccessible(array $ownerIds): void
    {
        if (auth()->user()?->hasRole('Super Admin')) {
            return;
        }

        $ownerIds = collect($ownerIds)
            ->map(fn ($ownerId) => (int) $ownerId)
            ->unique()
            ->values();

        $accessibleOwnerIds = $this->ownersForCurrentAccount()
            ->whereKey($ownerIds)
            ->pluck('users.id')
            ->map(fn ($ownerId) => (int) $ownerId);

        $actorId = (int) Auth::id();
        if ($actorId && $ownerIds->contains($actorId)) {
            $accessibleOwnerIds = $accessibleOwnerIds->push($actorId)->unique();
        }

        if ($ownerIds->diff($accessibleOwnerIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'user_id' => ['One or more selected owners do not belong to your subscriber account.'],
            ]);
        }
    }



}
