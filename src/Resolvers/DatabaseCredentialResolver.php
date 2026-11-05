<?php

namespace Uzapoint\Digitax\Resolvers;

use Throwable;
use Illuminate\Support\Facades\Log;
use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\Models\EtimsConfig;
use Uzapoint\Digitax\Support\DigitaxCredentials;

/**
 * Reads the local etims_configs mirror (filled from settings.etims.updated).
 *
 * Deliberately NOT memoised: queue workers are long-lived, and a business
 * switching eTIMS off must take effect on the next job. The lookup is one
 * indexed row.
 */
class DatabaseCredentialResolver implements CredentialResolver
{
    public function resolve(int|string $businessId): ?DigitaxCredentials
    {
        try {
            $config = EtimsConfig::query()->where('business_id', (string) $businessId)->first();
        } catch (Throwable $e) {
            // Table not migrated yet, or the key cannot be decrypted.
            Log::warning("[digitax] could not load eTIMS config for business {$businessId}: {$e->getMessage()}");
            return null;
        }

        return $config?->toCredentials();
    }
}
