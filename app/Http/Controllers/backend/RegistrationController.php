<?php

namespace App\Http\Controllers\Backend;

use App\Mail\MailManager;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RegistrationController extends Controller
{
    // ─── Role map: registration type → default role name ─────────────────────
    private const ROLE_MAP = [
        'landlord'     => 'Landlord',
        'owner'        => 'Owner',
        'estate_agent' => 'Estate Agent',
        'contractor'   => 'Contractor',
    ];

    // ─── Listing ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        // Only show registrations where OTP was verified (or already actioned)
        // Unverified (pending) registrations are NOT shown — they haven't completed verification
        $query = Registration::whereIn('status', ['verified', 'approved', 'rejected'])
                             ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name',  'like', "%{$s}%")
                  ->orWhere('email',      'like', "%{$s}%")
                  ->orWhere('phone',      'like', "%{$s}%");
            });
        }

        $registrations = $query->paginate(20);

        return view('backend.registrations.index', compact('registrations'));
    }

    // ─── Show / Edit single registration ─────────────────────────────────────
    public function show($id)
    {
        $registration = Registration::with('user.roles')->findOrFail($id);
        $permissions  = Permission::orderBy('name')->get()->groupBy('section');
        $roles        = Role::where('id', '!=', 1)->orderBy('name')->get();

        // Suggested role based on type
        $suggestedRole = self::ROLE_MAP[$registration->type] ?? 'User';

        // If already approved, load the user's direct permissions
        $userPermissions = $registration->user
            ? $registration->user->getDirectPermissions()->pluck('name')->toArray()
            : [];

        return view('backend.registrations.show',
            compact('registration', 'permissions', 'userPermissions', 'roles', 'suggestedRole'));
    }

    // ─── Approve ──────────────────────────────────────────────────────────────
    public function approve(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);

        if ($registration->status === 'approved') {
            flash('This registration is already approved.')->info();
            return back();
        }

        // Block approving unverified registrations
        if ($registration->status === 'pending' || is_null($registration->otp_verified_at)) {
            flash('Cannot approve — this registration has not completed OTP verification.')->error();
            return back();
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::findOrFail($request->role_id);

            // Generate a random password
            $plainPassword = Str::random(10);

            // Create the user
            $user = User::create([
                'first_name'        => $registration->first_name,
                'last_name'         => $registration->last_name,
                'email'             => $registration->email,
                'phone'             => $registration->phone,
                'user_type'         => $registration->type,
                'status'            => 1,
                'can_login'         => 1,
                'password'          => Hash::make($plainPassword),
                'email_verified_at' => $registration->otp_verified_at ?? now(),
            ]);

            // Assign role
            DB::table('model_has_roles')->insert([
                'role_id'    => $role->id,
                'model_type' => get_class($user),
                'model_id'   => $user->id,
            ]);

            // Update registration
            $registration->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'user_id'     => $user->id,
            ]);

            DB::commit();

            // Send welcome email with credentials
            $this->sendApprovalEmail($user, $registration, $plainPassword);

            flash('Registration approved. Welcome email with credentials sent.')->success();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Registration approval failed', ['error' => $e->getMessage()]);
            flash('Approval failed: ' . $e->getMessage())->error();
        }

        return redirect()->route('admin.registrations.show', $id);
    }

    // ─── Reject ───────────────────────────────────────────────────────────────
    public function reject($id)
    {
        $registration = Registration::findOrFail($id);

        if (in_array($registration->status, ['approved', 'rejected'])) {
            flash('Cannot reject this registration.')->info();
            return back();
        }

        $registration->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ]);

        // Send rejection email
        $this->sendRejectionEmail($registration);

        flash('Registration rejected. Notification email sent.')->success();
        return back();
    }

    // ─── Update permissions for an approved user ──────────────────────────────
    public function updatePermissions(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);

        if (!$registration->user) {
            flash('No user linked to this registration.')->error();
            return back();
        }

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $registration->user->syncPermissions($request->permissions ?? []);

        flash('Permissions updated successfully.')->success();
        return back();
    }

    // ─── Send welcome email with generated password ───────────────────────────
    private function sendApprovalEmail(User $user, Registration $registration, string $plainPassword): void
    {
        try {
            $name    = $user->first_name;
            $appName = config('app.name');
            $loginUrl = url('/login');

            $subject = "Welcome to {$appName} — Your Account is Ready";
            $content = "
                <p>Hi {$name},</p>
                <p>Great news! Your registration with <strong>{$appName}</strong> has been approved.</p>
                <p>Your account credentials are:</p>
                <table style='border-collapse:collapse; margin:16px 0;'>
                    <tr>
                        <td style='padding:6px 12px; font-weight:bold; background:#f8f9fa; border:1px solid #dee2e6;'>Email</td>
                        <td style='padding:6px 12px; border:1px solid #dee2e6;'>{$user->email}</td>
                    </tr>
                    <tr>
                        <td style='padding:6px 12px; font-weight:bold; background:#f8f9fa; border:1px solid #dee2e6;'>Password</td>
                        <td style='padding:6px 12px; border:1px solid #dee2e6;'><strong>{$plainPassword}</strong></td>
                    </tr>
                </table>
                <p style='text-align:center; margin:24px 0;'>
                    <a href='{$loginUrl}'
                       style='background:#0b60bd; color:#fff; padding:12px 28px;
                              border-radius:4px; text-decoration:none; font-size:15px;'>
                        Login to Your Account
                    </a>
                </p>
                <p style='color:#dc3545; font-size:13px;'>
                    <strong>Important:</strong> Please change your password after your first login.
                </p>
                <p>— The {$appName} Team</p>
            ";

            Mail::to($user->email)->send(new MailManager([
                'subject'     => $subject,
                'content'     => $content,
                'attachments' => [],
            ]));

            Log::info("Approval email sent to {$user->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send approval email: {$e->getMessage()}");
        }
    }

    // ─── Send rejection email ─────────────────────────────────────────────────
    private function sendRejectionEmail(Registration $registration): void
    {
        try {
            $name    = $registration->first_name;
            $appName = config('app.name');

            $subject = "Your {$appName} Registration";
            $content = "
                <p>Hi {$name},</p>
                <p>Thank you for your interest in <strong>{$appName}</strong>.</p>
                <p>After reviewing your registration, we are unable to approve your account at this time.</p>
                <p>If you believe this is a mistake or would like more information, please contact us.</p>
                <p>— The {$appName} Team</p>
            ";

            Mail::to($registration->email)->send(new MailManager([
                'subject'     => $subject,
                'content'     => $content,
                'attachments' => [],
            ]));

            Log::info("Rejection email sent to {$registration->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send rejection email: {$e->getMessage()}");
        }
    }
}
