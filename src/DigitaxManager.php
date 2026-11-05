<?php

namespace Uzapoint\Digitax;

use Illuminate\Http\Client\Factory as HttpFactory;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\Exceptions\EtimsDisabledException;
use Uzapoint\Digitax\Exceptions\EtimsModuleDisabledException;
use Uzapoint\Digitax\Support\DigitaxCredentials;
use Uzapoint\Digitax\Support\OfflineUrl;

/**
 * Entry point. Resolves a business's credentials and hands out clients.
 *
 *   $digitax->isEnabled($businessId, Module::SALES);
 *   $digitax->forBusiness($businessId, Module::SALES)->sales()->createWithItems($payload);
 *   $digitax->withApiKey($key)->business()->etimsInfo(); // verify a key
 */
class DigitaxManager
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected CredentialResolver $resolver,
        protected HttpFactory $http,
        protected array $config = [],
    ) {
    }

    public function credentials(int|string $businessId): ?DigitaxCredentials
    {
        return $this->resolver->resolve($businessId);
    }

    /**
     * True when the business has eTIMS on, a key, an active addon and, if
     * given, the module selected. Products and stocks are always selected.
     */
    public function isEnabled(int|string $businessId, ?string $module = null): bool
    {
        $credentials = $this->credentials($businessId);

        return $credentials !== null && ($module === null || $credentials->hasModule($module));
    }

    /**
     * @throws EtimsDisabledException
     * @throws EtimsModuleDisabledException
     */
    public function forBusiness(int|string $businessId, ?string $module = null): DigitaxClient
    {
        $credentials = $this->credentials($businessId);

        if ($credentials === null) {
            throw new EtimsDisabledException((string) $businessId);
        }

        if ($module !== null && !in_array($module, Module::ALL, true)) {
            throw new \InvalidArgumentException("Unknown eTIMS module '{$module}'");
        }

        if ($module !== null && !$credentials->hasModule($module)) {
            throw new EtimsModuleDisabledException((string) $businessId, $module);
        }

        return $this->withCredentials($credentials);
    }

    public function withCredentials(DigitaxCredentials $credentials): DigitaxClient
    {
        return new DigitaxClient($this->http, $credentials, $this->config);
    }

    public function withApiKey(string $apiKey, string $environment = 'test'): DigitaxClient
    {
        return $this->withCredentials(DigitaxCredentials::forKey($apiKey, $environment));
    }

    /**
     * Offline QR URL for a business, or null if it has no DigiTax business id.
     */
    public function offlineUrl(int|string $businessId, string $traderInvoiceNumber): ?string
    {
        return OfflineUrl::make(
            $this->credentials($businessId)?->digitaxBusinessId,
            $traderInvoiceNumber,
            $this->config['offline_url'] ?? null,
        );
    }
}
