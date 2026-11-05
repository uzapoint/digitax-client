<?php

namespace Uzapoint\Digitax\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\DigitaxManager;
use Uzapoint\Digitax\DigitaxServiceProvider;
use Uzapoint\Digitax\Support\DigitaxCredentials;
use Uzapoint\Digitax\Testing\ArrayCredentialResolver;

abstract class TestCase extends Orchestra
{
    public const BASE = 'https://api.digitax.tech/ke/v2';

    protected ArrayCredentialResolver $resolver;

    protected function getPackageProviders($app): array
    {
        return [DigitaxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('digitax.config_key', 'base64:' . base64_encode(str_repeat('k', 32)));
        $app['config']->set('digitax.retry_sleep_ms', 0);
    }

    protected function useArrayResolver(): ArrayCredentialResolver
    {
        $this->resolver = new ArrayCredentialResolver();
        $this->app->instance(CredentialResolver::class, $this->resolver);

        return $this->resolver;
    }

    protected function credentials(array $overrides = []): DigitaxCredentials
    {
        return new DigitaxCredentials(...array_merge([
            'businessId' => '7',
            'apiKey' => 'test-key',
            'environment' => 'test',
            'digitaxBusinessId' => 'business_01ABC',
            'modules' => ['sales' => true],
            'callbackUrl' => 'https://api.uzapoint.com/v1/callbacks/digitax/tok',
        ], $overrides));
    }

    protected function manager(): DigitaxManager
    {
        return $this->app->make(DigitaxManager::class);
    }
}
