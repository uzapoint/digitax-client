<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;
use Uzapoint\Digitax\Support\SaleResult;
use Uzapoint\Digitax\Support\TraderInvoiceNumber;

/**
 * Credit notes against a synced sale.
 *
 * Required: sale_id (DigiTax id of the ORIGINAL sale), return_date
 * (Y-m-d, not in the future), trader_invoice_number (unique across sales
 * and credit notes). Optional: credit_note_reason (01-06),
 * invoice_details, callback_url.
 */
class CreditNotes extends Resource
{
    /**
     * POST /credit-notes: items by DigiTax item id
     * {id, quantity, unit_price, total_amount, discount_*, sale_item_id?}.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/credit-notes', $this->prepare($payload)));
    }

    /**
     * POST /credit-notes-with-barcode: items by item_bar_code.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createWithBarcode(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/credit-notes-with-barcode', $this->prepare($payload)));
    }

    /**
     * POST /credit-notes-express: credits the WHOLE sale, no items. Only
     * valid while no other credit note exists for the sale.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createExpress(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/credit-notes-express', $this->prepare($payload)));
    }

    /**
     * GET /credit-notes
     *
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/credit-notes', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/credit-notes', $query);
    }

    /**
     * Credit notes are sales: GET /sales/{id}.
     */
    public function find(string $creditNoteId): SaleResult
    {
        return $this->client->sales()->find($creditNoteId);
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
