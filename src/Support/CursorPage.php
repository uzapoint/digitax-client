<?php

namespace Uzapoint\Digitax\Support;

/**
 * One page of a DigiTax list response:
 * {"pagination": {"next", "previous", "page_size"}, "data": [...], "meta": {...}}
 */
final class CursorPage
{
    /**
     * @param  list<array<string, mixed>>  $data
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly array $data,
        public readonly ?string $next,
        public readonly ?string $previous,
        public readonly ?int $pageSize,
        public readonly array $meta = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function from(array $response): self
    {
        $pagination = $response['pagination'] ?? [];
        $data = $response['data'] ?? [];

        // Some list endpoints may return a bare array.
        if (array_is_list($response) && !isset($response['data'])) {
            $data = $response;
            $pagination = [];
        }

        return new self(
            data: is_array($data) ? array_values($data) : [],
            next: ($pagination['next'] ?? null) ?: null,
            previous: ($pagination['previous'] ?? null) ?: null,
            pageSize: isset($pagination['page_size']) ? (int) $pagination['page_size'] : null,
            meta: is_array($response['meta'] ?? null) ? $response['meta'] : [],
        );
    }

    public function hasMore(): bool
    {
        return $this->next !== null && $this->data !== [];
    }
}
