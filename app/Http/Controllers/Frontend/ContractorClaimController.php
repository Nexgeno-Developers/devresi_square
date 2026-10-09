<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContractorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** One-time "claim your new account" link for contractors from the old module. */
class ContractorClaimController extends Controller
{
    public function show(User $user)
    {
        $profile = $this->legacyProfile($user);

        return view('frontend.contractor.claim', ['user' => $user, 'profile' => $profile]);
    }

    public function store(Request $request, User $user)
    {
        $profile = $this->legacyProfile($user);
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], ['terms.accepted' => 'Please accept the terms and privacy notice.']);

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        if (! $user->hasRole('Contractor')) {
            $user->assignRole('Contractor');
        }
        $profile->forceFill([
            'terms_accepted_at' => now(),
            'privacy_version' => config('contractor_marketplace.privacy_version'),
        ])->save();
        $profile->log('claimed', $user, null, 'Claimed account from the old contractor module.');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('contractor.profile.edit', ['step' => 'business'])
            ->with('success', 'Welcome. Add your business basics and documents to start getting jobs.');
    }

    private function legacyProfile(User $user): ContractorProfile
    {
        $profile = $user->contractorProfile;
        abort_unless($profile && $profile->legacy_contractor && ! $profile->terms_accepted_at, 404);

        return $profile;
    }
}
