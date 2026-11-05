<?php

namespace Uzapoint\Digitax\Codes;

final class CreditNoteReason
{
    public const MISSING_QUANTITY = '01';
    public const MISSING_DATA = '02';
    public const DAMAGED = '03';
    public const WASTED = '04';
    public const MATERIAL_SHORTAGE = '05';
    public const REFUND = '06';

    public const LABELS = [
        self::MISSING_QUANTITY => 'Missing quantity',
        self::MISSING_DATA => 'Missing data',
        self::DAMAGED => 'Damaged',
        self::WASTED => 'Wasted',
        self::MATERIAL_SHORTAGE => 'Material shortage',
        self::REFUND => 'Refund',
    ];

    public static function isValid(?string $code): bool
    {
        return $code !== null && isset(self::LABELS[$code]);
    }

    public static function orDefault(?string $code): string
    {
        return self::isValid($code) ? $code : self::REFUND;
    }
}
