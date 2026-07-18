<?php

/**
 * GC-Stats — User model
 *
 * Represents an authenticatable application user (e.g. admins/editors who
 * author news articles or manage data).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Support\PermissionTeam;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'discord_synced_at' => 'datetime',
        ];
    }

    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reported_user_id');
    }

    public function reportsSubmitted(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reporter_id');
    }

    /**
     * Number of distinct ways this account can currently be authenticated
     * with (password + each linked social provider). Must never drop to 0.
     */
    public function authMethodsCount(): int
    {
        return ($this->password !== null ? 1 : 0) + $this->socialAccounts()->count();
    }

    /**
     * Whether this user holds the global 'super-admin' role — checked
     * directly against the pivot table rather than hasRole(), which is
     * scoped to whatever PermissionTeam context is currently active (e.g.
     * a team's own role-management pages switch context to that team).
     * Site-wide super-admin status must never depend on that.
     */
    private ?bool $isSuperAdminCache = null;

    public function isSuperAdmin(): bool
    {
        // Memoized: Gate::before calls this on every single ->can()/
        // Gate::allows() check for the lifetime of the request, and unlike
        // hasRole() (which reuses Eloquent's in-memory roles relation)
        // this hits the DB directly — without caching, a single page with
        // a dozen permission checks would run two extra queries each.
        if ($this->isSuperAdminCache !== null) {
            return $this->isSuperAdminCache;
        }

        // team_id filter matters: a team can name a custom role
        // 'super-admin' too (Team\RoleController::store has no reserved-
        // name check), and Role::where('name', ...) alone isn't
        // team-scoped — without this it could resolve to the wrong row.
        $roleId = Role::where('name', 'super-admin')->where('team_id', PermissionTeam::GLOBAL_ID)->value('id');

        if ($roleId === null) {
            return $this->isSuperAdminCache = false;
        }

        return $this->isSuperAdminCache = DB::table('model_has_roles')
            ->where('model_id', $this->id)
            ->where('model_type', static::class)
            ->where('role_id', $roleId)
            ->where('team_id', PermissionTeam::GLOBAL_ID)
            ->exists();
    }

    /**
     * Whether this user can reach that team's own management page at all
     * (any of the team.* permissions, checked under that team's own
     * PermissionTeam context — then restored, so callers on a page that
     * also does its own global-context permission checks elsewhere in the
     * same request, e.g. the nav's admin-panel link, aren't affected).
     */
    public function canManageTeam(int $teamId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        PermissionTeam::use($teamId);

        $canManage = $this->can('team.profile.edit') || $this->can('team.logo.upload') || $this->can('team.roles.manage');

        $registrar->setPermissionsTeamId($previousTeamId);

        return $canManage;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
