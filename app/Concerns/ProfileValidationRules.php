<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'phone' => $this->phoneNumberRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate phone numbers.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneNumberRules(?int $userId = null): array
    {
        return [
            'nullable',
            'string',
            'regex:/^254(7|1)[0-9]{8}$/',
            $userId === null
                ? Rule::unique(User::class, 'phone')
                : Rule::unique(User::class, 'phone')->ignore($userId),
        ];
    }

    protected function phoneNumberMessages(): array
    {
        return [
            'phone.regex' => 'The phone number must be a valid Kenyan number (Safaricom: 2547XXXXXXXX, Airtel/Telkom: 2541XXXXXXXX).',
            'phone.unique' => 'This phone number is already registered.',
        ];
    }
}
