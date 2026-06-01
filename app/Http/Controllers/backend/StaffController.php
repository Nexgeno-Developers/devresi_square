<?php

namespace App\Http\Controllers\Backend;

use Hash;
// use App\Models\Role;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view all staffs'])->only('index');
        $this->middleware(['permission:add staff'])->only('create');
        $this->middleware(['permission:edit staff'])->only('edit');
        $this->middleware(['permission:delete staff'])->only('destroy');
    }

    public function index()
    {
        $staffs = Staff::paginate(10);
        return view('backend.staff.staffs.index', compact('staffs'));
    }

    public function create()
    {
        $roles = Role::with('permissions')->where('id', '!=', 1)->orderBy('id', 'asc')->get();
        $permissions = Permission::orderBy('name')->get();
        return view('backend.staff.staffs.create', compact('roles', 'permissions'));
        
        // $roles = Role::where('id','!=',1)->orderBy('id', 'desc')->get();
        // return view('backend.staff.staffs.create', compact('roles'));
    }

    // used to create staff with single role and additional permissions
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title'     => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                // 'name'      => 'required|string|max:255',
                'email'     => 'required|email|unique:users,email',
                'password'  => 'required',
                'role_id'   => 'required|exists:roles,id',
                'enable_additional_permissions' => 'nullable|in:on',
                'additional_permissions'   => 'nullable|array',
                'additional_permissions.*' => 'exists:permissions,name',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'title'     => $data['title'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'last_name'   => $data['last_name'],
                // 'name'      => $data['name'],
                'email'     => $data['email'],
                'user_type' => 'staff',
                'password'  => Hash::make($data['password']),
            ]);

            $roleId = $data['role_id'];
            $role = Role::with('permissions')->findOrFail($roleId);
            DB::table('model_has_roles')->insert([
                'role_id'     => $roleId,
                'model_type'  => get_class($user), // Will be 'App\Models\User'
                'model_id'    => $user->id,
            ]);
            
            // Handle additional permissions if checkbox is enabled
            if (isset($data['enable_additional_permissions']) && !empty($data['additional_permissions'])) {
                $inheritedPermissions = $role->permissions->pluck('name')->toArray();

                $filtered = collect($data['additional_permissions'])
                    ->diff($inheritedPermissions);

                if ($filtered->isNotEmpty()) {
                    $user->givePermissionTo($filtered);
                }
            }

            Staff::create([
                'user_id' => $user->id,
                'role_id' => $roleId,
            ]);

            DB::commit();
            flash()->success('Staff has been added successfully');
            return redirect()->route('staffs.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Staff creation failed', ['error' => $e->getMessage()]);

            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to add staff: ' . $e->getMessage()
                ], 500);
            }

            flash()->error('Failed to add staff: ' . $e->getMessage());
            return back()->withInput();
        }        
    }


    /*
    // This method is for single role to staff
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'      => 'required|string|max:255',
                'email'     => 'required|email|unique:users,email',
                'password'  => 'required',
                'role_id'   => 'required|exists:roles,id',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }

        // 2) Wrap in transaction
        DB::beginTransaction();
        try {
            // 3) Create User
            $user = User::create([
                'name'           => $data['name'],
                'email'          => $data['email'],
                // 'phone'          => $data['mobile'] ?? null,
                'user_type'      => 'staff',
                'password'       => Hash::make($data['password']),
            ]);

            // 4) Assign Spatie role
            $roleName = Role::findOrFail($data['role_id'])->name;
            $user->assignRole($roleName);

            // 5) Create Staff record
            Staff::create([
                'user_id' => $user->id,
                'role_id' => $data['role_id'],
            ]);

            DB::commit();

            flash()->success('Staff has been added successfully');
            return redirect()->route('staffs.index');
        }
        catch (\Throwable $e) {
            DB::rollBack();
            flash()->error('Failed to add staff: '.$e->getMessage());
            return back();
        }
    }*/

    // multiple roles and additional permissions
    /*public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'      => 'required|string|max:255',
                'email'     => 'required|email|unique:users,email',
                'password'  => 'required',
                'role_id'   => 'required|array',
                'role_id.*' => 'exists:roles,id',
                'additional_permissions'   => 'nullable|array',
                'additional_permissions.*' => 'exists:permissions,name',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Create the user
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'user_type' => 'staff',
                'password'  => Hash::make($data['password']),
            ]);

            // 2. Assign multiple roles
            $roles = Role::whereIn('id', $data['role_id'])->get();
            $user->syncRoles($roles);

            // 3. Collect permissions from roles to exclude from additional ones
            $inheritedPermissions = $roles->flatMap(function ($role) {
                return $role->permissions->pluck('name');
            })->unique();

            // 4. Filter additional permissions that are NOT inherited
            $additional = collect($data['additional_permissions'] ?? [])
                ->diff($inheritedPermissions);

            if ($additional->isNotEmpty()) {
                $user->givePermissionTo($additional);
            }

            // 5. Create Staff record
            Staff::create([
                'user_id' => $user->id,
                'role_id' => $roles->first()->id, // optional: store one role or change schema to allow multiple
            ]);

            DB::commit();
            flash()->success('Staff has been added successfully');
            return redirect()->route('staffs.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            flash()->error('Failed to add staff: ' . $e->getMessage());
            return back()->withInput();
        }
    }*/

    public function edit($id)
    {
        $staff = Staff::with(['role.permissions', 'user.permissions'])->findOrFail(decrypt($id));
        $roles = Role::with('permissions')->where('id', '!=', 1)->orderBy('id', 'desc')->get();
        // $permissions = Permission::orderBy('name')->get();
        // $userPermissions = $staff->user->getDirectPermissions()->pluck('name')->toArray();
        $permissions = Permission::all();
        $userPermissions = $staff->user->permissions->pluck('name')->toArray();

        return view('backend.staff.staffs.edit', compact('staff', 'roles', 'permissions', 'userPermissions'));
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $user  = $staff->user;

        try {
            $data = $request->validate([
                'title'     => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                // 'name'      => 'required|string|max:255',
                'email'     => "required|email|unique:users,email,{$user->id}",
                'password'  => 'nullable|string|min:6',
                'role_id'   => 'required|exists:roles,id',
                'enable_additional_permissions' => 'nullable|in:on',
                'additional_permissions'   => 'nullable|array',
                'additional_permissions.*' => 'exists:permissions,name',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Update user
            $user->title = $data['title'];
            $user->first_name = $data['first_name'];
            $user->middle_name = $data['middle_name'];
            $user->last_name = $data['last_name'];
            // $user->name = $data['name'];
            $user->email = $data['email'];
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            // 2. Update staff role_id
            $staff->role_id = $data['role_id'];
            $staff->save();

            // 3. Delete previous role mapping
            DB::table('model_has_roles')->where([
                ['model_id', '=', $user->id],
                ['model_type', '=', get_class($user)]
            ])->delete();

            // 4. Insert new role
            $role = Role::with('permissions')->findOrFail($data['role_id']);
            DB::table('model_has_roles')->insert([
                'role_id'     => $data['role_id'],
                'model_type'  => get_class($user),
                'model_id'    => $user->id,
            ]);

            // 5. Handle additional permissions
            if (isset($data['enable_additional_permissions'])) {
                $inheritedPermissions = $role->permissions->pluck('name')->toArray();
                $additional = collect($data['additional_permissions'] ?? [])->diff($inheritedPermissions);
                $user->syncPermissions($additional);
            } else {
                $user->syncPermissions([]); // Remove all direct permissions
            }

            DB::commit();
            flash()->success('Staff has been updated successfully');
            return redirect()->route('staffs.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            flash()->error('Failed to update staff: ' . $e->getMessage());
            return back()->withInput();
        }
    }



    /*public function edit($id)
    {
        $staff = Staff::findOrFail(decrypt($id));
        $roles = $roles = Role::where('id','!=',1)->orderBy('id', 'desc')->get();
        return view('backend.staff.staffs.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, $id)
    {
        // 1) Retrieve staff
        $staff = Staff::findOrFail($id);

        // 2) Validate inputs and flash errors with AIZ notify
        try {
            $data = $request->validate([
                'name'      => 'required|string|max:255',
                'email'     => "required|email|unique:users,email,{$staff->user->id}",
                // 'mobile' => 'nullable|string|max:20',
                'password'  => 'nullable',
                'role_id'   => 'required|exists:roles,id',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }
        // 3) Wrap in transaction
        DB::beginTransaction();
        try {
            $user  = $staff->user;

            // 4) Update User fields
            $user->name  = $data['name'];
            $user->email = $data['email'];
            // $user->phone = $data['mobile'] ?? null;

            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            // 5) Update Staff.role_id
            $staff->role_id = $data['role_id'];
            $staff->save();

            // 6) Sync Spatie roles
            $roleName = Role::findOrFail($data['role_id'])->name;
            $user->syncRoles($roleName);

            DB::commit();

            flash()->success('Staff has been updated successfully');
            return redirect()->route('staffs.index');
        }
        catch (\Throwable $e) {
            DB::rollBack();
            flash()->error('Failed to update staff: '.$e->getMessage());
            return back();
        }
    }*/

    public function destroy($id)
    {
        User::destroy(Staff::findOrFail($id)->user->id);
        if(Staff::destroy($id)){
            flash('Staff has been deleted successfully')->success();
            return redirect()->route('staffs.index');
        }
        flash()->error('Something went wrong');
        return back();
    }
}
