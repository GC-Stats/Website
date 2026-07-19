<?php

/**
 * GC-Stats — Bootstrap a super-admin
 *
 * Grants the 'super-admin' role to an existing user by email — used to
 * bootstrap the first admin, since /admin/roles itself requires it.
 * Usage: php artisan admin:make-super-admin user@example.com
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'admin:make-super-admin {email : Email address of an existing user}';

    protected $description = 'Grant the super-admin role to an existing user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}]. They need to sign up first (password or social login).");

            return self::FAILURE;
        }

        if ($user->hasRole('super-admin')) {
            $this->info("{$user->name} ({$email}) is already super-admin.");

            return self::SUCCESS;
        }

        $user->assignRole('super-admin');

        activity('administration')->performedOn($user)
            ->withProperties(['role' => 'super-admin', 'via' => 'admin:make-super-admin'])
            ->log('role.assigned');

        $this->info("Granted super-admin to {$user->name} ({$email}).");

        return self::SUCCESS;
    }
}
