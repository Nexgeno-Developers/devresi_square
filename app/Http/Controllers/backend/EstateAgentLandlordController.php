<?php

namespace App\Http\Controllers\Backend;

use App\Mail\MailManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EstateAgentLandlordController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view landlord contacts')->only('index');
        $this->middleware('permission:add landlord contacts')->only(['create', 'store']);
        $this->middleware('permission:edit landlord contacts')->only(['edit', 'update']);
        $this->middleware('permission:delete landlord contacts')->only('destroy');
        $this->middleware('permission:manage landlord login')->only('toggleLogin');
    }

    public function index(Request $request)
    {
        $agentId   = Auth::id();
        $companyId = Auth::user()->company_id ?? Auth::user()->ownedCompany?->id;

        $query = User::role('Landlord')
            ->where(function ($q) use ($agentId, $companyId) {
                $q->where('managed_by_user_id', $agentId)
                  ->orWhere(function ($q2) use ($companyId) {
                      $q2->where('company_id', $companyId)
                         ->where('user_source', 'estate_agent_added');
                  });
            });

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name',  'like', "%{$s}%")
                  ->orWhere('email',      'like', "%{$s}%")
                  ->orWhere('phone',      'like', "%{$s}%");
            });
        }

        $landlords = $query->orderByDesc('id')->paginate(15);
        return view('backend.estate_agent.landlords.index', compact('landlords'));
    }

    public function create()
    {
        return view('backend.estate_agent.landlords.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string|max:20',
            'give_login' => 'nullable|boolean',
        ]);

        $agent     = Auth::user();
        $companyId = $agent->company_id ?? $agent->ownedCompany?->id;
        $giveLogin = $request->boolean('give_login');
        $password  = Str::random(12);

        DB::beginTransaction();
        try {
            $landlord = User::create([
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'user_type'          => 'landlord',
                'user_source'        => 'estate_agent_added',
                'managed_by_user_id' => $agent->id,
                'company_id'         => $companyId,
                'can_login'          => $giveLogin ? 1 : 0,
                'status'             => 1,
                'password'           => Hash::make($password),
            ]);

            $landlord->assignRole('Landlord');
            DB::commit();

            if ($giveLogin) {
                $this->sendWelcomeEmail($landlord, $password);
                flash("Landlord added and credentials sent to {$landlord->email}.")->success();
            } else {
                flash('Landlord contact added successfully.')->success();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Landlord creation failed', ['error' => $e->getMessage()]);
            flash('Failed to add landlord: ' . $e->getMessage())->error();
        }

        return redirect()->route('estate_agent.landlords.index');
    }

    public function edit($id)
    {
        $landlord = $this->findOwnedLandlord($id);
        return view('backend.estate_agent.landlords.edit', compact('landlord'));
    }

    public function update(Request $request, $id)
    {
        $landlord = $this->findOwnedLandlord($id);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => "required|email|unique:users,email,{$landlord->id}",
            'phone'      => 'nullable|string|max:20',
        ]);

        $landlord->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
        ]);

        flash('Landlord contact updated.')->success();
        return redirect()->route('estate_agent.landlords.index');
    }

    public function destroy($id)
    {
        $landlord = $this->findOwnedLandlord($id);
        $landlord->delete();
        flash('Landlord contact removed.')->success();
        return redirect()->route('estate_agent.landlords.index');
    }

    public function toggleLogin($id)
    {
        $landlord = $this->findOwnedLandlord($id);
        $newState = !$landlord->can_login;
        $landlord->update(['can_login' => $newState]);

        if ($newState) {
            $password = Str::random(12);
            $landlord->update(['password' => Hash::make($password)]);
            $this->sendWelcomeEmail($landlord, $password);
            flash("Login enabled. Credentials sent to {$landlord->email}.")->success();
        } else {
            DB::table('sessions')->where('user_id', $landlord->id)->delete();
            flash('Login access disabled. The landlord has been logged out immediately.')->success();
        }

        return back();
    }

    private function findOwnedLandlord(int $id): User
    {
        $agentId   = Auth::id();
        $companyId = Auth::user()->company_id ?? Auth::user()->ownedCompany?->id;

        return User::role('Landlord')
            ->where('id', $id)
            ->where(function ($q) use ($agentId, $companyId) {
                $q->where('managed_by_user_id', $agentId)
                  ->orWhere(function ($q2) use ($companyId) {
                      $q2->where('company_id', $companyId)
                         ->where('user_source', 'estate_agent_added');
                  });
            })
            ->firstOrFail();
    }

    private function sendWelcomeEmail(User $landlord, string $password): void
    {
        try {
            $appName  = config('app.name');
            $loginUrl = url('/login');
            $content  = "
                <p>Hi {$landlord->first_name},</p>
                <p>Your account on <strong>{$appName}</strong> has been set up by your estate agent.</p>
                <table style='border-collapse:collapse;margin:16px 0;'>
                    <tr>
                        <td style='padding:6px 12px;font-weight:bold;background:#f8f9fa;border:1px solid #dee2e6;'>Email</td>
                        <td style='padding:6px 12px;border:1px solid #dee2e6;'>{$landlord->email}</td>
                    </tr>
                    <tr>
                        <td style='padding:6px 12px;font-weight:bold;background:#f8f9fa;border:1px solid #dee2e6;'>Password</td>
                        <td style='padding:6px 12px;border:1px solid #dee2e6;'><strong>{$password}</strong></td>
                    </tr>
                </table>
                <p style='text-align:center;margin:24px 0;'>
                    <a href='{$loginUrl}' style='background:#0b60bd;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;'>
                        Login to Your Account
                    </a>
                </p>
                <p style='color:#dc3545;font-size:13px;'>Please change your password after first login.</p>
            ";
            Mail::to($landlord->email)->send(new MailManager([
                'subject'     => "Your {$appName} account is ready",
                'content'     => $content,
                'attachments' => [],
            ]));
        } catch (\Exception $e) {
            Log::error("Failed to send landlord welcome email: {$e->getMessage()}");
        }
    }
}
