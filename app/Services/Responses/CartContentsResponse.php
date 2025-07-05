<?php

namespace App\Services\Responses;

use JsonSerializable;

class CartContentsResponse implements JsonSerializable
{
    public array $items;
    public int $totalQuantity;
    public float $totalPrice;

    public function __construct(array $items, int $totalQuantity, float $totalPrice)
    {
        $this->items = $items;
        $this->totalQuantity = $totalQuantity;
        $this->totalPrice = $totalPrice;
    }

    /**
     * Specify data which should be serialized to JSON
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'cartItems' => $this->items, // Changed 'items' to 'cartItems' to match JavaScript expectation
            'totalQuantity' => $this->totalQuantity,
            'totalPrice' => $this->totalPrice,
            'totalItemsInCart' => $this->totalQuantity, // Added for mini-cart compatibility
        ];
    }
}
