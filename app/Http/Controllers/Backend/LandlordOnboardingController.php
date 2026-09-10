<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Tenancy;
use App\Services\Onboarding\LandlordOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandlordOnboardingController extends Controller
{
    public function __construct(private readonly LandlordOnboardingService $onboarding)
    {
    }

    public function state(Request $request): JsonResponse
    {
        [$account, $user] = $this->guard($request, false);

        return response()->json([
            'ok' => true,
            'data' => $this->onboarding->state($account, $user),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->guard($request);

        $validated = $request->validate([
            'postcode' => 'required|string|max:12',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->onboarding->search($validated['postcode']),
        ]);
    }

    public function storeProperty(Request $request): JsonResponse
    {
        [$account, $user] = $this->guard($request);

        $validated = $request->validate([
            'chimnie_id' => 'required|string|max:120',
            'postcode' => 'required|string|max:12',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->onboarding->saveProperty(
                $account,
                $user,
                $validated['chimnie_id'],
                $validated['postcode']
            ),
        ]);
    }

    public function storeDocument(Request $request): JsonResponse
    {
        [$account, $user] = $this->guard($request);

        $validated = $request->validate([
            'kind' => 'required|in:photo_id,proof_of_address',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ]);

        $document = $this->onboarding->saveDocument($user, $validated['kind'], $validated['file']);

        if ((int) $account->onboarding_step < 3) {
            $this->onboarding->saveStep($account, 3);
        }

        return response()->json([
            'ok' => true,
            'data' => $document,
        ]);
    }

    public function storeOwners(Request $request): JsonResponse
    {
        [$account, $user] = $this->guard($request);

        $validated = $request->validate([
            'owner' => 'nullable|array',
            'owner.name' => 'nullable|string|max:120',
            'owner.email' => 'nullable|email|max:190',
            'owner.phone' => 'nullable|string|max:40',
            'owners' => 'nullable|array|max:5',
            'owners.*.name' => 'nullable|string|max:120',
            'owners.*.email' => 'nullable|email|max:190',
            'owners.*.phone' => 'nullable|string|max:40',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->onboarding->saveOwners(
                $account,
                $user,
                $validated['owner'] ?? [],
                $validated['owners'] ?? []
            ),
        ]);
    }

    public function storeTenancy(Request $request): JsonResponse
    {
        [$account, $user] = $this->guard($request);
        $this->authorize('create', Tenancy::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'rent' => 'required|numeric|min:0.01|max:999999.99',
            'deposit' => 'nullable|numeric|min:0|max:999999.99',
            'frequency' => 'nullable|in:Monthly,Weekly',
            'term_months' => 'nullable|integer|min:1|max:36',
            'move_in' => 'nullable|date',
            'invite' => 'sometimes|boolean',
            'occupants' => 'nullable|array|max:8',
            'occupants.*.name' => 'nullable|string|max:120',
            'occupants.*.email' => 'nullable|email|max:190',
            'occupants.*.phone' => 'nullable|string|max:40',
        ]);

        $sendInvite = $request->boolean('invite', true);

        return response()->json([
            'ok' => true,
            'data' => $this->onboarding->saveTenancy($account, $user, $validated, $sendInvite),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        [$account, $user] = $this->guard($request);

        $inviteMeta = $request->validate([
            'invited' => 'sometimes|boolean',
            'sent' => 'sometimes|boolean',
            'invite_error' => 'sometimes|nullable|string|max:500',
        ]);

        $this->onboarding->complete($account, $user);
        $this->onboarding->dismissAddProperty();
        $account->refresh();
        $property = Property::query()
            ->where('account_id', $account->id)
            ->whereKey($account->onboarding_property_id)
            ->first();
        $tenancy = $this->onboarding->latestOnboardingTenancy($account);

        return response()->json([
            'ok' => true,
            'data' => [
                'property' => $property ? [
                    'id' => $property->id,
                    'label' => trim(implode(', ', array_filter([
                        $property->line_1,
                        $property->city,
                        $property->postcode,
                    ]))),
                ] : null,
                'tenancy' => $tenancy,
                'invite' => [
                    'invited' => (bool) ($inviteMeta['invited'] ?? false),
                    'sent' => (bool) ($inviteMeta['sent'] ?? false),
                    'error' => $inviteMeta['invite_error'] ?? null,
                ],
                'counts' => [
                    'properties' => Property::query()->where('account_id', $account->id)->count(),
                    'tenancies' => Tenancy::query()
                        ->where('account_id', $account->id)
                        ->where('status', 'Active')
                        ->count(),
                ],
            ],
        ]);
    }

    public function saveStep(Request $request): JsonResponse
    {
        [$account] = $this->guard($request);

        $validated = $request->validate([
            'step' => 'required|integer|min:1|max:3',
        ]);

        $this->onboarding->saveStep($account, (int) $validated['step']);

        return response()->json(['ok' => true]);
    }

    public function dismiss(Request $request): JsonResponse
    {
        [$account] = $this->guard($request, false);
        $this->onboarding->leaveAddFlow($account);

        return response()->json(['ok' => true]);
    }

    /**
     * @return array{0: \App\Models\Account, 1: \App\Models\User}
     */
    private function guard(Request $request, bool $mustBeIncomplete = true): array
    {
        $user = $request->user();
        $account = current_account();

        abort_unless($user && $account && is_landlord_plan_user($user), 403);

        if ($mustBeIncomplete) {
            abort_unless($this->onboarding->canMutate($user, $account), 403);
        }

        return [$account, $user];
    }
}
