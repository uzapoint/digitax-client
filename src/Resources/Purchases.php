<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;

/**
 * Purchases pulled from eTIMS: invoices that suppliers issued to this
 * business's PIN. Link their lines to DigiTax items to accept them.
 */
class Purchases extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/purchases', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/purchases', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $purchaseId): array
    {
        return $this->client->get('/purchases/' . $this->segment($purchaseId));
    }

    /**
     * POST /purchases/{id}/link-items
     *
     * @param  array<string, string>|list<array{purchase_item_id: string, item_id: string}>  $links
     *         either [purchase_item_id => item_id] or a list of pairs
     * @return array<string, mixed>
     */
    public function linkItems(string $purchaseId, array $links): array
    {
        $items = array_is_list($links)
            ? $links
            : array_map(
                fn ($purchaseItemId, $itemId) => ['purchase_item_id' => (string) $purchaseItemId, 'item_id' => (string) $itemId],
                array_keys($links),
                $links,
            );

        return $this->client->post('/purchases/' . $this->segment($purchaseId) . '/link-items', ['items' => $items]);
    }
}
