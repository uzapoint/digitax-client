<?php

namespace Uzapoint\Digitax\Support;

use Uzapoint\Digitax\Codes\ReceiptType;
use Uzapoint\Digitax\Codes\SyncStatus;

/**
 * Normalised view of a DigiTax Sale object. Sales, credit notes, reverse
 * invoices and reverse credit notes all return this shape, and the
 * sale.sync callback carries it too.
 */
final class SaleResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(public readonly array $raw)
    {
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function from(array $raw): self
    {
        return new self($raw);
    }

    public function id(): ?string
    {
        return $this->string('id');
    }

    public function status(): string
    {
        return SyncStatus::normalize($this->raw['status'] ?? null);
    }

    public function isComplete(): bool
    {
        return $this->status() === SyncStatus::COMPLETE;
    }

    public function isFailed(): bool
    {
        return $this->status() === SyncStatus::FAILED;
    }

    public function isCreditNote(): bool
    {
        return ($this->raw['receipt_type_code'] ?? null) === ReceiptType::CREDIT_NOTE;
    }

    public function traderInvoiceNumber(): ?string
    {
        return $this->string('trader_invoice_number');
    }

    public function etimsUrl(): ?string
    {
        return $this->string('etims_url');
    }

    public function offlineUrl(): ?string
    {
        return $this->string('offline_url');
    }

    /**
     * URL to encode in the receipt QR code.
     */
    public function qrUrl(): ?string
    {
        return $this->etimsUrl() ?? $this->offlineUrl();
    }

    public function errorMessage(): ?string
    {
        return $this->string('kra_response_message');
    }

    /**
     * Flat array matching the etims_* columns Uzapoint stores on sales,
     * credit notes, voids and goods receipts.
     *
     * @return array<string, mixed>
     */
    public function toColumns(): array
    {
        return [
            'digitax_sale_id' => $this->id(),
            'etims_sync_status' => $this->status(),
            'etims_url' => $this->etimsUrl(),
            'etims_offline_url' => $this->offlineUrl(),
            'etims_receipt_number' => $this->string('receipt_number'),
            'etims_invoice_number' => $this->string('invoice_number'),
            'etims_serial_number' => $this->string('serial_number'),
            'etims_receipt_signature' => $this->string('receipt_signature'),
            'etims_internal_data' => $this->string('internal_data'),
            'etims_sale_detail_url' => $this->string('sale_detail_url'),
            'etims_tax_summary' => $this->raw['sales_tax_summary'] ?? null,
            'etims_error' => $this->errorMessage(),
        ];
    }

    private function string(string $key): ?string
    {
        $value = $this->raw[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
