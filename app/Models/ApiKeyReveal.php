<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ApiKeyReveal extends Model
{
    protected $fillable = [
        'api_key_id',
        'token',
        'key_value_encrypted',
        'expires_at',
        'viewed_at',
        'superseded_at',
    ];

    protected $hidden = [
        'key_value_encrypted',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'viewed_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    public function isConsumed(): bool
    {
        return $this->viewed_at !== null
            || $this->superseded_at !== null
            || $this->expires_at->isPast();
    }

    public static function issue(ApiKey $apiKey, string $clearKey): self
    {
        // A previous reveal for this key (e.g. a prior regenerate() the user
        // never opened) would otherwise still decrypt to a now-stale clear
        // key for up to its own TTL — supersede it immediately so only the
        // reveal just issued can ever succeed. Kept distinct from viewed_at
        // so an audit can still tell "seen by a human" apart from
        // "invalidated, never seen".
        static::where('api_key_id', $apiKey->id)
            ->whereNull('viewed_at')
            ->whereNull('superseded_at')
            ->update(['superseded_at' => now()]);

        return self::create([
            'api_key_id' => $apiKey->id,
            'token' => bin2hex(random_bytes(32)),
            'key_value_encrypted' => Crypt::encryptString($clearKey),
            'expires_at' => now()->addMinutes(config('api_keys.reveal_ttl_minutes', 15)),
        ]);
    }

    /**
     * Atomically claims this reveal — a single UPDATE guarded by
     * `viewed_at/superseded_at IS NULL AND expires_at > now()` — and decrypts
     * only if the claim succeeded. Closes the check-then-act race where two
     * concurrent requests could both pass an earlier isConsumed() check
     * before either write landed; folding the expiry check into the same
     * query (rather than checking it in PHP beforehand) keeps that guard
     * atomic too.
     */
    public function consume(): ?string
    {
        $claimed = static::whereKey($this->id)
            ->whereNull('viewed_at')
            ->whereNull('superseded_at')
            ->where('expires_at', '>', now())
            ->update(['viewed_at' => now()]);

        if ($claimed === 0) {
            return null;
        }

        try {
            return Crypt::decryptString($this->key_value_encrypted);
        } catch (DecryptException $e) {
            // The claim above already committed — this reveal is burned
            // either way. Logged because a decrypt failure here (APP_KEY
            // rotation, corrupted ciphertext) is otherwise indistinguishable
            // from a normal expired/already-used link.
            Log::error('ApiKeyReveal decrypt failed after claiming reveal', [
                'api_key_reveal_id' => $this->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
