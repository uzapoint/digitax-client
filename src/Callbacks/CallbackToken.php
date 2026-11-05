<?php

namespace Uzapoint\Digitax\Callbacks;

/**
 * DigiTax does not sign callbacks. Each business gets a random token in its
 * callback URL instead; settings-ms stores a hash of it and resolves it back
 * to the business.
 */
final class CallbackToken
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(24));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * DigiTax rejects localhost and private IP callback hosts, so local and
     * docker URLs are dropped (callbacks are simply not requested).
     */
    public static function isPublicUrl(?string $url): bool
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['fragment'])) {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || !str_contains($host, '.')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return true;
    }
}
