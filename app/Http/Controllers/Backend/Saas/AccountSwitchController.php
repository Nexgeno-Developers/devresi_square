<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Services\Saas\CurrentAccountService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountSwitchController extends Controller
{
    private const IMPERSONATOR_SESSION_KEY = 'impersonator_user_id';

    public function switch(Request $request, CurrentAccountService $service)
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer'],
        ]);

        try {
            $account = $service->setCurrentAccount($request->user(), (int) $data['account_id']);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        flash("Current account switched to {$account->account_name}.")->success();

        return back();
    }

    public function loginAs(Request $request, Account $account, CurrentAccountService $service)
    {
        $superAdmin = $request->user();
        abort_unless($superAdmin?->isSuperAdmin(), 403);

        $owner = $account->owner;
        if (! $owner) {
            flash('This account does not have an owner user to log in as.')->error();

            return back();
        }

        if (! $owner->can_login || ! $owner->status) {
            flash('The account owner is not currently allowed to log in.')->error();

            return back();
        }

        $service->setCurrentAccount($superAdmin, $account->id);
        $request->session()->put(self::IMPERSONATOR_SESSION_KEY, $superAdmin->id);

        Auth::login($owner);
        $request->session()->regenerate();

        flash("You are now logged in as {$account->account_name}.")->success();

        return redirect()->route('backend.dashboard');
    }

    public function leave(Request $request, CurrentAccountService $service)
    {
        $superAdminId = $request->session()->pull(self::IMPERSONATOR_SESSION_KEY);
        $superAdmin = $superAdminId ? User::find($superAdminId) : null;

        abort_unless($superAdmin?->isSuperAdmin(), 403, 'The Super Admin session is no longer available.');

        Auth::login($superAdmin);
        $request->session()->regenerate();
        $service->clearCurrentAccount();

        flash('Returned to the Super Admin dashboard.')->success();

        return redirect()->route('backend.dashboard');
    }
}
