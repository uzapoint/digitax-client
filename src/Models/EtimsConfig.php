<?php

namespace Uzapoint\Digitax\Models;

use Illuminate\Database\Eloquent\Model;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\Support\DigitaxCredentials;

/**
 * @property string $business_id
 * @property bool $enabled
 * @property bool $addon_active
 * @property string $environment
 * @property string|null $api_key
 * @property string|null $digitax_business_id
 * @property array<string, bool>|null $modules
 * @property string|null $callback_url
 * @property int $version
 */
class EtimsConfig extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['api_key'];

    protected $casts = [
        'enabled' => 'boolean',
        'addon_active' => 'boolean',
        'api_key' => 'encrypted',
        'modules' => 'array',
        'version' => 'integer',
    ];

    public function getTable(): string
    {
        return config('digitax.table', 'etims_configs');
    }

    public function isUsable(): bool
    {
        return $this->enabled && $this->addon_active && is_string($this->api_key) && $this->api_key !== '';
    }

    public function toCredentials(): ?DigitaxCredentials
    {
        if (!$this->isUsable()) {
            return null;
        }

        return new DigitaxCredentials(
            businessId: (string) $this->business_id,
            apiKey: $this->api_key,
            environment: $this->environment ?: 'test',
            digitaxBusinessId: $this->digitax_business_id,
            modules: Module::normalize($this->modules),
            callbackUrl: $this->callback_url,
        );
    }
}
