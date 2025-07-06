<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage; // Import the ProductImage model
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Contracts\ProductServiceInterface;
use App\Exceptions\ImageProcessingException;
use Illuminate\Support\Facades\DB; // For database transactions

class ProductService implements ProductServiceInterface
{
    /**
     * Create a new product.
     * Handles main image and multiple gallery images.
     *
     * @param array $data Validated data for the product.
     * @param UploadedFile|null $mainImage The uploaded main image file.
     * @param array $galleryImages Array of uploaded gallery image files.
     * @return Product
     * @throws ImageProcessingException If image upload fails.
     */
    public function createProduct(array $data, ?UploadedFile $mainImage = null, array $galleryImages = []): Product
    {
        DB::beginTransaction(); // Start a database transaction

        try {
            $mainImagePath = null;
            if ($mainImage) {
                // Upload and process the main image and its thumbnails
                $mainImagePath = $this->uploadAndProcessMainImage($mainImage);
            }

            // Merge the main image path with other product data
            $product = Product::create(array_merge($data, ['image' => $mainImagePath]));

            // Handle gallery images
            if (!empty($galleryImages)) {
                $this->uploadAndAttachGalleryImages($product, $galleryImages);
            }

            DB::commit(); // Commit the transaction
            return $product;
        } catch (ImageProcessingException $e) {
            DB::rollBack(); // Rollback on image processing error
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on any other exception
            throw new ImageProcessingException('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing product.
     * Handles main image, multiple gallery images, and deletion of existing images.
     *
     * @param Product $product The product instance to update.
     * @param array $data Validated data for the product.
     * @param UploadedFile|null $newMainImage The new uploaded main image file.
     * @param bool $removeMainImage Flag to indicate if the existing main image should be removed.
     * @param array $newGalleryImages Array of new uploaded gallery image files.
     * @param array $removeGalleryImageIds Array of IDs of gallery images to be removed.
     * @return Product
     * @throws ImageProcessingException If image upload/deletion fails.
     */
    public function updateProduct(
        Product $product,
        array $data,
        ?UploadedFile $newMainImage = null,
        bool $removeMainImage = false,
        array $newGalleryImages = [],
        array $removeGalleryImageIds = []
    ): Product {
        DB::beginTransaction(); // Start a database transaction

        try {
            $currentMainImagePath = $product->image;

            // Handle main image update/removal
            if ($newMainImage) {
                // Delete old main image and its thumbnails if a new one is uploaded
                if ($currentMainImagePath) {
                    $this->deleteAllMainImageVersions($currentMainImagePath);
                }
                $currentMainImagePath = $this->uploadAndProcessMainImage($newMainImage);
            } elseif ($removeMainImage) {
                // If remove_image flag is true, delete the current main image and its thumbnails
                if ($currentMainImagePath) {
                    $this->deleteAllMainImageVersions($currentMainImagePath);
                }
                $currentMainImagePath = null;
            }

            // Update the product with new data and main image path
            $product->update(array_merge($data, ['image' => $currentMainImagePath]));

            // Handle new gallery images
            if (!empty($newGalleryImages)) {
                $this->uploadAndAttachGalleryImages($product, $newGalleryImages);
            }

            // Handle removal of existing gallery images
            if (!empty($removeGalleryImageIds)) {
                $this->deleteGalleryImages($product, $removeGalleryImageIds);
            }

            DB::commit(); // Commit the transaction
            return $product;
        } catch (ImageProcessingException $e) {
            DB::rollBack(); // Rollback on image processing error
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on any other exception
            throw new ImageProcessingException('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Delete a product.
     * The Product model's 'deleting' event and ProductImage Observer now handle image deletions.
     *
     * @param Product $product The product instance to delete.
     * @return bool
     * @throws ImageProcessingException If product deletion fails.
     */
    public function deleteProduct(Product $product): bool
    {
        // Image deletion (main and gallery) is now handled by the Product model's 'deleting' event
        // and ProductImage Observer, so no explicit image deletion logic is needed here.
        try {
            return $product->delete(); // This will trigger the deleting events
        } catch (\Exception $e) {
            throw new ImageProcessingException('Failed to delete product: ' . $e->getMessage());
        }
    }

    /**
     * Uploads and processes the main image and generates thumbnails.
     *
     * @param UploadedFile $image The uploaded main image file.
     * @return string The path to the stored main image.
     * @throws ImageProcessingException If image processing or storage fails, or validation fails.
     */
    protected function uploadAndProcessMainImage(UploadedFile $image): string
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
     * Uploads and processes a single gallery image.
     *
     * @param UploadedFile $image The uploaded gallery image file.
     * @return string The path to the stored gallery image.
     * @throws ImageProcessingException If image processing or storage fails.
     */
    protected function uploadAndProcessGalleryImage(UploadedFile $image): string
    {
        $quality = config('image.quality', 85);
        $disk = config('image.disk', 'public');
        $directory = config('image.gallery_directory', 'images/products/gallery'); // New config for gallery directory

        // Generate a unique filename
        $filename = uniqid() . '_' . time() . '.webp';
        $imagePath = $directory . '/' . $filename;

        try {
            $img = Image::make($image->getPathname());
            $img->orientate()->strip(); // Orientate and strip metadata
            // Resize gallery images if needed, e.g., to a max width/height for display
            $img->resize(800, 600, function ($constraint) { // Example resize for gallery images
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::disk($disk)->put($imagePath, $img->encode('webp', $quality));
            return $imagePath;
        } catch (\Exception $e) {
            throw ImageProcessingException::uploadFailed('Failed to upload gallery image: ' . $e->getMessage());
        }
    }

    /**
     * Attaches uploaded gallery images to a product.
     *
     * @param Product $product The product instance.
     * @param array $galleryImages Array of UploadedFile instances.
     * @return void
     * @throws ImageProcessingException
     */
    protected function uploadAndAttachGalleryImages(Product $product, array $galleryImages): void
    {
        foreach ($galleryImages as $imageFile) {
            if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                $imagePath = $this->uploadAndProcessGalleryImage($imageFile);
                $product->images()->create(['image_path' => $imagePath]);
            }
        }
    }

    /**
     * Deletes specified gallery images from a product.
     *
     * @param Product $product The product instance.
     * @param array $imageIds Array of ProductImage IDs to delete.
     * @return void
     * @throws ImageProcessingException
     */
    protected function deleteGalleryImages(Product $product, array $imageIds): void
    {
        // Fetch ProductImage models to ensure they belong to this product and trigger observers
        $imagesToDelete = $product->images()->whereIn('id', $imageIds)->get();

        foreach ($imagesToDelete as $image) {
            try {
                $image->delete(); // This will trigger the ProductImageObserver to delete the file
            } catch (\Exception $e) {
                throw new ImageProcessingException('Failed to delete gallery image: ' . $e->getMessage());
            }
        }
    }

    /**
     * Deletes the main image and all its associated thumbnail versions.
     * Renamed from deleteAllImageVersions for clarity.
     *
     * @param string $mainImagePath The path of the main image to delete.
     * @return bool
     * @throws ImageProcessingException If image deletion fails.
     */
    protected function deleteAllMainImageVersions(string $mainImagePath): bool
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
