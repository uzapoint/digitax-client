<?php

namespace Uzapoint\Digitax\Sync;

use Illuminate\Support\Facades\Log;

/**
 * Event bus handler for settings.etims.updated. Register in a service's
 * config/eventbus.php:
 *
 *   'handlers' => [
 *       'settings.etims.updated' => \Uzapoint\Digitax\Sync\HandleEtimsConfigUpdated::class,
 *   ],
 *
 * and bind the service's queue to exchange settings.events with that
 * routing key.
 */
class HandleEtimsConfigUpdated
{
    public function __construct(protected EtimsConfigSynchronizer $synchronizer)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $changed = $this->synchronizer->apply($data);

        Log::info('[digitax] eTIMS config ' . ($changed ? 'applied' : 'skipped (stale)'), [
            'business_id' => $data['business_id'] ?? null,
            'enabled' => $data['enabled'] ?? null,
            'version' => $data['version'] ?? null,
        ]);
    }
}
