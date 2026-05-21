<?php

namespace App\Http\Controllers\Backend;

use Hash;
// use App\Models\Role;
use App\Models\User;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Designation;
use App\Models\StaffContact;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $staffs = Staff::with('user.designation')
            ->with('branch')
            ->whereHas('user', fn($query) => $query->where('user_type', 'staff'))
            ->paginate(10);

        return view('backend.staff.staffs.index', compact('staffs'));
    }

    public function create()
    {
        $designations = Designation::with('permissions')->orderBy('title')->get();
        $permissions = Permission::orderBy('name')->get();
        $branches = $this->branchOptionsFor(auth()->user());

        return view('backend.staff.staffs.create', compact('designations', 'permissions', 'branches'));
    }

    // Staff permissions are inherited live from the selected designation.
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title'          => 'required|string|max:255',
                'first_name'     => 'required|string|max:255',
                'middle_name'    => 'nullable|string|max:255',
                'last_name'      => 'required|string|max:255',
                'email'          => 'required|email|unique:users,email',
                'phone'          => 'nullable|string|max:20',
                'password'       => 'required',
                'designation_id' => 'required|exists:designations,id',
                'branch_id'      => 'nullable|exists:branches,id',
                'profile_picture' => 'nullable|image|max:2048',
                'custom_permissions' => 'nullable|array',
                'custom_permissions.*' => 'integer|exists:permissions,id',
                // multiple emails & phones
                'extra_emails'   => 'nullable|array',
                'extra_emails.*' => 'nullable|email|max:255',
                'extra_phones'   => 'nullable|array',
                'extra_phones.*' => 'nullable|string|max:20',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $profilePicture = $request->hasFile('profile_picture')
                ? $request->file('profile_picture')->store('profile_pictures', 'public')
                : null;

            $user = User::create([
                'title'          => $data['title'],
                'first_name'     => $data['first_name'],
                'middle_name'    => $data['middle_name'] ?? null,
                'last_name'      => $data['last_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'user_type'      => 'staff',
                'designation_id' => $data['designation_id'],
                'branch_id'      => $data['branch_id'] ?? null,
                'profile_picture' => $profilePicture,
                'password'       => Hash::make($data['password']),
            ]);

            Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
            $user->syncRoles(['Staff']);

            $staff = Staff::create([
                'user_id' => $user->id,
                'branch_id' => $data['branch_id'] ?? null,
                'permissions_customized' => false,
            ]);

            $this->syncStaffPermissionOverride($user, $staff, (int) $data['designation_id'], $data['custom_permissions'] ?? []);

            // Save extra emails
            foreach (($data['extra_emails'] ?? []) as $email) {
                if (!empty($email)) {
                    StaffContact::create([
                        'staff_id' => $staff->id,
                        'type'     => 'email',
                        'value'    => $email,
                    ]);
                }
            }

            // Save extra phones
            foreach (($data['extra_phones'] ?? []) as $phone) {
                if (!empty($phone)) {
                    StaffContact::create([
                        'staff_id' => $staff->id,
                        'type'     => 'phone',
                        'value'    => $phone,
                    ]);
                }
            }

            DB::commit();
            flash()->success('Staff has been added successfully');
            return redirect()->route('staffs.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Staff creation failed', ['error' => $e->getMessage()]);
            flash()->error('Failed to add staff: ' . $e->getMessage());
            return back()->withInput();
        }
    }


    public function edit($id)
    {
        $staff = Staff::with(['user.designation.permissions', 'user.permissions', 'contacts'])->findOrFail(decrypt($id));
        $designations = Designation::with('permissions')->orderBy('title')->get();
        $permissions = Permission::orderBy('name')->get();
        $branches = $this->branchOptionsFor(auth()->user());
        $selectedPermissionIds = $staff->permissions_customized
            ? $staff->user->getDirectPermissions()->pluck('id')->toArray()
            : ($staff->user->designation?->permissions->pluck('id')->toArray() ?? []);

        return view('backend.staff.staffs.edit', compact('staff', 'designations', 'permissions', 'selectedPermissionIds', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $user  = $staff->user;

        try {
            $data = $request->validate([
                'title'          => 'required|string|max:255',
                'first_name'     => 'required|string|max:255',
                'middle_name'    => 'nullable|string|max:255',
                'last_name'      => 'required|string|max:255',
                'email'          => "required|email|unique:users,email,{$user->id}",
                'phone'          => 'nullable|string|max:20',
                'password'       => 'nullable|string|min:6',
                'designation_id' => 'required|exists:designations,id',
                'branch_id'      => 'nullable|exists:branches,id',
                'profile_picture' => 'nullable|image|max:2048',
                'custom_permissions' => 'nullable|array',
                'custom_permissions.*' => 'integer|exists:permissions,id',
                // multiple emails & phones
                'extra_emails'   => 'nullable|array',
                'extra_emails.*' => 'nullable|email|max:255',
                'extra_phones'   => 'nullable|array',
                'extra_phones.*' => 'nullable|string|max:20',
            ]);
        } catch (ValidationException $e) {
            flashValidationErrors($e);
            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Update user
            $user->title          = $data['title'];
            $user->first_name     = $data['first_name'];
            $user->middle_name    = $data['middle_name'] ?? null;
            $user->last_name      = $data['last_name'];
            $user->email          = $data['email'];
            $user->phone          = $data['phone'] ?? null;
            $user->designation_id = $data['designation_id'];
            $user->branch_id      = $data['branch_id'] ?? null;
            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $user->profile_picture = $request->file('profile_picture')->store('profile_pictures', 'public');
            }
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            $staff->branch_id = $data['branch_id'] ?? null;
            $staff->save();

            Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
            $user->syncRoles(['Staff']);
            $this->syncStaffPermissionOverride($user, $staff, (int) $data['designation_id'], $data['custom_permissions'] ?? []);

            // 2. Sync extra emails (delete all then re-insert)
            $staff->contacts()->delete();

            foreach (($data['extra_emails'] ?? []) as $email) {
                if (!empty($email)) {
                    StaffContact::create([
                        'staff_id' => $staff->id,
                        'type'     => 'email',
                        'value'    => $email,
                    ]);
                }
            }

            foreach (($data['extra_phones'] ?? []) as $phone) {
                if (!empty($phone)) {
                    StaffContact::create([
                        'staff_id' => $staff->id,
                        'type'     => 'phone',
                        'value'    => $phone,
                    ]);
                }
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

    private function syncStaffPermissionOverride(User $user, Staff $staff, int $designationId, array $permissionIds): void
    {
        $selectedPermissionIds = collect($permissionIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $designationPermissionIds = Designation::with('permissions:id')
            ->findOrFail($designationId)
            ->permissions
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $permissionsCustomized = $selectedPermissionIds !== $designationPermissionIds;

        $user->syncPermissions(
            $permissionsCustomized
                ? Permission::whereIn('id', $selectedPermissionIds)->get()
                : []
        );

        $staff->permissions_customized = $permissionsCustomized;
        $staff->save();
    }

    private function branchOptionsFor(User $user)
    {
        if ($user->ownedCompany) {
            return $user->ownedCompany->branches()->orderBy('name')->get();
        }

        if ($user->company_id) {
            return Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }

        return Branch::orderBy('name')->get();
    }
}
