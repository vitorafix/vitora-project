<?php

namespace App\Contracts\Repositories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

interface ProductVariantRepositoryInterface
{
    /**
     * Find a product variant by its ID.
     *
     * @param int $id
     * @return \App\Models\ProductVariant|null
     */
    public function find(int $id): ?ProductVariant;

    /**
     * Find a product variant by a specific field and value.
     *
     * @param string $field
     * @param mixed $value
     * @return \App\Models\ProductVariant|null
     */
    public function findBy(string $field, mixed $value): ?ProductVariant;

    /**
     * Get all product variants.
     *
     * @return \Illuminate\Database\Eloquent\Collection<ProductVariant>
     */
    public function all(): Collection;

    /**
     * Create a new product variant.
     *
     * @param array $data
     * @return \App\Models\ProductVariant
     */
    public function create(array $data): ProductVariant;

    /**
     * Update an existing product variant.
     *
     * @param \App\Models\ProductVariant $productVariant
     * @param array $data
     * @return \App\Models\ProductVariant
     */
    public function update(ProductVariant $productVariant, array $data): ProductVariant;

    /**
     * Delete a product variant.
     *
     * @param \App\Models\ProductVariant $productVariant
     * @return bool
     */
    public function delete(ProductVariant $productVariant): bool;

    /**
     * Decrement the stock of a product variant.
     *
     * @param \App\Models\ProductVariant $productVariant
     * @param int $quantity
     * @return void
     */
    public function decrementStock(ProductVariant $productVariant, int $quantity): void;

    /**
     * Increment the stock of a product variant.
     *
     * @param \App\Models\ProductVariant $productVariant
     * @param int $quantity
     * @return void
     */
    public function incrementStock(ProductVariant $productVariant, int $quantity): void;
}

