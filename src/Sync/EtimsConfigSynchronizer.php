<?php

namespace Uzapoint\Digitax\Sync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Models\EtimsConfig;
use Uzapoint\Digitax\Support\ConfigCipher;

/**
 * Applies a settings.etims.updated message to the local etims_configs row.
 */
class EtimsConfigSynchronizer
{
    public function __construct(protected ?ConfigCipher $cipher = null)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return bool  true when the row changed, false for a stale message
     */
    public function apply(array $data): bool
    {
        $businessId = (string) ($data['business_id'] ?? '');
        if ($businessId === '') {
            throw new InvalidArgumentException('settings.etims.updated without business_id');
        }

        $version = (int) ($data['version'] ?? 0);

        $apiKey = null;
        if (!empty($data['api_key_encrypted'])) {
            $apiKey = ($this->cipher ??= new ConfigCipher())->decrypt((string) $data['api_key_encrypted']);
        }

        return DB::transaction(function () use ($businessId, $version, $apiKey, $data) {
            /** @var EtimsConfig|null $existing */
            $existing = EtimsConfig::query()->where('business_id', $businessId)->lockForUpdate()->first();

            if ($existing && $existing->version > $version) {
                Log::info("[digitax] ignoring stale eTIMS config v{$version} for business {$businessId} (have v{$existing->version})");
                return false;
            }

            $config = $existing ?? new EtimsConfig(['business_id' => $businessId]);
            $config->fill([
                'enabled' => (bool) ($data['enabled'] ?? false),
                'addon_active' => (bool) ($data['addon_active'] ?? false),
                'environment' => in_array($data['environment'] ?? null, ['test', 'live'], true) ? $data['environment'] : 'test',
                'api_key' => $apiKey,
                'digitax_business_id' => $data['digitax_business_id'] ?? null,
                'modules' => Module::normalize($data['modules'] ?? null),
                'callback_url' => $data['callback_url'] ?? null,
                'version' => $version,
            ]);
            $config->save();

            return true;
        });
    }
}
