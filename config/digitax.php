<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API base URLs
    |--------------------------------------------------------------------------
    | DigiTax documents one base URL. TEST and LIVE businesses are told apart
    | by their API key. Override per environment here if DigiTax ever issues
    | separate hosts.
    */
    'base_url' => env('DIGITAX_BASE_URL', 'https://api.digitax.tech/ke/v2'),

    'base_urls' => [
        'test' => env('DIGITAX_TEST_BASE_URL'),
        'live' => env('DIGITAX_LIVE_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline receipt URL
    |--------------------------------------------------------------------------
    | https://receipt.dg.tax/r/<business_id>/<trader_invoice_number>
    */
    'offline_url' => env('DIGITAX_OFFLINE_URL', 'https://receipt.dg.tax/r'),

    /*
    |--------------------------------------------------------------------------
    | HTTP behaviour
    |--------------------------------------------------------------------------
    | Retries apply ONLY to idempotent methods (GET, PUT) and only to
    | connection failures or 502/503/504. POST is never retried
    | automatically: a timed-out POST may still have reached DigiTax, and a
    | blind retry would create a duplicate (or a 409 on the trader invoice
    | number). Queue jobs own POST retries.
    */
    'timeout' => (int) env('DIGITAX_TIMEOUT', 20),
    'connect_timeout' => (int) env('DIGITAX_CONNECT_TIMEOUT', 5),
    'retries' => (int) env('DIGITAX_RETRIES', 2),
    'retry_sleep_ms' => (int) env('DIGITAX_RETRY_SLEEP_MS', 500),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Failed requests are logged with the request payload: DigiTax's generic
    | "invalid request data" errors rarely name the field, so the payload is
    | the only way to see it. Payloads never contain the API key.
    */
    'log_channel' => env('DIGITAX_LOG_CHANNEL'),
    'log_failed_payloads' => (bool) env('DIGITAX_LOG_FAILED_PAYLOADS', true),

    /*
    |--------------------------------------------------------------------------
    | Credential resolver
    |--------------------------------------------------------------------------
    | Class implementing Uzapoint\Digitax\Contracts\CredentialResolver.
    | The default reads the local etims_configs mirror that is filled from the
    | settings.etims.updated RabbitMQ event.
    */
    'resolver' => env('DIGITAX_RESOLVER', \Uzapoint\Digitax\Resolvers\DatabaseCredentialResolver::class),

    /*
    |--------------------------------------------------------------------------
    | Config distribution
    |--------------------------------------------------------------------------
    | settings-ms encrypts the API key with this shared key before it is put
    | on RabbitMQ. Every consuming service needs the same value. Generate
    | with: php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
    */
    'config_key' => env('ETIMS_CONFIG_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Database mirror
    |--------------------------------------------------------------------------
    */
    'table' => env('DIGITAX_CONFIG_TABLE', 'etims_configs'),
    'run_migrations' => (bool) env('DIGITAX_RUN_MIGRATIONS', true),
];
