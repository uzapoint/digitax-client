<?php

namespace Uzapoint\Digitax\Codes;

/**
 * DigiTax transaction status values.
 *
 * Items report COMPLETE; sales and credit notes report COMPLETED. The
 * sales summary also uses DRAFT and SUBMITTED. normalize() folds them into
 * the four values Uzapoint stores.
 */
final class SyncStatus
{
    public const PENDING = 'pending';
    public const COMPLETE = 'complete';
    public const FAILED = 'failed';
    public const UNSYNCED = 'unsynced';

    public static function normalize(?string $digitaxStatus): string
    {
        return match (strtoupper(trim((string) $digitaxStatus))) {
            'COMPLETE', 'COMPLETED' => self::COMPLETE,
            'FAILED' => self::FAILED,
            'PENDING', 'SUBMITTED', 'DRAFT' => self::PENDING,
            // DigiTax omits status when the record synced synchronously.
            '' => self::COMPLETE,
            default => self::PENDING,
        };
    }
}
