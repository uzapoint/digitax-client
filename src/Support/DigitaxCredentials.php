<?php

namespace Uzapoint\Digitax\Support;

use Uzapoint\Digitax\Codes\Module;

/**
 * Everything the client needs to talk to DigiTax for one business.
 */
final class DigitaxCredentials
{
    /** @var array<string, bool> */
    public readonly array $modules;

    /**
     * @param  array<string, mixed>|null  $modules
     */
    public function __construct(
        public readonly string $businessId,
        public readonly string $apiKey,
        public readonly string $environment = 'test',
        public readonly ?string $digitaxBusinessId = null,
        ?array $modules = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $baseUrl = null,
    ) {
        $this->modules = Module::normalize($modules);
    }

    public static function forKey(string $apiKey, string $environment = 'test', ?string $baseUrl = null): self
    {
        return new self(businessId: 'adhoc', apiKey: $apiKey, environment: $environment, baseUrl: $baseUrl);
    }

    public function hasModule(string $module): bool
    {
        return $this->modules[$module] ?? false;
    }

    public function isLive(): bool
    {
        return $this->environment === 'live';
    }
}
