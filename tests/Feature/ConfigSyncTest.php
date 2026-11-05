<?php

namespace Uzapoint\Digitax\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Uzapoint\Digitax\Codes\Module;
use Uzapoint\Digitax\DigitaxManager;
use Uzapoint\Digitax\Models\EtimsConfig;
use Uzapoint\Digitax\Sync\EtimsConfigMessage;
use Uzapoint\Digitax\Sync\HandleEtimsConfigUpdated;
use Uzapoint\Digitax\Tests\TestCase;

/**
 * Runs only against the in-memory sqlite connection defined in TestCase.
 */
class ConfigSyncTest extends TestCase
{
    use RefreshDatabase;

    private function message(array $overrides = []): array
    {
        return EtimsConfigMessage::build(...array_merge([
            'businessId' => 12,
            'enabled' => true,
            'addonActive' => true,
            'environment' => 'test',
            'apiKey' => 'secret-key',
            'digitaxBusinessId' => 'business_01XYZ',
            'modules' => ['sales' => true],
            'callbackUrl' => 'https://api.uzapoint.com/v1/callbacks/digitax/t',
            'version' => 1000,
        ], $overrides));
    }

    public function test_message_never_carries_plain_key(): void
    {
        $message = $this->message();

        $this->assertArrayNotHasKey('api_key', $message);
        $this->assertNotSame('secret-key', $message['api_key_encrypted']);
        $this->assertStringNotContainsString('secret-key', json_encode($message));
    }

    public function test_handler_applies_config_and_resolver_reads_it(): void
    {
        $this->assertSame('testing', DB::getDefaultConnection());

        app(HandleEtimsConfigUpdated::class)->handle($this->message());

        $row = EtimsConfig::first();
        $this->assertSame('12', $row->business_id);
        $this->assertSame('secret-key', $row->api_key);
        // At rest the key is encrypted with APP_KEY.
        $this->assertNotSame('secret-key', DB::table('etims_configs')->value('api_key'));

        $manager = app(DigitaxManager::class);
        $this->assertTrue($manager->isEnabled(12, Module::SALES));
        $this->assertFalse($manager->isEnabled(12, Module::PURCHASES));
        $this->assertSame('https://receipt.dg.tax/r/business_01XYZ/SAL-1', $manager->offlineUrl(12, 'SAL-1'));
    }

    public function test_stale_version_is_ignored(): void
    {
        $handler = app(HandleEtimsConfigUpdated::class);
        $handler->handle($this->message(['version' => 2000]));
        $handler->handle($this->message(['version' => 1500, 'enabled' => false]));

        $this->assertTrue(EtimsConfig::first()->enabled);
    }

    public function test_inactive_addon_disables_etims(): void
    {
        app(HandleEtimsConfigUpdated::class)->handle($this->message(['addonActive' => false]));

        $this->assertFalse(app(DigitaxManager::class)->isEnabled(12));
    }

    public function test_cleared_key_disables_etims(): void
    {
        $handler = app(HandleEtimsConfigUpdated::class);
        $handler->handle($this->message());
        $handler->handle($this->message(['apiKey' => null, 'version' => 2000]));

        $this->assertFalse(app(DigitaxManager::class)->isEnabled(12));
    }
}
