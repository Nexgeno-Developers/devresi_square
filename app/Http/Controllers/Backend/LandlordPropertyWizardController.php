<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use App\Http\Requests\Property\LandlordPropertyWizardStepRequest;
use App\Models\Country;
use App\Models\Property;
use App\Services\Property\LandlordPropertyWizardService;
use App\Services\Saas\AccountLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandlordPropertyWizardController
{
    use EnforcesSaasPlanLimits;

    public function __construct(
        private readonly LandlordPropertyWizardService $wizardService
    ) {
    }

    public function show(Request $request, int $step = 1): View|RedirectResponse
    {
        $step = max(1, min(3, $step));
        $property = $this->resolveProperty($request);

        if (! $property && $this->saasLimitError('property')) {
            $property = $this->findResumableDraft();
        }

        if ($response = $this->guardNewPropertyCreation($property)) {
            return $response;
        }

        if ($property && ! $request->filled('property_id')) {
            return redirect()->route('admin.properties.landlord_wizard.step', [
                'step' => max(1, min(3, (int) $property->quick_step + 1)),
                'property_id' => $property->id,
            ]);
        }

        if ($property && $step > 1 && (int) $property->quick_step < $step - 1) {
            return redirect()->route('admin.properties.landlord_wizard.step', [
                'step' => max(1, (int) $property->quick_step + 1),
                'property_id' => $property->id,
            ]);
        }

        $countries = Country::orderBy('name')->get();
        $steps = config('landlord_mvp.wizard_steps', []);
        $completeness = $property ? $this->buildCompleteness($property) : [];

        return view('backend.properties.landlord-wizard.step-' . $step, compact(
            'property',
            'countries',
            'step',
            'steps',
            'completeness'
        ));
    }

    public function store(LandlordPropertyWizardStepRequest $request): View|RedirectResponse
    {
        $step = (int) $request->input('step');
        $validated = $request->validated();
        unset($validated['confirm']);

        $property = null;

        if ($request->filled('property_id')) {
            $property = Property::findOrFail($request->input('property_id'));
            ensureModelBelongsToCurrentAccount($property);
            $this->authorizePropertyAccess($property);
        }

        if ($step === 1 && ! $property) {
            if ($response = $this->guardNewPropertyCreation(null)) {
                return $response;
            }
        }

        if ($step === 1) {
            if ($property) {
                $property = $this->wizardService->updateStep($property, $validated, 1);
            } else {
                $property = $this->wizardService->createDraft($validated);
            }

            return redirect()->route('admin.properties.landlord_wizard.step', [
                'step' => 2,
                'property_id' => $property->id,
            ]);
        }

        if (! $property) {
            return redirect()->route('admin.properties.landlord_wizard.show');
        }

        if ($step === 2) {
            $this->wizardService->updateStep($property, $validated, 2);

            return redirect()->route('admin.properties.landlord_wizard.step', [
                'step' => 3,
                'property_id' => $property->id,
            ]);
        }

        if ($step === 3) {
            $this->wizardService->updateStep($property, [], 3);

            flash('Your property has been added successfully.')->success();

            return redirect()->route('admin.properties.index', [
                'property_id' => $property->id,
                'tabname' => 'property',
            ]);
        }

        return redirect()->route('admin.properties.landlord_wizard.show');
    }

    private function findResumableDraft(): ?Property
    {
        $accountId = current_account_id();
        $userId = auth()->id();

        if (! $accountId || ! $userId) {
            return null;
        }

        return Property::query()
            ->where('account_id', $accountId)
            ->where('created_by', $userId)
            ->whereIn('quick_step', [1, 2])
            ->latest('id')
            ->first();
    }

    private function guardNewPropertyCreation(?Property $property): View|RedirectResponse|null
    {
        if ($property) {
            return null;
        }

        if (! $this->saasLimitError('property')) {
            return null;
        }

        $account = current_account();
        $summary = $account
            ? app(AccountLimitService::class)->summary($account)
            : null;

        return view('backend.properties.landlord-wizard.limit-reached', [
            'message' => $this->saasLimitError('property'),
            'summary' => $summary,
        ]);
    }

    private function resolveProperty(Request $request): ?Property
    {
        if (! $request->filled('property_id')) {
            return null;
        }

        $property = Property::find($request->input('property_id'));

        if (! $property) {
            return null;
        }

        ensureModelBelongsToCurrentAccount($property);
        $this->authorizePropertyAccess($property);

        return $property;
    }

    private function authorizePropertyAccess(Property $property): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['Super Admin', 'Property Manager'])) {
            return;
        }

        if ($user->hasRole('Landlord') && (int) $property->created_by === (int) $user->id) {
            return;
        }

        if ($user->hasRole('Estate Agent')) {
            $createdUserIds = $user->createdUsers()->pluck('id')->push($user->id);

            if ($createdUserIds->contains($property->created_by)) {
                return;
            }
        }

        abort(403, 'You are not allowed to edit this property.');
    }

    /**
     * @return array<int, array{label: string, value: string|null, status: string}>
     */
    private function buildCompleteness(Property $property): array
    {
        return [
            [
                'label' => 'Address',
                'value' => trim(implode(', ', array_filter([
                    $property->line_1,
                    $property->city,
                    $property->postcode,
                ]))),
                'status' => $property->line_1 && $property->postcode ? 'known' : 'missing',
            ],
            [
                'label' => 'Property type',
                'value' => $property->specific_property_type
                    ? ucfirst($property->specific_property_type) . ' (' . ucfirst($property->property_type ?? '') . ')'
                    : null,
                'status' => $property->specific_property_type ? 'known' : 'missing',
            ],
            [
                'label' => 'Bedrooms / bathrooms',
                'value' => ($property->bedroom || $property->bathroom)
                    ? trim(($property->bedroom ?? '?') . ' bed / ' . ($property->bathroom ?? '?') . ' bath')
                    : null,
                'status' => ($property->bedroom && $property->bathroom) ? 'known' : 'missing',
            ],
            [
                'label' => 'Tenure',
                'value' => $property->tenure ?: null,
                'status' => $property->tenure ? 'known' : 'missing',
            ],
            [
                'label' => 'EPC rating',
                'value' => $property->epc_rating ?: null,
                'status' => $property->epc_rating ? 'known' : 'missing',
            ],
        ];
    }
}
