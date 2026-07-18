<?php

namespace App\Actions\Fortify;

use App\Models\SanctionIdentity;
use App\Models\User;
use App\Services\SanctionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly SanctionService $sanctions,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        if ($this->sanctions->hasActiveSanctionFor(SanctionIdentity::TYPE_EMAIL, $input['email'])) {
            throw ValidationException::withMessages([
                'email' => __('account.errors.email_blocked'),
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        activity('account')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['method' => 'password'])
            ->log('account.registered');

        return $user;
    }
}
