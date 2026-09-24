<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use App\Support\PlatformRoles;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use EnforcesSaasPlanLimits;

    public function __construct()
    {
        // Launch Step 10: every role mutation requires the matching permission.
        $this->middleware(['permission:view staff roles'])->only('index');
        $this->middleware(['permission:add staff role'])->only(['create', 'store']);
        $this->middleware(['permission:edit staff role'])->only(['edit', 'update']);
        $this->middleware(['permission:delete staff role'])->only('destroy');
        // Creating global permission names is platform-only.
        $this->middleware(['role:Super Admin'])->only('add_permission');
    }

    public function index()
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $roles = Role::query()
            ->whereNotIn('name', PlatformRoles::PROTECTED_NAMES)
            ->orderBy('name')
            ->paginate(10);

        return view('backend.staff.staff_roles.index', compact('roles'));
    }

    public function create()
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $permissions = Permission::query()->orderBy('name')->get();

        return view('backend.staff.staff_roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $request->validate([
            'name' => 'required|string|max:125',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        PlatformRoles::assertMutable($request->name);

        $request->validate([
            'name' => 'unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        $perms = Permission::whereIn('id', $request->permissions)->get();
        $role->syncPermissions($perms);

        flash('New role has been added successfully')->success();

        return redirect()->route('roles.index');
    }

    public function edit($id)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $role = Role::findOrFail($id);
        PlatformRoles::assertMutable($role->name);

        $permissions = Permission::query()->orderBy('name')->get();
        $rolePerms = $role->permissions->pluck('id')->toArray();

        return view('backend.staff.staff_roles.edit', compact('role', 'permissions', 'rolePerms'));
    }

    public function update(Request $request, $id)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $role = Role::findOrFail($id);
        PlatformRoles::assertMutable($role->name);

        $request->validate([
            'name' => "required|string|unique:roles,name,{$id}|max:125",
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        PlatformRoles::assertMutable($request->name);

        $role->update(['name' => $request->name]);

        $perms = Permission::whereIn('id', $request->permissions)->get();
        $role->syncPermissions($perms);

        flash('Role has been updated successfully')->success();

        return redirect()->route('roles.index');
    }

    public function destroy($id)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        if (env('DEMO_MODE') == 'On') {
            flash('Data can not change in demo mode.')->info();

            return back();
        }

        $role = Role::findOrFail($id);
        PlatformRoles::assertMutable($role->name);

        $role->delete();
        flash('Role has been deleted successfully')->success();

        return redirect()->route('roles.index');
    }

    public function add_permission(Request $request)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name|max:125',
        ]);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        cache()->forget('all_permissions');

        flash('Permission created.')->success();

        return redirect()->route('roles.index');
    }

    public function create_admin_permissions()
    {
    }
}
