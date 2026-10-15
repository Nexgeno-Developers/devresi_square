<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContractorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** One-time claim link for contractors from the old module. The secret is stored as a hash and lasts 72 hours. */
class ContractorClaimController extends Controller
{
    public function show(Request $request, User $user)
    {
        $profile = $this->claimable($user, (string) $request->query('token'));

        return view('frontend.contractor.claim', ['user' => $user, 'profile' => $profile]);
    }

    public function store(Request $request, User $user)
    {
        $token = (string) ($request->input('token') ?: $request->query('token'));
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], ['terms.accepted' => 'Please accept the terms and privacy notice.']);

        DB::transaction(function () use ($user, $token, $data) {
            $profile = ContractorProfile::query()->where('user_id', $user->id)->lockForUpdate()->first();
            $this->assertClaim($profile, $user, $token);
            $user->forceFill(['password' => Hash::make($data['password'])])->save();
            if (! $user->hasRole('Contractor')) {
                $user->assignRole('Contractor');
            }
            $profile->forceFill([
                'terms_accepted_at' => now(),
                'privacy_version' => config('contractor_marketplace.privacy_version'),
                'claim_token_hash' => null,
                'claim_expires_at' => null,
            ])->save();
            $profile->log('claimed', $user, null, 'Claimed account from the old contractor module.');
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('contractor.profile.edit', ['step' => 'business'])
            ->with('success', 'Welcome. Add your business basics and documents to start getting jobs.');
    }

    private function claimable(User $user, string $token): ContractorProfile
    {
        $profile = $user->contractorProfile;
        $this->assertClaim($profile, $user, $token);

        return $profile;
    }

    private function assertClaim(?ContractorProfile $profile, User $user, string $token): void
    {
        abort_unless($profile && $profile->legacy_contractor && (int) $profile->user_id === (int) $user->id, 404);
        if ($profile->terms_accepted_at || blank($profile->claim_token_hash)) {
            abort(404);
        }
        if ($profile->claim_expires_at && $profile->claim_expires_at->isPast()) {
            abort(410);
        }
        $given = hash('sha256', $token);
        abort_unless($token !== '' && hash_equals((string) $profile->claim_token_hash, $given), 404);
    }
}
