<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

/** @var \Intervention\Image\Image $image */

class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['branch', 'category', 'ratings'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|string|unique:products',
            'name'           => 'required|string',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'branch_id'      => 'required|exists:branches,id',
            'category_id'    => 'nullable|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->processAndStoreImage($request->file('image'), $validated['code']);
        } else {
            $validated['image'] = null;
        }

        return Product::create($validated)
            ->load(['branch', 'category', 'ratings']);
    }

    public function show(Product $product)
    {
        return $product->load(['branch', 'category', 'ratings']);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code'           => 'required|string|unique:products,code,' . $product->id,
            'name'           => 'required|string',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'branch_id'      => 'required|exists:branches,id',
            'category_id'    => 'nullable|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
        ]);

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($product->image) {
                $this->deleteImage($product->image);
            }
            $validated['image'] = $this->processAndStoreImage($request->file('image'), $validated['code']);
        } else {
            // Mantener la imagen actual si no se sube nueva
            $validated['image'] = $product->image;
        }

        // Calcular el stock reservado desde order_items
        $reservedStock = \App\Models\OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->sum('quantity');

        if (isset($validated['stock']) && $validated['stock'] < $reservedStock) {
            return response()->json([
                'error' => 'Cannot reduce stock below reserved quantity (' . $reservedStock . ')'
            ], 400);
        }

        $product->update($validated);

        return $product->load(['branch', 'category', 'ratings']);
    }

    public function destroy(Product $product)
    {
        $hasOrderItems = \App\Models\OrderItem::where('product_id', $product->id)->count();

        if ($hasOrderItems > 0) {
            return response()->json([
                'error' => 'Cannot delete a product with active orders'
            ], 400);
        }

        if ($product->image) {
            $this->deleteImage($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    private function processAndStoreImage($imageFile, $productCode)
    {
        try {
            // Crear nombre único para el archivo
            $fileName = 'product_' . $productCode . '_' . time() . '.webp';
            $filePath = 'products/' . $fileName;
            $fullPath = storage_path('app/public/' . $filePath);

            // Crear directorio si no existe
            Storage::disk('public')->makeDirectory('products');

            // Obtener información de la imagen
            $imageInfo = getimagesize($imageFile->getPathname());
            $mimeType = $imageInfo['mime'];

            // Crear imagen según el tipo
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($imageFile->getPathname());
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($imageFile->getPathname());
                    // Preservar transparencia en PNG
                    imagealphablending($sourceImage, false);
                    imagesavealpha($sourceImage, true);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($imageFile->getPathname());
                    break;
                default:
                    throw new \Exception('Formato de imagen no soportado: ' . $mimeType);
            }

            // Convertir a WebP con calidad del 80%
            $success = imagewebp($sourceImage, $fullPath, 80);

            // Liberar memoria
            imagedestroy($sourceImage);

            if (!$success) {
                throw new \Exception('Error al convertir la imagen a WebP');
            }

            // Retornar URL accesible
            return Storage::url($filePath);
        } catch (\Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            return null;
        }
    }

    private function deleteImage($imageUrl)
    {
        try {
            // Extraer el path del storage de la URL
            $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info('Imagen eliminada: ' . $path);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage());
        }
    }
}
