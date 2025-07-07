<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 * title="OrderResource",
 * description="Order resource model",
 * @OA\Xml(
 * name="OrderResource"
 * )
 * )
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     *
     * @OA\Property(property="id", type="string", example="order_uuid_123"),
     * @OA\Property(property="user_id", type="string", example="user_uuid_456"),
     * @OA\Property(property="total_amount", type="number", format="float", example="99.99"),
     * @OA\Property(property="status", type="string", example="pending"),
     * @OA\Property(property="shipping_address", type="string", example="123 Main St, Anytown"),
     * @OA\Property(property="billing_address", type="string", example="123 Main St, Anytown"),
     * @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
     * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-27T10:00:00Z"),
     * @OA\Property(
     * property="items",
     * type="array",
     * @OA\Items(ref="#/components/schemas/OrderItemResource")
     * )
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}

