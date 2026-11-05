<?php

namespace Uzapoint\Digitax\Resources;

/**
 * Businesses: eTIMS info and branches.
 */
class Business extends Resource
{
    /**
     * GET /etims-info. Returns the Branch object, including business_id (the
     * id used in offline URLs), tax_pin, branch_office_id and
     * branch_office_name. Use it to verify an API key.
     *
     * @return array<string, mixed>
     */
    public function etimsInfo(): array
    {
        return $this->client->get('/etims-info');
    }

    /**
     * GET /branches
     *
     * @return list<array<string, mixed>>
     */
    public function branches(): array
    {
        $response = $this->client->get('/branches');

        return array_values($response['data'] ?? (array_is_list($response) ? $response : []));
    }
}
