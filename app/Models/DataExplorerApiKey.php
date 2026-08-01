<?php

/**
 * GC-Stats — Data Explorer personal (BYOK) API key
 *
 * One row per (user, provider): a user may link both an OpenAI and an
 * Anthropic key at once, but only one is_active at a time — that's the one
 * DataExplorerQuotaService/DataExplorerService actually use. Linked from the
 * dedicated /data-explorer/settings page once validated against the
 * provider (see DataExplorerKeyService). Lets a user keep querying once
 * they're unauthorized for the platform key, or have used their share of
 * its monthly quota — see DataExplorerQuotaService.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataExplorerApiKey extends Model
{
    use HasFactory;

    public const PROVIDER_OPENAI = 'openai';

    public const PROVIDER_ANTHROPIC = 'anthropic';

    public const PROVIDERS = [
        self::PROVIDER_OPENAI,
        self::PROVIDER_ANTHROPIC,
    ];

    /**
     * The exact model each provider is called with — informational, shown
     * on the AI query settings page so a user knows what their key powers.
     * Not user-selectable: GC-Stats-API pins one model per provider.
     */
    public const PROVIDER_MODELS = [
        self::PROVIDER_OPENAI => 'GPT-4o Mini',
        self::PROVIDER_ANTHROPIC => 'Claude Haiku 4.5',
    ];

    public const VALIDATION_VALID = 'valid';

    public const VALIDATION_INVALID = 'invalid';

    protected $fillable = [
        'user_id',
        'provider',
        'is_active',
        'key_encrypted',
        'linked_at',
        'last_validated_at',
        'last_validation_status',
    ];

    protected $hidden = [
        'key_encrypted',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'key_encrypted' => 'encrypted',
        'linked_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->last_validation_status === self::VALIDATION_VALID;
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->isValid();
    }
}
