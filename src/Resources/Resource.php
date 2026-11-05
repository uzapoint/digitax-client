<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\DigitaxClient;
use Uzapoint\Digitax\Support\CursorPage;

abstract class Resource
{
    public function __construct(protected DigitaxClient $client)
    {
    }

    /**
     * Fetch one page of a cursor-paginated list.
     *
     * @param  array<string, mixed>  $query  before, after, page_size (1-20), plus filters
     */
    protected function page(string $path, array $query = []): CursorPage
    {
        return CursorPage::from($this->client->get($path, $query));
    }

    /**
     * Walk every page lazily, following pagination.next as the "after" cursor.
     *
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    protected function cursor(string $path, array $query = [], ?int $maxPages = null): LazyCollection
    {
        return LazyCollection::make(function () use ($path, $query, $maxPages) {
            $query['page_size'] ??= 20;
            $pages = 0;

            do {
                $page = $this->page($path, $query);
                $pages++;

                foreach ($page->data as $row) {
                    yield $row;
                }

                $query['after'] = $page->next;
                unset($query['before']);
            } while ($page->hasMore() && ($maxPages === null || $pages < $maxPages));
        });
    }

    protected function segment(string $id): string
    {
        return rawurlencode($id);
    }
}
