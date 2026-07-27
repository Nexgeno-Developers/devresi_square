<?php

namespace App\Rules;

use App\Models\Property;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePropertyIdentity implements ValidationRule
{
    public function __construct(
        private readonly array $attributes,
        private readonly int|string|null $accountId,
        private readonly int|string|null $ignorePropertyId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->accountId === null || $this->accountId === '') {
            return;
        }

        $identityHash = Property::makeIdentityHash($this->attributes);

        if ($identityHash === null) {
            return;
        }

        $duplicateExists = Property::withTrashed()
            ->forAccount($this->accountId)
            ->when(
                $this->ignorePropertyId !== null && $this->ignorePropertyId !== '',
                fn ($query) => $query->whereKeyNot($this->ignorePropertyId)
            )
            ->select([
                'id',
                'line_1',
                'line_2',
                'postcode',
                'country',
            ])
            ->cursor()
            ->contains(
                fn (Property $property) => Property::makeIdentityHash($property->getAttributes()) === $identityHash
            );

        if ($duplicateExists) {
            $fail('This property address already exists in your account.');
        }
    }
}
