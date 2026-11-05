<?php

namespace Uzapoint\Digitax\Codes;

/**
 * KRA eTIMS tax type codes.
 */
final class TaxType
{
    public const EXEMPT = 'A';
    public const STANDARD = 'B';   // 16% VAT
    public const ZERO_RATED = 'C'; // 0% VAT
    public const NON_VAT = 'D';
    public const REDUCED = 'E';    // 8% VAT

    public const LABELS = [
        self::EXEMPT => 'A - Exempt',
        self::STANDARD => 'B - Standard (16%)',
        self::ZERO_RATED => 'C - Zero-rated (0%)',
        self::NON_VAT => 'D - Non-VAT',
        self::REDUCED => 'E - Reduced (8%)',
    ];

    public const RATES = [
        self::EXEMPT => 0.0,
        self::STANDARD => 16.0,
        self::ZERO_RATED => 0.0,
        self::NON_VAT => 0.0,
        self::REDUCED => 8.0,
    ];

    public static function isValid(?string $code): bool
    {
        return $code !== null && isset(self::LABELS[$code]);
    }

    /**
     * Suggest a code from a percentage rate. Returns null when the rate is
     * ambiguous (0% can be exempt, zero-rated or non-VAT): the caller must
     * ask the user instead of guessing.
     */
    public static function fromRate(float|int|string|null $rate): ?string
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        $rate = round((float) $rate, 2);

        return match (true) {
            abs($rate - 16.0) < 0.01 => self::STANDARD,
            abs($rate - 8.0) < 0.01 => self::REDUCED,
            default => null,
        };
    }
}
