<?php

namespace Uzapoint\Digitax\Codes;

/**
 * KRA stock movement codes (DigiTax "Stock: Data Attributes").
 */
final class MovementType
{
    public const INCOMING_IMPORT = '01';
    public const INCOMING_PURCHASE = '02';
    public const INCOMING_RETURN = '03';
    public const INCOMING_STOCK_MOVEMENT = '04';
    public const INCOMING_PROCESSING = '05';
    public const INCOMING_ADJUSTMENT = '06';

    public const OUTGOING_SALE = '11';
    public const OUTGOING_RETURN = '12';
    public const OUTGOING_STOCK_MOVEMENT = '13';
    public const OUTGOING_PROCESSING = '14';
    public const OUTGOING_DISCARDING = '15';
    public const OUTGOING_ADJUSTMENT = '16';

    public static function isIncoming(string $code): bool
    {
        return (int) $code >= 1 && (int) $code <= 6;
    }

    public static function action(string $code): string
    {
        return self::isIncoming($code) ? StockAction::ADD : StockAction::DEDUCT;
    }
}
