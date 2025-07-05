<?php

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Http\UploadedFile;

interface ProductServiceInterface
{
    public function createProduct(array $data, ?UploadedFile $image = null): Product;
    public function updateProduct(Product $product, array $data, ?UploadedFile $newImage = null, bool $removeImage = false): Product;
    public function deleteProduct(Product $product): bool;
    public function getImageUrl(?string $imagePath): ?string;
}