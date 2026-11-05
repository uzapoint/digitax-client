<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;

/**
 * Import items that KRA customs records against the business.
 */
class Imports extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/imports', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/imports', $query);
    }

    /**
     * PUT /imports/{id}/link {item_id}
     *
     * @return array<string, mixed>
     */
    public function link(string $importId, string $itemId): array
    {
        return $this->client->put('/imports/' . $this->segment($importId) . '/link', ['item_id' => $itemId]);
    }
}
