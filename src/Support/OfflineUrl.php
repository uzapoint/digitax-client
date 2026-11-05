<?php

namespace Uzapoint\Digitax\Support;

/**
 * DigiTax offline receipt URL: works before the sale reaches eTIMS and
 * resolves to the eTIMS verification page once it completes.
 */
final class OfflineUrl
{
    public static function make(?string $digitaxBusinessId, ?string $traderInvoiceNumber, ?string $base = null): ?string
    {
        if (!$digitaxBusinessId || !TraderInvoiceNumber::isValid($traderInvoiceNumber)) {
            return null;
        }

        $base = rtrim($base ?? (function_exists('config') ? (string) config('digitax.offline_url', 'https://receipt.dg.tax/r') : 'https://receipt.dg.tax/r'), '/');

        return "{$base}/{$digitaxBusinessId}/{$traderInvoiceNumber}";
    }
}
