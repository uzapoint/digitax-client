<?php

namespace Uzapoint\Digitax;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Uzapoint\Digitax\Contracts\CredentialResolver;
use Uzapoint\Digitax\Resolvers\DatabaseCredentialResolver;

class DigitaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/digitax.php', 'digitax');

        $this->app->bindIf(CredentialResolver::class, function ($app) {
            $class = $app['config']->get('digitax.resolver') ?: DatabaseCredentialResolver::class;

            return $app->make($class);
        });

        // Not a singleton: the manager is stateless, and binding it fresh
        // lets tests swap the resolver or the Http fake at any time.
        $this->app->bind(DigitaxManager::class, fn ($app) => new DigitaxManager(
            $app->make(CredentialResolver::class),
            $app->make(HttpFactory::class),
            $app['config']->get('digitax', []),
        ));

        $this->app->alias(DigitaxManager::class, 'digitax');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/digitax.php' => config_path('digitax.php'),
            ], 'digitax-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'digitax-migrations');
        }

        if ($this->app['config']->get('digitax.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}
