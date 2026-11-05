<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;
use Uzapoint\Digitax\Support\SaleResult;
use Uzapoint\Digitax\Support\TraderInvoiceNumber;

/**
 * Credit notes against a reverse invoice (sale_id = reverse invoice id).
 */
class ReverseCreditNotes extends Resource
{
    /**
     * POST /reverse-credit-notes: items by DigiTax item id.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/reverse-credit-notes', $this->prepare($payload)));
    }

    /**
     * POST /reverse-credit-notes-with-barcode: items by item_bar_code.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createWithBarcode(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/reverse-credit-notes-with-barcode', $this->prepare($payload)));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/reverse-credit-notes', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/reverse-credit-notes', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function prepare(array $payload): array
    {
        if (isset($payload['trader_invoice_number'])) {
            TraderInvoiceNumber::assert((string) $payload['trader_invoice_number']);
        }

        return $this->client->withCallback($payload);
    }
}
