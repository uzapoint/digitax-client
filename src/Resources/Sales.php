<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;
use Uzapoint\Digitax\Support\SaleResult;
use Uzapoint\Digitax\Support\TraderInvoiceNumber;

/**
 * Sales. Every create returns a Sale object (see SaleResult).
 *
 * Common required fields: sale_date (Y-m-d, not in the future),
 * trader_invoice_number (unique across sales and credit notes),
 * payment_type_code, invoice_status_code, items.
 */
class Sales extends Resource
{
    /**
     * POST /sales: items reference registered DigiTax items by id.
     * Item fields: id, quantity, unit_price, total_amount, discount_*,
     * package_unit_quantity, item_description.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/sales', $this->prepare($payload)));
    }

    /**
     * POST /sales-with-items: items carry full item details
     * (item_name, item_class_code, item_bar_code, item_tax_type_code,
     * quantity, unit_price, total_amount, taxable_amount, tax_amount,
     * discount_*, is_stockable, and the DigiTax id).
     *
     * @param  array<string, mixed>  $payload
     */
    public function createWithItems(array $payload): SaleResult
    {
        return SaleResult::from($this->client->post('/sales-with-items', $this->prepare($payload)));
    }

    /**
     * GET /sales/{id}: works for sales and credit notes.
     */
    public function find(string $saleId): SaleResult
    {
        return SaleResult::from($this->client->get('/sales/' . $this->segment($saleId)));
    }

    /**
     * GET /sales. Filters: start_date, end_date (Y-m-d, creation date).
     *
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/sales', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/sales', $query);
    }

    /**
     * GET /sale/{business-id}/{trader-invoice-number}: the documented
     * "offline URL" lookup. Most callers only need the URL itself, built
     * locally by DigitaxClient::offlineUrl().
     *
     * @return array<string, mixed>
     */
    public function findByTraderInvoiceNumber(string $traderInvoiceNumber, ?string $digitaxBusinessId = null): array
    {
        $businessId = $digitaxBusinessId ?? $this->client->credentials->digitaxBusinessId;

        if (!$businessId) {
            throw new \InvalidArgumentException('DigiTax business id is required for the trader invoice lookup');
        }

        return $this->client->get(
            '/sale/' . $this->segment($businessId) . '/' . $this->segment(TraderInvoiceNumber::assert($traderInvoiceNumber))
        );
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
