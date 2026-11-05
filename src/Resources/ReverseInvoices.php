<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;
use Uzapoint\Digitax\Support\SaleResult;
use Uzapoint\Digitax\Support\TraderInvoiceNumber;

/**
 * Reverse invoices: the buyer issues the eTIMS invoice on the supplier's
 * behalf. The supplier (Suppliers::create) must be COMPLETED first.
 */
class ReverseInvoices extends Resource
{
    /**
     * POST /reverse-invoices: items by DigiTax item id.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/reverse-invoices', $this->prepare($payload)));
    }

    /**
     * POST /reverse-invoices-with-items. Required: supplier_id,
     * sale_date, trader_invoice_number, items (item_name,
     * item_class_code, item_bar_code, item_tax_type_code, quantity,
     * unit_price, total_amount, discount_rate, discount_amount).
     *
     * @param  array<string, mixed>  $payload
     */
    public function createWithItems(array $payload): SaleResult
    {
        $payload['items'] = array_map(fn (array $item) => $item + [
            // Both are REQUIRED on this endpoint only.
            'discount_rate' => 0,
            'discount_amount' => 0,
        ], $payload['items'] ?? []);

        return SaleResult::from($this->client->post('/reverse-invoices-with-items', $this->prepare($payload)));
    }

    public function find(string $saleId): SaleResult
    {
        return SaleResult::from($this->client->get('/reverse-invoices/' . $this->segment($saleId)));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/reverse-invoices', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/reverse-invoices', $query);
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
