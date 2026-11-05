<?php

namespace Uzapoint\Digitax\Resources;

use InvalidArgumentException;
use Uzapoint\Digitax\Codes\MovementType;
use Uzapoint\Digitax\Codes\StockAction;

class Stock extends Resource
{
    /**
     * PUT /stock/adjust {item_id, quantity, action ADD|DEDUCT, movement_type}.
     * Response: {id, item_code, stock_quantity} (balance AFTER the change).
     *
     * @return array<string, mixed>
     */
    public function adjust(string $itemId, float|int|string $quantity, string $movementType, ?string $action = null): array
    {
        $quantity = abs((float) $quantity);
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock adjustment quantity must be greater than zero');
        }

        $action ??= MovementType::action($movementType);
        if (!in_array($action, [StockAction::ADD, StockAction::DEDUCT], true)) {
            throw new InvalidArgumentException("Invalid stock action {$action}");
        }

        return $this->client->put('/stock/adjust', [
            'item_id' => $itemId,
            'quantity' => $quantity,
            'action' => $action,
            'movement_type' => $movementType,
        ]);
    }

    /**
     * Raw payload form, for callers that already build the body.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function adjustRaw(array $payload): array
    {
        return $this->client->put('/stock/adjust', $payload);
    }

    /**
     * POST /stock/transfer: moves stock to an item of ANOTHER DigiTax
     * business (e.g. a separately registered branch).
     *
     * @return array<string, mixed>  {message}
     */
    public function transfer(string $itemId, string $receivingBusinessId, string $receivingItemId, float|int|string $quantity): array
    {
        return $this->client->post('/stock/transfer', [
            'item_id' => $itemId,
            'business_id' => $receivingBusinessId,
            'receiving_item_id' => $receivingItemId,
            'quantity' => abs((float) $quantity),
        ]);
    }
}
