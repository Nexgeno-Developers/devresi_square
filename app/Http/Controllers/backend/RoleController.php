<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view staff roles'])->only('index');
        $this->middleware(['permission:add staff role'])->only('create');
        $this->middleware(['permission:edit staff role'])->only('edit');
        $this->middleware(['permission:delete staff role'])->only('destroy');
    }


    public function index()
    {
        $roles = Role::where('id', '!=', 1)->paginate(10);
        return view('backend.staff.staff_roles.index', compact('roles'));

        // $roles = Role::paginate(10);
        // return view('backend.staff.staff_roles.index', compact('roles'));
    }


    public function create()
    {
        $permissions = Permission::all();
        return view('backend.staff.staff_roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
{
    $request->validate([
        'name'          => 'required|string|unique:roles,name',
        'permissions'   => 'required|array',
        'permissions.*' => 'exists:permissions,id',
    ]);

    $role = Role::create([
        'name'       => $request->name,
        'guard_name' => 'web',
    ]);

    // Fetch the Permission models
    $perms = Permission::whereIn('id', $request->permissions)->get();

    // Sync by passing the collection of Permission models
    $role->syncPermissions($perms);

    flash('New role has been added successfully')->success();
    return redirect()->route('roles.index');
}


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function show($id)
    // {
    //     //
    // }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * //@return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role        = Role::findOrFail($id);
        $permissions = Permission::all();
        $rolePerms   = $role->permissions->pluck('id')->toArray();

        return view('backend.staff.staff_roles.edit', compact('role', 'permissions', 'rolePerms'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * //@return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $request->validate([
        'name'          => "required|string|unique:roles,name,{$id}",
        'permissions'   => 'required|array',
        'permissions.*' => 'exists:permissions,id',
    ]);

    $role = Role::findOrFail($id);
    $role->update(['name' => $request->name]);

    // Fetch the Permission models
    $perms = Permission::whereIn('id', $request->permissions)->get();

    // Sync them
    $role->syncPermissions($perms);

    flash('Role has been updated successfully')->success();
    return redirect()->route('roles.index');
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * //@return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(env('DEMO_MODE') == 'On'){
            flash('Data can not change in demo mode.')->info();
            return back();
        }

        Role::destroy($id);
        flash('Role has been deleted successfully')->success();
        return redirect()->route('roles.index');
    }

    public function add_permission(Request $request)
    {
        $permission = Permission::create(['name' => $request->name]);
        return redirect()->route('roles.index');
    }

    public function create_admin_permissions()
    {
    }
}
