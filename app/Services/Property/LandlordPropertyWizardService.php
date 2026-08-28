<?php

namespace App\Services\Property;

use App\Models\Property;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LandlordPropertyWizardService
{
    public function persist(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (UniqueConstraintViolationException $exception) {
            $message = strtolower($exception->getMessage());
            $isPropertyIdentityViolation =
                str_contains($message, 'properties_account_identity_unique')
                || str_contains($message, 'properties_account_identity_huniq')
                || (
                    str_contains($message, 'properties.account_id')
                    && str_contains($message, 'properties.property_identity_hash')
                );

            if (! $isPropertyIdentityViolation) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'line_1' => 'This property address already exists in your account.',
            ]);
        }
    }

    public function createDraft(array $data): Property
    {
        $ref = generateReferenceNumber(Property::class, 'prop_ref_no', 'RESISQP');

        return $this->persist(function () use ($data, $ref) {
            return Property::create(array_merge($data, [
                'account_id' => current_account_id(),
                'prop_ref_no' => $ref,
                'created_by' => Auth::id(),
                'quick_step' => 1,
                'property_type' => $data['property_type'] ?? 'lettings',
            ]));
        });
    }

    public function updateStep(Property $property, array $data, int $step): Property
    {
        $this->persist(function () use ($property, $data, $step) {
            $property->update(array_merge($data, [
                'quick_step' => $step,
            ]));
        });

        return $property->fresh();
    }
}
