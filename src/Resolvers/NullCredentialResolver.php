<?php

namespace Uzapoint\Digitax\Resolvers;

use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\Support\DigitaxCredentials;

/**
 * eTIMS is always off. Useful for services that never sync.
 */
class NullCredentialResolver implements CredentialResolver
{
    public function resolve(int|string $businessId): ?DigitaxCredentials
    {
        return null;
    }
}
