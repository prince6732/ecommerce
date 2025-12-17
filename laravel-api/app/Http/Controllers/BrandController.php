<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Exception;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::all();
        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string',
            'description1'  => 'nullable|string',
            'description2'  => 'nullable|string',
            'description3'  => 'nullable|string',
            'image1'        => 'nullable|string|max:255',
            'image2'        => 'nullable|string|max:255',
            'image3'        => 'nullable|string|max:255',
            'status'        => 'required',
        ]);

        $validated['status'] = filter_var($validated['status'], FILTER_VALIDATE_BOOLEAN);

        try {
            $brand = Brand::create($validated);
            return response()->json(['res' => 'success', 'brand' => $brand], 201);
        } catch (Exception $e) {
            return response()->json(['res' => 'error', 'message' => 'Something went wrong.'], 500);
        }
    }

    public function show(Brand $brand)
    {
        $brandData = $brand->toArray();
        $brandData['products_count'] = $brand->products()->where('status', true)->count();

        return response()->json($brandData);
    }

    public function getProducts(Request $request, Brand $brand)
    {
        $perPage = $request->get('per_page', 8);
        $page = $request->get('page', 1);

        $products = $brand->products()
            ->where('status', true)
            ->with(['variants', 'category', 'brand'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $productsData = $products->map(function ($product) {
            $variants = $product->variants;

            $imageUrl = $product->image_url;
            if (!$imageUrl && $variants->isNotEmpty()) {
                $firstVariant = $variants->first();
                $imageUrl = $firstVariant->image_url;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'item_code' => $product->item_code,
                'image_url' => $imageUrl,
                'category' => $product->category,
                'brand' => $product->brand,
                'min_price' => $variants->min('sp') ?? 0,
                'max_price' => $variants->max('sp') ?? 0,
                'total_stock' => $variants->sum('stock'),
                'variants_count' => $variants->count(),
            ];
        });

        $totalProducts = $brand->products()->where('status', true)->count();
        $hasMore = ($page * $perPage) < $totalProducts;

        return response()->json([
            'products' => $productsData,
            'has_more' => $hasMore,
            'total' => $totalProducts,
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string',
            'description1'  => 'nullable|string',
            'description2'  => 'nullable|string',
            'description3'  => 'nullable|string',
            'image1'        => 'nullable|string|max:255',
            'image2'        => 'nullable|string|max:255',
            'image3'        => 'nullable|string|max:255',
            'status'        => 'required',
        ]);

        $exists = Brand::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $brand->id)
            ->exists();

        if ($exists) {
            return response()->json(['res' => 'error', 'message' => 'This brand already exists.'], 422);
        }

        $validated['status'] = filter_var($validated['status'], FILTER_VALIDATE_BOOLEAN);

        try {
            $brand->update($validated);
            return response()->json(['res' => 'success', 'brand' => $brand]);
        } catch (Exception $e) {
            return response()->json(['res' => 'error', 'message' => 'Something went wrong.'], 500);
        }
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->json(['res' => 'success', 'message' => 'Brand deleted successfully']);
    }
}
