<?php

/**
 * GC-Stats — Bootstrap a super-admin
 *
 * Grants the 'super-admin' role to an existing user by username — used to
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
use App\Support\PermissionTeam;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    protected $signature = 'admin:make-super-admin {username : Username of an existing user}';

    protected $description = 'Grant the super-admin role to an existing user';

    public function handle(): int
    {
        $username = $this->argument('username');
        $user = User::where('username', $username)->first();

        if (! $user) {
            $this->error("No user found [{$username}]. They need to sign up first (password or social login).");

            return self::FAILURE;
        }

        // Looked up via the is_super_admin flag rather than a hardcoded
        // name, since that role's name is editable (see RoleController).
        $role = Role::where('team_id', PermissionTeam::GLOBAL_ID)->where('is_super_admin', true)->firstOrFail();

        if ($user->isSuperAdmin()) {
            $this->info("{$user->name} ({$username}) is already super-admin.");

            return self::SUCCESS;
        }

        $user->assignRole($role);

        activity('administration')->performedOn($user)
            ->withProperties(['role' => $role->name, 'via' => 'admin:make-super-admin'])
            ->log('role.assigned');

        $this->info("Granted super-admin to {$user->name} ({$username}).");

        return self::SUCCESS;
    }
}
