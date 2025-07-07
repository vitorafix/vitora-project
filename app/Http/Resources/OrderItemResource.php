<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 * title="OrderItemResource",
 * description="Order item resource model",
 * @OA\Xml(
 * name="OrderItemResource"
 * )
 * )
 */
class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     *
     * @OA\Property(property="id", type="string", example="order_item_uuid_789"),
     * @OA\Property(property="order_id", type="string", example="order_uuid_123"),
     * @OA\Property(property="product_id", type="string", example="product_uuid_abc"),
     * @OA\Property(property="product_name", type="string", example="Sample Product"),
     * @OA\Property(property="quantity", type="integer", example="2"),
     * @OA\Property(property="price", type="number", format="float", example="49.99"),
     * @OA\Property(property="total", type="number", format="float", example="99.98"),
     * @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
     * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z")
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => (int) $this->quantity,
            'price' => (float) $this->price,
            'total' => (float) $this->total,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

