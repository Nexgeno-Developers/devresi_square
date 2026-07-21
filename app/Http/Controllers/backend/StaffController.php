<?php

namespace App\Http\Controllers\Backend;

use Hash;
// use App\Models\Role;
use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use App\Models\AccountUser;
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
    use EnforcesSaasPlanLimits;

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
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->whereHas('user', fn($query) => $query->where('user_type', 'staff'))
            ->paginate(10);

        return view('backend.staff.staffs.index', compact('staffs'));
    }

    public function create()
    {
        if ($response = $this->redirectIfSaasLimitDenied('staff', 'staffs.index')) {
            return $response;
        }

        $designations = Designation::with('permissions')
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->orderBy('title')
            ->get();
        $permissions = Permission::orderBy('name')->get();
        $branches = $this->branchOptionsFor(auth()->user());
        $canCustomizePermissions = $this->canCustomizeStaffPermissions();

        return view('backend.staff.staffs.create', compact('designations', 'permissions', 'branches', 'canCustomizePermissions'));
    }

    // Staff permissions are inherited live from the selected designation.
    public function store(Request $request)
    {
        if ($response = $this->backIfSaasLimitDenied('staff')) {
            return $response;
        }

        if (! $this->canCustomizeStaffPermissions() && ($request->filled('custom_permissions') || $request->filled('custom_permissions_submitted'))) {
            flash('Your current plan does not include staff roles and permissions.')->error();

            return back()->withInput();
        }

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
            $accountId = current_account_id();
            if (! auth()->user()?->hasRole('Super Admin') && ! $accountId) {
                abort(403, 'No active SaaS account found for this user.');
            }

            if (! empty($data['branch_id'])) {
                $branch = Branch::findOrFail($data['branch_id']);
                ensureModelBelongsToCurrentAccount($branch);
            }

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
                'account_id' => $accountId,
                'user_id' => $user->id,
                'branch_id' => $data['branch_id'] ?? null,
                'permissions_customized' => false,
            ]);

            if ($accountId) {
                AccountUser::updateOrCreate(
                    [
                        'account_id' => $accountId,
                        'user_id' => $user->id,
                    ],
                    [
                        'member_type' => 'staff',
                        'access_level' => 'edit',
                        'can_login' => true,
                        'branch_id' => $data['branch_id'] ?? null,
                        'designation_id' => $data['designation_id'],
                        'status' => 'active',
                        'created_by' => auth()->id(),
                    ]
                );
            }

            if ($this->canCustomizeStaffPermissions()) {
                $this->syncStaffPermissionOverride($user, $staff, (int) $data['designation_id'], $data['custom_permissions'] ?? []);
            }

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
        ensureModelBelongsToCurrentAccount($staff);

        $designations = Designation::with('permissions')
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->orderBy('title')
            ->get();
        $permissions = Permission::orderBy('name')->get();
        $branches = $this->branchOptionsFor(auth()->user());
        $canCustomizePermissions = $this->canCustomizeStaffPermissions();
        $selectedPermissionIds = $staff->permissions_customized
            ? $staff->user->getDirectPermissions()->pluck('id')->toArray()
            : ($staff->user->designation?->permissions->pluck('id')->toArray() ?? []);

        return view('backend.staff.staffs.edit', compact('staff', 'designations', 'permissions', 'selectedPermissionIds', 'branches', 'canCustomizePermissions'));
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        ensureModelBelongsToCurrentAccount($staff);

        if (! $this->canCustomizeStaffPermissions() && ($request->filled('custom_permissions') || $request->filled('custom_permissions_submitted'))) {
            flash('Your current plan does not include staff roles and permissions.')->error();

            return back()->withInput();
        }

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
            $accountId = $staff->account_id ?: current_account_id();

            if (! empty($data['branch_id'])) {
                $branch = Branch::findOrFail($data['branch_id']);
                ensureModelBelongsToCurrentAccount($branch);
            }

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
            $staff->account_id = $accountId;
            $staff->save();

            if ($accountId) {
                AccountUser::updateOrCreate(
                    [
                        'account_id' => $accountId,
                        'user_id' => $user->id,
                    ],
                    [
                        'member_type' => 'staff',
                        'access_level' => 'edit',
                        'can_login' => true,
                        'branch_id' => $data['branch_id'] ?? null,
                        'designation_id' => $data['designation_id'],
                        'status' => 'active',
                        'created_by' => auth()->id(),
                    ]
                );
            }

            Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
            $user->syncRoles(['Staff']);
            if ($this->canCustomizeStaffPermissions()) {
                $this->syncStaffPermissionOverride($user, $staff, (int) $data['designation_id'], $data['custom_permissions'] ?? []);
            } else {
                $staff->permissions_customized = false;
                $staff->save();
                $user->syncPermissions([]);
            }

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
        $staff = Staff::findOrFail($id);
        ensureModelBelongsToCurrentAccount($staff);

        User::destroy($staff->user->id);
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
        if (! $user->hasRole('Super Admin')) {
            return Branch::forAccount(current_account_id())->orderBy('name')->get();
        }

        if ($user->ownedCompany) {
            return $user->ownedCompany->branches()->orderBy('name')->get();
        }

        if ($user->company_id) {
            return Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }

        return Branch::orderBy('name')->get();
    }

    private function canCustomizeStaffPermissions(): bool
    {
        return $this->saasLimitError('roles_permissions') === null;
    }
}
