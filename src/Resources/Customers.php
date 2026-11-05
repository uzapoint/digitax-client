<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;

/**
 * Business customers. customer_tin is an 11-character KRA PIN
 * (A or P, 9 digits, a letter).
 */
class Customers extends Resource
{
    public const TIN_PATTERN = '/^[AP]\d{9}[A-Z]$/';

    public static function isValidTin(?string $tin): bool
    {
        return $tin !== null && preg_match(self::TIN_PATTERN, strtoupper(trim($tin))) === 1;
    }

    /**
     * POST /customers {customer_name, customer_tin, email?, phone?}
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->post('/customers', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(string $customerId, array $payload): array
    {
        return $this->client->put('/customers/' . $this->segment($customerId), $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $customerId): array
    {
        return $this->client->get('/customers/' . $this->segment($customerId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/customers', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/customers', $query);
    }
}
