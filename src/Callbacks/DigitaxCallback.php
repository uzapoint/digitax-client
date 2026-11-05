<?php

namespace Uzapoint\Digitax\Callbacks;

use InvalidArgumentException;
use Uzapoint\Digitax\Codes\SyncStatus;
use Uzapoint\Digitax\Support\SaleResult;

/**
 * A DigiTax callback body: {"event": "item.sync"|"sale.sync", "data": {...}}.
 * sale.sync covers sales AND credit notes (receipt_type_code R).
 */
final class DigitaxCallback
{
    public const ITEM_SYNC = 'item.sync';
    public const SALE_SYNC = 'sale.sync';

    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        public readonly string $event,
        public readonly array $data,
    ) {
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(array $body): self
    {
        $event = (string) ($body['event'] ?? '');
        $data = $body['data'] ?? null;

        if (!in_array($event, [self::ITEM_SYNC, self::SALE_SYNC], true) || !is_array($data)) {
            throw new InvalidArgumentException("Unsupported DigiTax callback event '{$event}'");
        }

        return new self($event, $data);
    }

    public function isItemSync(): bool
    {
        return $this->event === self::ITEM_SYNC;
    }

    public function isSaleSync(): bool
    {
        return $this->event === self::SALE_SYNC;
    }

    /**
     * DigiTax id of the item or sale.
     */
    public function id(): ?string
    {
        $id = $this->data['id'] ?? null;

        return $id === null || $id === '' ? null : (string) $id;
    }

    public function status(): string
    {
        return SyncStatus::normalize($this->data['status'] ?? $this->data['queue_status'] ?? null);
    }

    public function sale(): SaleResult
    {
        if (!$this->isSaleSync()) {
            throw new \LogicException('Not a sale.sync callback');
        }

        $data = $this->data;
        // The callback names its status queue_status; SaleResult reads status.
        $data['status'] ??= $data['queue_status'] ?? null;

        return SaleResult::from($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['event' => $this->event, 'data' => $this->data];
    }
}
