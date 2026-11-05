<?php

namespace Uzapoint\Digitax\Codes;

final class InvoiceStatus
{
    public const WAIT_FOR_APPROVAL = '01';
    public const APPROVED = '02';
    public const CANCEL_REQUESTED = '03';
    public const CANCELED = '04';
    public const CREDIT_NOTE_GENERATED = '05';
    public const TRANSFERRED = '06';

    public const LABELS = [
        self::WAIT_FOR_APPROVAL => 'Wait for approval',
        self::APPROVED => 'Approved',
        self::CANCEL_REQUESTED => 'Cancel requested',
        self::CANCELED => 'Canceled',
        self::CREDIT_NOTE_GENERATED => 'Credit note generated',
        self::TRANSFERRED => 'Transferred',
    ];
}
