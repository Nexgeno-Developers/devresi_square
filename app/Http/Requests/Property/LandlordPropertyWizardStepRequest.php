<?php

namespace App\Http\Requests\Property;

use App\Rules\UniquePropertyIdentity;
use Illuminate\Foundation\Http\FormRequest;

class LandlordPropertyWizardStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Property Manager', 'Landlord', 'Estate Agent'])) {
            return $user->can('create properties');
        }

        return false;
    }

    public function rules(): array
    {
        return match ((int) $this->input('step')) {
            1 => [
                'line_1' => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
                    new UniquePropertyIdentity(
                        $this->all(),
                        current_account_id(),
                        $this->input('property_id')
                    ),
                ],
                'line_2' => 'nullable|string|max:255',
                'city' => 'required|string|max:100',
                'country' => 'required|exists:countries,id',
                'county' => 'nullable|string|max:155',
                'postcode' => 'required|string|max:20',
                'uprn' => 'nullable|string|max:32',
            ],
            2 => [
                'specific_property_type' => 'required|string|max:100',
                'property_type' => 'required|in:lettings,sales,both',
                'bedroom' => 'required|string|max:10',
                'bathroom' => 'required|string|max:10',
                'tenure' => 'nullable|string|max:100',
                'epc_rating' => 'nullable|string|max:5',
            ],
            3 => [
                'confirm' => 'accepted',
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'confirm.accepted' => 'Please confirm the property details before continuing.',
        ];
    }
}
