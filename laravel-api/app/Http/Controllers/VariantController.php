<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function index()
    {
        $variants = Variant::with('product')->get();
        return response()->json($variants);
    }

    public function store(Request $request)
    {
        if ($request->has('image_json') && is_string($request->image_json)) {
            $request->merge([
                'image_json' => json_decode($request->image_json, true) ?? [],
            ]);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:150',
            'sku'  => 'required|string|max:50|unique:variants,sku',
            'stock'       => 'nullable|integer|min:0',
            'mrp'         => 'required|numeric|min:0',
            'sp'          => 'required|numeric|min:0',
            'bp'          => 'required|numeric|min:0',
            'image_url'   => 'nullable|mimes:jpeg,png,jpg,gif,webp,avif|max:6048',
            'image_json'  => 'nullable|array',
            'product_id'  => 'required|exists:products,id',
            'status'      => 'required|boolean',
        ]);

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $request->file('image_url')
                ->store('uploads/variants', 'public');
        }

        if ($request->hasFile('image_json')) {
            $imageFiles = $request->file('image_json');
            $imagePaths = [];
            foreach ($imageFiles as $file) {
                $imagePaths[] = $file->store('uploads/variants', 'public');
            }
            $validated['image_json'] = $imagePaths;
        }

        $exists = Variant::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($exists) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This variant already exists for the selected product.'
            ], 422);
        }

        $variant = Variant::create($validated);

        return response()->json([
            'res'     => 'success',
            'variant' => $variant
        ], 201);
    }

    public function update(Request $request, Variant $variant)
    {
        if ($request->has('image_json') && is_string($request->image_json)) {
            $request->merge([
                'image_json' => json_decode($request->image_json, true) ?? [],
            ]);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:150',
            'sku'  => 'required|string|max:50|unique:variants,sku,' . $variant->id,
            'stock'       => 'nullable|integer|min:0',
            'mrp'         => 'required|numeric|min:0',
            'sp'          => 'required|numeric|min:0',
            'bp'          => 'required|numeric|min:0',
            'image_url'   => 'nullable|mimes:jpeg,png,jpg,gif,webp,avif|max:6048',
            'image_json'  => 'nullable|array',
            'product_id'  => 'required|exists:products,id',
            'status'      => 'required|boolean',
        ]);

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $request->file('image_url')
                ->store('uploads/variants', 'public');
        }

        if ($request->hasFile('image_json')) {
            $imageFiles = $request->file('image_json');
            $imagePaths = [];
            foreach ($imageFiles as $file) {
                $imagePaths[] = $file->store('uploads/variants', 'public');
            }
            $validated['image_json'] = $imagePaths;
        }

        $exists = Variant::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])
            ->where('product_id', $validated['product_id'])
            ->where('id', '!=', $variant->id)
            ->first();

        if ($exists) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This variant already exists for the selected product.'
            ], 422);
        }

        $variant->update($validated);

        return response()->json([
            'res'     => 'success',
            'variant' => $variant
        ]);
    }

    public function show(Variant $variant)
    {
        $variant->load(['product.brand', 'product.category']);
        return response()->json([
            'success' => true,
            'message' => 'Variant details fetched successfully.',
            'data' => $variant,
        ]);
    }

    public function destroy(Variant $variant)
    {
        $variant->delete();

        return response()->json([
            'res'     => 'success',
            'message' => 'Variant deleted successfully'
        ]);
    }
}
