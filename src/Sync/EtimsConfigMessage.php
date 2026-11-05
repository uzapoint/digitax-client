<?php

namespace Uzapoint\Digitax\Sync;

use InvalidArgumentException;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Support\ConfigCipher;

/**
 * Payload of the settings.etims.updated event.
 *
 * {
 *   "business_id": "12",
 *   "enabled": true,           // ETIMS_ENABLED AND addon active
 *   "addon_active": true,
 *   "environment": "test",
 *   "digitax_business_id": "business_01H...",
 *   "modules": {"products": true, "stocks": true, "sales": true, "purchases": false},
 *   "api_key_encrypted": "...", // ConfigCipher, null when no key is set
 *   "callback_url": "https://api.example.com/v1/callbacks/digitax/<token>",
 *   "version": 1762300000000    // epoch ms; consumers ignore older versions
 * }
 */
final class EtimsConfigMessage
{
    /**
     * @param  array<string, mixed>|null  $modules
     */
    public static function build(
        int|string $businessId,
        bool $enabled,
        bool $addonActive,
        string $environment,
        ?string $apiKey,
        ?string $digitaxBusinessId,
        ?array $modules,
        ?string $callbackUrl,
        ?int $version = null,
        ?ConfigCipher $cipher = null,
    ): array {
        if (!in_array($environment, ['test', 'live'], true)) {
            throw new InvalidArgumentException("Invalid DigiTax environment '{$environment}'");
        }

        $apiKey = is_string($apiKey) && trim($apiKey) !== '' ? trim($apiKey) : null;

        return [
            'business_id' => (string) $businessId,
            'enabled' => $enabled && $addonActive,
            'addon_active' => $addonActive,
            'environment' => $environment,
            'digitax_business_id' => $digitaxBusinessId ?: null,
            'modules' => Module::normalize($modules),
            'api_key_encrypted' => $apiKey === null ? null : ($cipher ?? new ConfigCipher())->encrypt($apiKey),
            'callback_url' => $callbackUrl ?: null,
            'version' => $version ?? (int) floor(microtime(true) * 1000),
        ];
    }
}
