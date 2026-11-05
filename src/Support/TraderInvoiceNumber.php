<?php

namespace Uzapoint\Digitax\Support;

use InvalidArgumentException;

/**
 * DigiTax trader_invoice_number rules: unique across sales and credit
 * notes, at most 50 characters, only letters, digits, - _ . and , (so it
 * is safe in the offline URL without encoding).
 */
final class TraderInvoiceNumber
{
    public const MAX_LENGTH = 50;
    private const PATTERN = '/^[A-Za-z0-9\-_.,]{1,50}$/';

    public static function isValid(?string $value): bool
    {
        return $value !== null && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Return the value unchanged, or throw. Use this for numbers Uzapoint
     * generates itself (sale_number, credit note numbers): they must match
     * KRA's record exactly, so silently rewriting them would hide a bug.
     */
    public static function assert(?string $value): string
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException(
                "Invalid DigiTax trader_invoice_number '{$value}': use 1-50 of [A-Za-z0-9-_.,]"
            );
        }

        return $value;
    }

    /**
     * Best-effort cleanup for external references: invalid characters become
     * '-', repeats collapse, and the result is cut to 50 characters.
     */
    public static function sanitize(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\-_.,]+/', '-', trim($value)) ?? '';
        $clean = trim(preg_replace('/-{2,}/', '-', $clean) ?? '', '-');

        return substr($clean, 0, self::MAX_LENGTH);
    }

    /**
     * Number for the n-th credit note raised by voiding a sale: SAL-000123-V1.
     */
    public static function forVoid(string $saleNumber, int $sequence): string
    {
        return self::assert("{$saleNumber}-V{$sequence}");
    }
}
