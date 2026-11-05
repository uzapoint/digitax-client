<?php

namespace Uzapoint\Digitax\Testing;

use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\Support\DigitaxCredentials;

/**
 * In-memory resolver for tests.
 */
class ArrayCredentialResolver implements CredentialResolver
{
    /** @var array<string, DigitaxCredentials> */
    private array $credentials = [];

    public function set(DigitaxCredentials $credentials): static
    {
        $this->credentials[$credentials->businessId] = $credentials;

        return $this;
    }

    public function forget(int|string $businessId): static
    {
        unset($this->credentials[(string) $businessId]);

        return $this;
    }

    public function resolve(int|string $businessId): ?DigitaxCredentials
    {
        return $this->credentials[(string) $businessId] ?? null;
    }
}
