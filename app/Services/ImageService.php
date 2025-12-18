<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImageService
{
    public function processAndStoreImage(UploadedFile $imageFile, string $productCode): ?string
    {
        try {
            $fileName = 'product_' . $productCode . '_' . time() . '.webp';
            $filePath = 'products/' . $fileName;
            $fullPath = storage_path('app/public/' . $filePath);

            Storage::disk('public')->makeDirectory('products');

            $imageInfo = getimagesize($imageFile->getPathname());
            $mimeType = $imageInfo['mime'];

            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($imageFile->getPathname());
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($imageFile->getPathname());
                    imagealphablending($sourceImage, false);
                    imagesavealpha($sourceImage, true);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($imageFile->getPathname());
                    break;
                default:
                    throw new Exception('Formato de imagen no soportado: ' . $mimeType);
            }

            $success = imagewebp($sourceImage, $fullPath, 80);
            imagedestroy($sourceImage);

            if (!$success) {
                throw new Exception('Error al convertir la imagen a WebP');
            }

            return Storage::url($filePath);
        } catch (Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            return null;
        }
    }

    public function validateAndStoreExternalImage(string $imageUrl, string $productCode): ?string
    {
        try {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                throw new Exception('URL de imagen no válida');
            }

            Log::info("Guardando URL externa directamente: {$imageUrl}");
            return $imageUrl;
        } catch (Exception $e) {
            Log::error('Error processing external image: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteImageIfLocal(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        if (
            str_starts_with($imageUrl, '/storage/') ||
            str_contains($imageUrl, 'storage/')
        ) {
            $this->deleteImage($imageUrl);
        }
    }

    private function deleteImage(string $imageUrl): void
    {
        try {
            $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info('Imagen eliminada: ' . $path);
            }
        } catch (Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage());
        }
    }
}
