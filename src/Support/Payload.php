<?php

namespace Uzapoint\Digitax\Support;

/**
 * Prepares request bodies for DigiTax's strict JSON decoder:
 *  - null values are dropped (optional strings reject JSON null);
 *  - numeric fields given as strings (Eloquent decimal casts) are cast to
 *    float ("120.00" fails with "unexpected byte 34").
 *
 * Only the keys listed in NUMERIC_KEYS are cast. Codes such as "02" or
 * "99010000" stay strings.
 */
final class Payload
{
    public const NUMERIC_KEYS = [
        'quantity', 'unit_price', 'total_amount', 'taxable_amount', 'tax_amount',
        'discount_amount', 'discount_rate', 'package_unit_quantity',
        'default_price', 'default_unit_price', 'stock_quantity', 'service_charge_rate',
    ];

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function clean(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::clean($value);
                continue;
            }

            if (is_string($key) && in_array($key, self::NUMERIC_KEYS, true) && is_numeric($value)) {
                $result[$key] = (float) $value;
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
