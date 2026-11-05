<?php

namespace Uzapoint\Digitax\Codes;

/**
 * Uzapoint eTIMS sync modules (ETIMS_SYNC_MODULES setting).
 * Products and stocks are mandatory whenever eTIMS is enabled.
 */
final class Module
{
    public const PRODUCTS = 'products';
    public const STOCKS = 'stocks';
    public const SALES = 'sales';
    public const PURCHASES = 'purchases';

    public const ALL = [self::PRODUCTS, self::STOCKS, self::SALES, self::PURCHASES];
    public const MANDATORY = [self::PRODUCTS, self::STOCKS];

    /**
     * @param  array<string, mixed>|null  $modules
     * @return array<string, bool>
     */
    public static function normalize(?array $modules): array
    {
        $result = [];
        foreach (self::ALL as $module) {
            $result[$module] = in_array($module, self::MANDATORY, true)
                || filter_var($modules[$module] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $result;
    }
}
