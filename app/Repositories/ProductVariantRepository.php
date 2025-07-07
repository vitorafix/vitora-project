<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductVariantRepositoryInterface;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

class ProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function find(int $id): ?ProductVariant
    {
        return ProductVariant::find($id);
    }

    public function findBy(string $field, mixed $value): ?ProductVariant
    {
        return ProductVariant::where($field, $value)->first();
    }

    public function all(): Collection
    {
        return ProductVariant::all();
    }

    public function create(array $data): ProductVariant
    {
        return ProductVariant::create($data);
    }

    public function update(ProductVariant $productVariant, array $data): ProductVariant
    {
        $productVariant->update($data);
        return $productVariant;
    }

    public function delete(ProductVariant $productVariant): bool
    {
        return $productVariant->delete();
    }

    public function decrementStock(ProductVariant $productVariant, int $quantity): void
    {
        $productVariant->decrement('stock', $quantity);
    }

    public function incrementStock(ProductVariant $productVariant, int $quantity): void
    {
        $productVariant->increment('stock', $quantity);
    }
}
