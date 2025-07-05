<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Contracts\ProductServiceInterface; // Ensure this is imported
use App\Exceptions\ImageProcessingException; // Ensure this is imported

class ProductService implements ProductServiceInterface
{
    /**
     * Create a new product.
     *
     * @param array $data Validated data for the product.
     * @param UploadedFile|null $image The uploaded image file.
     * @return Product
     * @throws ImageProcessingException If image upload fails.
     */
    public function createProduct(array $data, ?UploadedFile $image = null): Product
    {
        $imagePath = null;
        if ($image) {
            // Upload and process the main image and its thumbnails
            $imagePath = $this->uploadAndProcessImage($image);
        }

        // Merge the image path with other product data
        return Product::create(array_merge($data, ['image' => $imagePath]));
    }

    /**
     * Update an existing product.
     *
     * @param Product $product The product instance to update.
     * @param array $data Validated data for the product.
     * @param UploadedFile|null $newImage The new uploaded image file.
     * @param bool $removeImage Flag to indicate if the existing image should be removed.
     * @return Product
     * @throws ImageProcessingException If image upload/deletion fails.
     */
    public function updateProduct(Product $product, array $data, ?UploadedFile $newImage = null, bool $removeImage = false): Product
    {
        $currentImagePath = $product->image;

        if ($newImage) {
            // Delete old image and its thumbnails if a new one is uploaded
            if ($currentImagePath) {
                $this->deleteAllImageVersions($currentImagePath);
            }
            $currentImagePath = $this->uploadAndProcessImage($newImage);
        } elseif ($removeImage) {
            // If remove_image flag is true, delete the current image and its thumbnails
            if ($currentImagePath) {
                $this->deleteAllImageVersions($currentImagePath);
            }
            $currentImagePath = null;
        }

        // Update the product with new data and image path
        $product->update(array_merge($data, ['image' => $currentImagePath]));

        return $product;
    }

    /**
     * Delete a product.
     *
     * @param Product $product The product instance to delete.
     * @return bool
     * @throws ImageProcessingException If image deletion fails.
     */
    public function deleteProduct(Product $product): bool
    {
        // Delete associated image and its thumbnails before deleting the product
        if ($product->image) {
            $this->deleteAllImageVersions($product->image);
        }

        // Delete the product (will soft delete if SoftDeletes trait is used)
        return $product->delete();
    }

    /**
     * Uploads and processes the main image and generates thumbnails.
     *
     * @param UploadedFile $image The uploaded image file.
     * @return string The path to the stored main image.
     * @throws ImageProcessingException If image processing or storage fails, or validation fails.
     */
    protected function uploadAndProcessImage(UploadedFile $image): string
    {
        // Get configurable settings from config/image.php
        $maxWidth = config('image.max_width', 1200);
        $maxHeight = config('image.max_height', 800);
        $quality = config('image.quality', 85);
        $allowedMimes = config('image.allowed_mimes', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
        $maxSize = config('image.max_size', 5 * 1024 * 1024); // 5MB
        $disk = config('image.disk', 'public');
        $directory = config('image.directory', 'images/products');
        $thumbnails = config('image.thumbnails', []);
        $autoOrient = config('image.auto_orient', true);
        $stripMetadata = config('image.strip_metadata', true);
        $optimize = config('image.optimize', true);
        $watermarkEnabled = config('image.watermark.enabled', false);
        $watermarkPath = config('image.watermark.path', 'watermark.png');
        $watermarkPosition = config('image.watermark.position', 'bottom-right');
        $watermarkOpacity = config('image.watermark.opacity', 50);
        $backupEnabled = config('image.backup.enabled', false);
        $scanForMalware = config('image.scan_for_malware', false);


        // Validate file MIME type using new config setting
        if (!in_array($image->getMimeType(), $allowedMimes)) {
            throw ImageProcessingException::invalidFormat($image->getMimeType(), $allowedMimes);
        }

        // Validate file size using new static method
        if ($image->getSize() > $maxSize) {
            throw ImageProcessingException::fileTooLarge($image->getSize(), $maxSize);
        }

        // Optional: Malware scanning (requires external library/service)
        if ($scanForMalware) {
            // Implement malware scanning logic here
            // if (MalwareScanner::scan($image->getPathname())) {
            //     throw ImageProcessingException::uploadFailed('فایل حاوی بدافزار است.');
            // }
        }

        // Generate a unique base filename (without extension) for all versions
        $baseFilename = uniqid() . '_' . time();
        $originalExtension = $image->getClientOriginalExtension(); // Keep original extension for reference or specific needs

        try {
            // Process the main image
            $img = Image::make($image->getPathname());

            // Apply general processing options
            if ($autoOrient) {
                $img->orientate(); // Correct image orientation based on EXIF data
            }
            if ($stripMetadata) {
                $img->strip(); // Remove EXIF and other metadata
            }

            // Resize main image
            $img->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Optional: Apply watermark
            if ($watermarkEnabled) {
                // Implement watermark logic here
                // $img->insert(public_path($watermarkPath), $watermarkPosition, 10, 10)->opacity($watermarkOpacity);
            }

            // Optimize image (if not already handled by encode)
            if ($optimize) {
                // Intervention Image's encode method often includes optimization.
                // For more advanced optimization, consider libraries like TinyPNG API or ImageOptim.
            }

            // Encode and store the main image as WebP
            $mainImagePath = $directory . '/' . $baseFilename . '.webp';
            Storage::disk($disk)->put($mainImagePath, $img->encode('webp', $quality));

            // Generate and store thumbnails
            foreach ($thumbnails as $sizeName => $sizeConfig) {
                $thumbImg = Image::make($image->getPathname()); // Re-make from original to avoid cumulative changes

                if ($autoOrient) {
                    $thumbImg->orientate();
                }
                if ($stripMetadata) {
                    $thumbImg->strip();
                }

                $thumbImg->fit($sizeConfig['width'], $sizeConfig['height'], function ($constraint) {
                    $constraint->upsize(); // Only upscale if necessary
                });

                // Optional: Apply watermark to thumbnails if desired
                if ($watermarkEnabled && config('image.watermark.apply_to_thumbnails', false)) {
                     // $thumbImg->insert(public_path($watermarkPath), $watermarkPosition, 5, 5)->opacity($watermarkOpacity);
                }

                $thumbPath = $directory . '/' . $baseFilename . '-' . $sizeName . '.webp';
                Storage::disk($disk)->put($thumbPath, $thumbImg->encode('webp', $sizeConfig['quality']));
            }

            // Optional: Backup original image (e.g., to S3)
            if ($backupEnabled) {
                // Implement backup logic here, possibly dispatching a job
                // Storage::disk(config('image.backup.disk'))->put($directory . '/' . $baseFilename . '_original.' . $originalExtension, file_get_contents($image->getPathname()));
            }

            return $mainImagePath; // Return path to the main image
        } catch (\Exception $e) {
            // Catch specific Intervention Image exceptions if needed, or a general one
            throw ImageProcessingException::uploadFailed($e->getMessage());
        }
    }

    /**
     * Deletes the main image and all its associated thumbnail versions.
     *
     * @param string $mainImagePath The path of the main image to delete.
     * @return bool
     * @throws ImageProcessingException If image deletion fails.
     */
    protected function deleteAllImageVersions(string $mainImagePath): bool
    {
        $disk = config('image.disk', 'public');
        $directory = config('image.directory', 'images/products');
        $thumbnails = config('image.thumbnails', []);

        // Extract base filename (e.g., 'unique_time' from 'images/products/unique_time.webp')
        $pathParts = pathinfo($mainImagePath);
        $baseFilename = str_replace('.webp', '', $pathParts['basename']); // Assuming main image is always webp

        $deleted = false;
        try {
            // Delete main image
            if (Storage::disk($disk)->exists($mainImagePath)) {
                Storage::disk($disk)->delete($mainImagePath);
                $deleted = true;
            }

            // Delete thumbnails
            foreach ($thumbnails as $sizeName => $sizeConfig) {
                $thumbPath = $directory . '/' . $baseFilename . '-' . $sizeName . '.webp';
                if (Storage::disk($disk)->exists($thumbPath)) {
                    Storage::disk($disk)->delete($thumbPath);
                    $deleted = true;
                }
            }

            // Optional: Delete original backup if enabled and exists
            if (config('image.backup.enabled', false)) {
                $originalExtension = pathinfo($mainImagePath, PATHINFO_EXTENSION); // This might not be accurate if main image is always webp
                // A better approach would be to store original extension in DB or infer from baseFilename if needed
                $originalBackupPath = $directory . '/' . $baseFilename . '_original.' . $originalExtension; // Adjust extension if needed
                if (Storage::disk(config('image.backup.disk'))->exists($originalBackupPath)) {
                    Storage::disk(config('image.backup.disk'))->delete($originalBackupPath);
                }
            }

            return $deleted;
        } catch (\Exception $e) {
            throw ImageProcessingException::deleteFailed($e->getMessage());
        }
    }

    /**
     * Get the full public URL of a product image.
     *
     * @param string|null $imagePath The relative path of the image.
     * @return string|null The full URL of the image, or null if no path is provided.
     */
    public function getImageUrl(?string $imagePath): ?string
    {
        $disk = config('image.disk', 'public');
        return $imagePath ? Storage::disk($disk)->url($imagePath) : null;
    }

    /**
     * Get the full public URL of a product thumbnail.
     *
     * @param string $mainImagePath The path of the main image.
     * @param string $sizeName The name of the thumbnail size (e.g., 'small', 'medium').
     * @return string|null The full URL of the thumbnail, or null if not found.
     */
    public function getThumbnailUrl(string $mainImagePath, string $sizeName): ?string
    {
        $disk = config('image.disk', 'public');
        $directory = config('image.directory', 'images/products');

        $pathParts = pathinfo($mainImagePath);
        $baseFilename = str_replace('.webp', '', $pathParts['basename']); // Assuming main image is always webp

        $thumbnailPath = $directory . '/' . $baseFilename . '-' . $sizeName . '.webp';

        if (Storage::disk($disk)->exists($thumbnailPath)) {
            return Storage::disk($disk)->url($thumbnailPath);
        }

        return null;
    }
}
