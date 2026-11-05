<?php

namespace Uzapoint\Digitax\Support;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Encrypts the DigiTax API key for transport over RabbitMQ with the shared
 * ETIMS_CONFIG_KEY. Each service then re-encrypts it at rest with its own
 * APP_KEY (encrypted model cast).
 */
final class ConfigCipher
{
    private Encrypter $encrypter;

    public function __construct(?string $key = null)
    {
        $key ??= function_exists('config') ? config('digitax.config_key') : null;

        if (!$key) {
            throw new RuntimeException('ETIMS_CONFIG_KEY is not set: cannot encrypt/decrypt eTIMS config.');
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }

        if (strlen($key) !== 32) {
            throw new RuntimeException('ETIMS_CONFIG_KEY must be 32 bytes (use a base64: prefixed value).');
        }

        $this->encrypter = new Encrypter($key, 'AES-256-CBC');
    }

    public function encrypt(string $value): string
    {
        return $this->encrypter->encryptString($value);
    }

    public function decrypt(string $payload): string
    {
        return $this->encrypter->decryptString($payload);
    }
}
