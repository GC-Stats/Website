<?php

namespace App\Actions\Fortify;

use App\Models\SanctionIdentity;
use App\Models\User;
use App\Services\SanctionService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Pronouns;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(
        private readonly SanctionService $sanctions,
    ) {}

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:32',
                'alpha_dash',
                Rule::unique('users')->ignore($user->id),
            ],

            'pronouns' => ['required', 'integer', Rule::in(Pronouns::OPTIONS)],

            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->getMessages())
                ->errorBag('updateProfileInformation')
                ->redirectTo(route('account.edit'));
        }

        $email = $input['email'] ?? null;
        $oldEmail = $user->email;
        $emailChanged = $email !== $oldEmail;

        if ($emailChanged && $email === null && $user->password !== null) {
            throw ValidationException::withMessages([
                'email' => __('account.errors.email_required_for_password'),
            ])->errorBag('updateProfileInformation')->redirectTo(route('account.edit'));
        }

        if ($emailChanged && $email !== null) {
            if ($this->sanctions->hasActiveSanctionFor(SanctionIdentity::TYPE_EMAIL, $email)) {
                throw ValidationException::withMessages([
                    'email' => __('account.errors.email_blocked'),
                ])->errorBag('updateProfileInformation')->redirectTo(route('account.edit'));
            }
        }

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input, $email);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'username' => $input['username'],
                'pronouns' => $input['pronouns'],
                'email' => $email,
            ])->save();
        }

        if ($user->wasChanged(['name', 'username', 'pronouns'])) {
            activity('account')
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(ActivityChangeSet::fromModel($user, ['name', 'username', 'pronouns'])->toArray())
                ->log('account.profile_updated');
        }

        if ($emailChanged && $email !== null) {
            $this->sanctions->propagateIdentity($user, SanctionIdentity::TYPE_EMAIL, $email);
            $this->sanctions->transferNonBanSanctions($user, SanctionIdentity::TYPE_EMAIL, $email);

            activity('account')
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(ActivityChangeSet::make()->add('email', $oldEmail, $email)->toArray())
                ->log('account.email_changed');
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input, ?string $email): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'username' => $input['username'],
            'pronouns' => $input['pronouns'],
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
