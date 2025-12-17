<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:50',
            'description'      => 'nullable|string',
            'image'            => 'nullable|string',
            'secondary_image'  => 'nullable|string',
            'link'             => 'nullable|string|max:255',
            'status'           => 'required|boolean',
            'parent_id'        => 'nullable|exists:categories,id',
        ]);

        $existing = Category::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->first();

        if ($existing) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This category already exists.'
            ], 422);
        }

        $category = Category::create($validated);

        return response()->json([
            'res'      => 'success',
            'category' => $category
        ], 201);
    }

    public function show(Category $category)
    {
        $category->load(['children', 'attributes']);
        return response()->json([
            'success' => true,
            'message' => 'Category details fetched successfully.',
            'result'  => $category,
        ]);
    }

    public function getCategoryByIdForProduct(Category $category)
    {
        $category->load([
            'children',
            'attributes' => function ($query) {
                $query->with(['values' => function ($valueQuery) {
                    $valueQuery->active();
                }])
                    ->orderByDesc('is_primary');
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category details fetched successfully.',
            'result'  => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:50',
            'description'      => 'nullable|string',
            'image'            => 'nullable|string',
            'secondary_image'  => 'nullable|string',
            'link'             => 'nullable|string|max:255',
            'status'           => 'required|boolean',
            'parent_id'        => 'nullable|exists:categories,id',
        ]);

        $exists = Category::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $category->id)
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->first();

        if ($exists) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This category already exists.'
            ], 422);
        }

        $category->update($validated);

        return response()->json([
            'res'      => 'success',
            'category' => $category
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        if ($category->secondary_image) {
            Storage::disk('public')->delete($category->secondary_image);
        }

        $category->delete();

        return response()->json([
            'res'     => 'success',
            'message' => 'Category deleted successfully'
        ]);
    }

    public function getSubcategoriesWithProductCounts(Request $request)
    {
        try {
            $limit = $request->query('limit', 20);
            $limit = max(1, min(100, (int)$limit));

            $minProducts = $request->query('min_products', 0);
            $minProducts = max(0, (int)$minProducts);

            $orderBy = $request->query('order_by', 'products_count');
            $validOrderFields = ['products_count', 'name', 'created_at'];
            $orderBy = in_array($orderBy, $validOrderFields) ? $orderBy : 'products_count';

            $orderDirection = $request->query('order_direction', 'desc');
            $orderDirection = in_array($orderDirection, ['asc', 'desc']) ? $orderDirection : 'desc';

            $categories = Category::with(['parent:id,name'])
                ->whereNotNull('parent_id')
                ->withCount(['products' => function ($query) {
                    $query->active();
                }])
                ->having('products_count', '>=', $minProducts)
                ->orderBy($orderBy, $orderDirection)
                ->limit($limit)
                ->get()
                ->map(function ($category) {
                    $products = $category->products()->active()->with('variants')->get();

                    $variantsCount = $products->sum(function ($product) {
                        return $product->variants->count();
                    });

                    $totalStock = $products->sum(function ($product) {
                        return $product->variants->sum('stock');
                    });

                    $prices = $products->flatMap(function ($product) {
                        return $product->variants->pluck('sp')->filter();
                    });

                    $priceStats = [
                        'min_price' => $prices->min() ?: 0,
                        'max_price' => $prices->max() ?: 0,
                        'avg_price' => $prices->avg() ?: 0,
                        'currency' => 'INR'
                    ];

                    $sampleProducts = $products->take(3)->map(function ($product) {
                        $minPrice = $product->variants->min('sp') ?: 0;
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'image_url' => $product->image_url ?: ($product->variants->first() ? $product->variants->first()->image_url : null),
                            'min_price' => $minPrice,
                        ];
                    });

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'image' => $category->image,
                        'secondary_image' => $category->secondary_image,
                        'parent_category' => $category->parent ? [
                            'id' => $category->parent->id,
                            'name' => $category->parent->name,
                        ] : null,
                        'products_count' => $category->products_count,
                        'variants_count' => $variantsCount,
                        'total_stock' => $totalStock,
                        'price_stats' => $priceStats,
                        'sample_products' => $sampleProducts,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                });

            return response()->json([
                'res' => 'success',
                'message' => 'Subcategories with product counts fetched successfully.',
                'data' => [
                    'categories' => $categories,
                    'count' => $categories->count(),
                    'filters' => [
                        'min_products' => $minProducts,
                        'order_by' => $orderBy,
                        'order_direction' => $orderDirection,
                        'limit' => $limit,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'res' => 'error',
                'message' => 'Failed to fetch subcategories with product counts.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
