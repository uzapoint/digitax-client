<?php

namespace Uzapoint\Digitax\Codes;

/**
 * KRA payment type codes (DigiTax "Invoice attributes").
 */
final class PaymentType
{
    public const CASH = '01';
    public const CREDIT = '02';
    public const CASH_CREDIT = '03';
    public const BANK_CHECK = '04';
    public const CARD = '05';
    public const MOBILE_MONEY = '06';
    public const OTHER = '07';

    public const LABELS = [
        self::CASH => 'Cash',
        self::CREDIT => 'Credit',
        self::CASH_CREDIT => 'Cash/Credit',
        self::BANK_CHECK => 'Bank check',
        self::CARD => 'Debit & credit card',
        self::MOBILE_MONEY => 'Mobile money',
        self::OTHER => 'Other',
    ];

    /**
     * Map an internal payment method name to a KRA code.
     */
    private const METHOD_MAP = [
        'cash' => self::CASH,
        'credit' => self::CREDIT,
        'cheque' => self::BANK_CHECK,
        'check' => self::BANK_CHECK,
        'bank' => self::BANK_CHECK,
        'bank_transfer' => self::BANK_CHECK,
        'card' => self::CARD,
        'debit_card' => self::CARD,
        'credit_card' => self::CARD,
        'mpesa' => self::MOBILE_MONEY,
        'm-pesa' => self::MOBILE_MONEY,
        'mobile' => self::MOBILE_MONEY,
        'mobile_money' => self::MOBILE_MONEY,
        'airtel' => self::MOBILE_MONEY,
        'airtel_money' => self::MOBILE_MONEY,
    ];

    public static function fromMethod(?string $method): string
    {
        $key = strtolower(trim((string) $method));

        return self::METHOD_MAP[$key] ?? self::OTHER;
    }

    /**
     * Resolve one code for a sale paid with several methods.
     *
     * @param  iterable<string|null>  $methods
     */
    public static function fromMethods(iterable $methods, bool $unpaid = false): string
    {
        if ($unpaid) {
            return self::CREDIT;
        }

        $codes = [];
        foreach ($methods as $method) {
            $codes[self::fromMethod($method)] = true;
        }

        if ($codes === []) {
            return self::CASH;
        }

        if (count($codes) === 1) {
            return array_key_first($codes);
        }

        // KRA only has a combined code for cash + credit.
        if (count($codes) === 2 && isset($codes[self::CASH], $codes[self::CREDIT])) {
            return self::CASH_CREDIT;
        }

        return self::OTHER;
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && isset(self::LABELS[$code]);
    }
}
