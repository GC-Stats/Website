<?php

/**
 * GC-Stats — Data Explorer BYOK key service
 *
 * Validates, stores and removes a user's own OpenAI/Anthropic API key(s),
 * used to keep querying once they're unauthorized for the platform key or
 * have spent their share of its monthly quota (see DataExplorerQuotaService).
 * A user may link both providers at once but only one can be is_active —
 * that's the one requests actually use, see activate(). Each key is
 * validated against its provider with a light test call before it's ever
 * stored, and stays encrypted at rest (Eloquent's native 'encrypted' cast
 * on DataExplorerApiKey::key_encrypted).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\DataExplorerApiKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class DataExplorerKeyService
{
    /**
     * Links (or replaces) $user's key for $provider. The very first key a
     * user links is activated automatically so the feature starts working
     * right away; linking a second provider afterwards leaves activation
     * alone — flipping which key is actually used is an explicit choice via
     * activate(), never a silent side effect of adding a key.
     *
     * @throws ValidationException if the provider is unknown or the key fails validation against it
     */
    public function link(User $user, string $provider, string $clearKey): DataExplorerApiKey
    {
        if (! in_array($provider, DataExplorerApiKey::PROVIDERS, true)) {
            throw ValidationException::withMessages(['provider' => __('data_explorer.errors.invalid_provider')]);
        }

        if (! $this->validateKey($provider, $clearKey)) {
            throw ValidationException::withMessages(['key' => __('data_explorer.errors.invalid_key')]);
        }

        return DB::transaction(function () use ($user, $provider, $clearKey) {
            $hasAnyKey = $user->dataExplorerApiKeys()->exists();

            return DataExplorerApiKey::updateOrCreate(
                ['user_id' => $user->id, 'provider' => $provider],
                [
                    'key_encrypted' => $clearKey,
                    'is_active' => ! $hasAnyKey,
                    'linked_at' => now(),
                    'last_validated_at' => now(),
                    'last_validation_status' => DataExplorerApiKey::VALIDATION_VALID,
                ],
            );
        });
    }

    public function unlink(User $user, string $provider): void
    {
        DataExplorerApiKey::where('user_id', $user->id)->where('provider', $provider)->delete();
    }

    /**
     * Switches which linked key is used — deactivates every other provider
     * for this user and activates $provider, atomically so a request
     * running concurrently never sees two (or zero) active keys.
     *
     * @throws ValidationException if $user has no valid key for $provider
     */
    public function activate(User $user, string $provider): void
    {
        DB::transaction(function () use ($user, $provider) {
            $key = DataExplorerApiKey::where('user_id', $user->id)
                ->where('provider', $provider)
                ->lockForUpdate()
                ->first();

            if ($key === null || ! $key->isValid()) {
                throw ValidationException::withMessages(['provider' => __('data_explorer.errors.invalid_provider')]);
            }

            $user->dataExplorerApiKeys()->where('id', '!=', $key->id)->update(['is_active' => false]);
            $key->update(['is_active' => true]);
        });
    }

    /**
     * Light, cheap-as-possible test call against the provider — just enough
     * to confirm the key authenticates, not a real completion request.
     * Uses its own short timeout, distinct from the 15-20s budget given to
     * an actual query call, since this runs synchronously inside the
     * settings form submission.
     */
    public function validateKey(string $provider, string $clearKey): bool
    {
        try {
            $response = match ($provider) {
                DataExplorerApiKey::PROVIDER_OPENAI => Http::withToken($clearKey)
                    ->timeout(10)
                    ->get('https://api.openai.com/v1/models'),
                DataExplorerApiKey::PROVIDER_ANTHROPIC => Http::withHeaders([
                    'x-api-key' => $clearKey,
                    'anthropic-version' => '2023-06-01',
                ])->timeout(10)->get('https://api.anthropic.com/v1/models'),
                default => null,
            };
        } catch (\Throwable) {
            return false;
        }

        return $response?->successful() ?? false;
    }
}
