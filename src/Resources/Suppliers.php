<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;

/**
 * Suppliers, used for reverse invoicing. A new supplier is PENDING until
 * it approves the KRA verification token; reverse invoices need COMPLETED.
 */
class Suppliers extends Resource
{
    /**
     * POST /suppliers {supplier_name, supplier_tin, phone, email?}
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->post('/suppliers', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $supplierId): array
    {
        return $this->client->get('/suppliers/' . $this->segment($supplierId));
    }

    public function isApproved(string $supplierId): bool
    {
        return strtoupper((string) ($this->find($supplierId)['status'] ?? '')) === 'COMPLETED';
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/suppliers', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/suppliers', $query);
    }
}
