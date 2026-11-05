<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;

/**
 * Items registered on eTIMS.
 *
 * Required on create: item_class_code, item_type_code, item_name,
 * origin_nation_code, package_unit_code, quantity_unit_code,
 * tax_type_code, default_unit_price. Optional: stock_quantity (opening
 * stock), item_bar_code, levies, callback_url (item.sync event).
 */
class Items extends Resource
{
    /**
     * POST /items. Response: Item {id, etims_item_code, status
     * PENDING|FAILED|COMPLETE, stock_quantity, ...}.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->post('/items', $this->client->withCallback($this->withPrices($payload)));
    }

    /**
     * PUT /items/{id}
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(string $itemId, array $payload): array
    {
        return $this->client->put('/items/' . $this->segment($itemId), $this->withPrices($payload));
    }

    /**
     * GET /items/{id}. Includes stock_quantity and is_stock_item.
     *
     * @return array<string, mixed>
     */
    public function find(string $itemId): array
    {
        return $this->client->get('/items/' . $this->segment($itemId));
    }

    /**
     * Current DigiTax stock for an item, or null when it is not tracked.
     */
    public function stockQuantity(string $itemId): ?float
    {
        $item = $this->find($itemId);

        if (array_key_exists('is_stock_item', $item) && !$item['is_stock_item']) {
            return null;
        }

        return isset($item['stock_quantity']) ? (float) $item['stock_quantity'] : null;
    }

    /**
     * GET /items, one page.
     *
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/items', $query);
    }

    /**
     * Every item, across all pages.
     *
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/items', $query);
    }

    /**
     * The docs name default_unit_price, but the live validator also
     * requires default_price ("default_price: value is required"). Send
     * both whenever one is given.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withPrices(array $payload): array
    {
        $price = $payload['default_unit_price'] ?? $payload['default_price'] ?? null;

        if ($price !== null) {
            $payload['default_unit_price'] ??= $price;
            $payload['default_price'] ??= $price;
        }

        return $payload;
    }
}
