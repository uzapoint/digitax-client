<?php

namespace Uzapoint\Digitax\Contracts;

use Uzapoint\Digitax\Support\DigitaxCredentials;

interface CredentialResolver
{
    /**
     * Credentials for a business, or null when eTIMS is disabled, has no
     * API key, or the etims-integration addon is not active.
     */
    public function resolve(int|string $businessId): ?DigitaxCredentials;
}
