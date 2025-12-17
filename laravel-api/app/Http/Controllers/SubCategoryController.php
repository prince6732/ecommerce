<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubCategoryController extends Controller
{
    public function index(Request $request)
    {
        $parentId = $request->get('parent_id');
        $search = $request->get('search');
        $limit = $request->get('limit', 100);

        $query = Category::with(['attributes' => function ($query) {
            $query->select('attributes.id', 'name')
                ->withPivot(['has_images', 'is_primary']);
        }])->where('parent_id', $parentId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $subcategories = $query->limit($limit)->get();

        $parentCategory = Category::find($parentId);

        return response()->json([
            'res' => 'success',
            'parent_category' => $parentCategory?->name,
            'subcategories' => $subcategories,
            'total' => $subcategories->count(),
        ]);
    }

    public function show(Category $subcategory)
    {
        if (!$subcategory->parent_id) {
            return response()->json([
                'res' => 'error',
                'message' => 'This is not a subcategory.',
            ], 400);
        }

        $subcategory->load(['parent', 'attributes' => function ($query) {
            $query->withPivot(['has_images', 'is_primary']);
        }]);

        return response()->json([
            'res' => 'success',
            'subcategory' => $subcategory,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('attributes') && is_string($request->attributes)) {
            $request->merge(['attributes' => json_decode($request->attributes, true)]);
        }

        if (is_array($request->attributes)) {
            $normalized = array_map(function ($attr) {
                return [
                    'AttributeId' => $attr['AttributeId'] ?? null,
                    'HasImages'   => $attr['HasImages'] ?? false,
                    'IsPrimary'   => $attr['IsPrimary'] ?? ($attr['isPrimary'] ?? false),
                ];
            }, $request->attributes);
            $request->merge(['attributes' => $normalized]);
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:50',
            'description'     => 'nullable|string',
            'image'           => 'nullable|string',
            'secondary_image' => 'nullable|string',
            'link'            => 'nullable|string|max:255',
            'status'          => 'required|boolean',
            'parent_id'       => 'required|exists:categories,id',
            'attributes'      => 'nullable|array',
            'attributes.*.AttributeId' => 'required|exists:attributes,id',
            'attributes.*.HasImages'   => 'required|boolean',
            'attributes.*.IsPrimary'   => 'nullable|boolean',
        ]);

        if (Category::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('parent_id', $validated['parent_id'])
            ->exists()
        ) {
            return response()->json([
                'res' => 'error',
                'message' => 'This subcategory already exists.',
            ], 422);
        }

        if (!empty($validated['attributes'])) {
            $primaryCount = collect($validated['attributes'])->where('IsPrimary', true)->count();
            if ($primaryCount > 1) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Only one attribute can be marked as primary.',
                ], 422);
            }
        }

        $subcategory = Category::create($validated);

        if (!empty($validated['attributes'])) {
            $pivotData = [];
            foreach ($validated['attributes'] as $attr) {
                $pivotData[$attr['AttributeId']] = [
                    'has_images' => $attr['HasImages'],
                    'is_primary' => $attr['IsPrimary'],
                ];
            }
            $subcategory->attributes()->sync($pivotData);
        }

        $subcategory->load('attributes');

        return response()->json([
            'res' => 'success',
            'subcategory' => $subcategory,
        ], 201);
    }

    public function update(Request $request, Category $subcategory)
    {
        if ($request->has('attributes') && is_string($request->attributes)) {
            $request->merge(['attributes' => json_decode($request->attributes, true)]);
        }

        if (is_array($request->attributes)) {
            $normalized = array_map(function ($attr) {
                return [
                    'AttributeId' => $attr['AttributeId'] ?? null,
                    'HasImages'   => $attr['HasImages'] ?? false,
                    'IsPrimary'   => $attr['IsPrimary'] ?? false,
                ];
            }, $request->attributes);
            $request->merge(['attributes' => $normalized]);
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:50',
            'description'     => 'nullable|string',
            'image'           => 'nullable|string',
            'secondary_image' => 'nullable|string',
            'link'            => 'nullable|string|max:255',
            'status'          => 'required|boolean',
            'parent_id'       => 'required|exists:categories,id',
            'attributes'      => 'nullable|array',
            'attributes.*.AttributeId' => 'required|exists:attributes,id',
            'attributes.*.HasImages'   => 'required|boolean',
            'attributes.*.IsPrimary'   => 'nullable|boolean',
        ]);

        if (Category::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('parent_id', $validated['parent_id'])
            ->where('id', '!=', $subcategory->id)
            ->exists()
        ) {
            return response()->json([
                'res' => 'error',
                'message' => 'This subcategory already exists.',
            ], 422);
        }

        if (!empty($validated['attributes'])) {
            $primaryCount = collect($validated['attributes'])->where('IsPrimary', true)->count();
            if ($primaryCount > 1) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Only one attribute can be marked as primary.',
                ], 422);
            }
        }

        $subcategory->update($validated);

        $pivotData = [];
        if (!empty($validated['attributes'])) {
            foreach ($validated['attributes'] as $attr) {
                $pivotData[$attr['AttributeId']] = [
                    'has_images' => $attr['HasImages'],
                    'is_primary' => $attr['IsPrimary'] ?? false,
                ];
            }
        }
        $subcategory->attributes()->sync($pivotData);

        $subcategory->load('attributes');

        return response()->json([
            'res' => 'success',
            'subcategory' => $subcategory,
        ]);
    }

    public function destroy(Category $subcategory)
    {
        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        if ($subcategory->secondary_image) {
            Storage::disk('public')->delete($subcategory->secondary_image);
        }

        $subcategory->delete();

        return response()->json([
            'res' => 'success',
            'message' => 'Subcategory deleted successfully.',
        ]);
    }
}
