<?php

namespace Uzapoint\Digitax\Resources;

use DateTimeInterface;

class Reports extends Resource
{
    /**
     * GET /sales-summary?start_date&end_date. Response:
     * {summary: {PENDING|DRAFT|FAILED|COMPLETED|SUBMITTED: {total_sales_count, total_sales_amount}}}
     *
     * @return array<string, mixed>
     */
    public function salesSummary(DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): array
    {
        return $this->client->get('/sales-summary', [
            'start_date' => $this->date($startDate),
            'end_date' => $this->date($endDate),
        ]);
    }

    private function date(DateTimeInterface|string $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
