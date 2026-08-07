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
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'team_id', 'team_tag', 'pronouns', 'bio', 'socials'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmailContract, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmail, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'data_explorer_enabled' => 'boolean',
            'pronouns' => 'integer',
            'socials' => 'array',
        ];
    }

    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    /** The team this user has picked to show as "fan of" on their public profile. */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function newsAuthor(): HasOne
    {
        return $this->hasOne(NewsAuthor::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    /**
     * In-app notifications addressed to this user (see NotificationService)
     * — overrides Notifiable's own notifications() relation, which targets
     * Illuminate's database notification channel and is unused here.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderByDesc('id');
    }

    /** Change requests this user has submitted (see ChangeRequest::requested_by), across any subject type. */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class, 'requested_by');
    }

    /**
     * Active global (not team-scoped) suspension or ban, if any — the same
     * check EnsureAccountIsNotSanctioned enforces at the route level, reused
     * here for actions that have no dedicated route to attach middleware to
     * (e.g. Livewire component methods).
     */
    public function activeGlobalBlockingSanction(): ?Sanction
    {
        return $this->sanctions()
            ->active()
            ->whereNull('team_id')
            ->whereIn('type', Sanction::BLOCKING_TYPES)
            ->latest('starts_at')
            ->first();
    }

    /**
     * Whether this user holds any global admin-panel role (moderator,
     * editor, super-admin, or a custom one) — i.e. is site staff. Checked
     * directly against the pivot table for the same reason as
     * isSuperAdmin(): must not depend on whatever PermissionTeam context is
     * currently active. Used to restrict sanctioning staff accounts to
     * super admins only, see SanctionService::issue().
     */
    public function hasGlobalRole(): bool
    {
        return DB::table('model_has_roles')
            ->where('model_id', $this->id)
            ->where('model_type', static::class)
            ->where('team_id', PermissionTeam::GLOBAL_ID)
            ->exists();
    }

    /**
     * Whether this user is currently allowed to set a public bio/socials —
     * either they're staff (see hasGlobalRole()), or their account has
     * proven itself old enough: 30 days normally, or only 15 days if it's
     * backed by a linked OAuth provider (harder to throwaway than an
     * email/password signup).
     */
    public function isEligibleForBio(): bool
    {
        if ($this->hasGlobalRole()) {
            return true;
        }

        $accountAgeDays = $this->created_at->diffInDays(now());

        if ($this->socialAccounts()->exists()) {
            return $accountAgeDays >= 15;
        }

        return $accountAgeDays >= 30;
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reported_user_id');
    }

    public function reportsSubmitted(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reporter_id');
    }

    public function apiRequestLogs(): HasManyThrough
    {
        return $this->hasManyThrough(ApiRequestLog::class, ApiKey::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function dataExplorerApiKeys(): HasMany
    {
        return $this->hasMany(DataExplorerApiKey::class);
    }

    /** The one (if any) of this user's linked provider keys currently toggled on. */
    public function activeDataExplorerApiKey(): HasOne
    {
        return $this->hasOne(DataExplorerApiKey::class)->where('is_active', true);
    }

    public function dataExplorerUsages(): HasMany
    {
        return $this->hasMany(DataExplorerUsage::class);
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
     * Whether this user holds the protected super-admin role — checked
     * directly against the pivot table rather than hasRole(), which is
     * scoped to whatever PermissionTeam context is currently active (e.g.
     * a team's own role-management pages switch context to that team).
     * Site-wide super-admin status must never depend on that. Matched via
     * the is_super_admin flag rather than name, since the role's name is
     * editable (see RoleController).
     */
    private ?bool $isSuperAdminCache = null;

    public function isSuperAdmin(): bool
    {
        if ($this->isSuperAdminCache !== null) {
            return $this->isSuperAdminCache;
        }

        $roleId = Role::where('is_super_admin', true)->where('team_id', PermissionTeam::GLOBAL_ID)->value('id');

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
     * Shared by every "search users to assign a role/owner to" screen
     * (Admin\RoleController, Admin\TeamController) so the searchable
     * columns and LIKE-escaping stay identical everywhere.
     */
    public function scopeMatching(Builder $query, string $term): Builder
    {
        $escaped = Str::of($term)->replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'])->toString();

        return $query->where(
            fn ($q) => $q->where('name', 'like', "%{$escaped}%")
                ->orWhere('username', 'like', "%{$escaped}%")
                ->orWhere('email', 'like', "%{$escaped}%")
        );
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

    /**
     * Gravatar URL for this user's email. Uses `d=404` so the caller can
     * fall back to initials (e.g. via an `onerror` handler) when no
     * Gravatar is registered for the address, rather than always showing
     * Gravatar's generic placeholder image.
     */
    public function gravatarUrl(int $size = 128): string
    {
        $hash = hash('sha256', Str::lower(Str::trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=404";
    }

    /**
     * SEO-friendly URL segment for this user's public profile — not stored,
     * derived from the name/username, matching Team::routeSlug()'s pattern.
     */
    public function routeSlug(): string
    {
        return Str::routeSlug($this->username ?: $this->name, $this->id);
    }
}
