<?php

namespace Uzapoint\Digitax\Resources;

use Illuminate\Support\LazyCollection;
use Uzapoint\Digitax\Support\CursorPage;

/**
 * Verify a supplier's eTIMS invoice against KRA.
 */
class InvoiceVerifications extends Resource
{
    /**
     * POST /invoice-verifications {invoice_number, amount?, supplier_pin?, reference_number?}
     *
     * amount is an INTEGER in the DigiTax schema, so it is cast here
     * rather than sent as a float.
     *
     * @return array<string, mixed>  {status, source EXTERNAL|INTERNAL, created_at, data}
     */
    public function verify(string $invoiceNumber, int|float|null $amount = null, ?string $supplierPin = null, ?string $referenceNumber = null): array
    {
        return $this->client->post('/invoice-verifications', [
            'invoice_number' => $invoiceNumber,
            'amount' => $amount === null ? null : (int) round($amount),
            'supplier_pin' => $supplierPin,
            'reference_number' => $referenceNumber,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->page('/invoice-verifications', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function all(array $query = []): LazyCollection
    {
        return $this->cursor('/invoice-verifications', $query);
    }
}
