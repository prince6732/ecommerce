<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::all();
        return response()->json($attributes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|boolean',
        ]);

        $existing = Attribute::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->first();
        if ($existing) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This attribute already exists.'
            ], 422);
        }

        $attribute = Attribute::create($validated);

        return response()->json([
            'res'       => 'success',
            'attribute' => $attribute
        ], 201);
    }

    public function show(Attribute $attribute)
    {
        return response()->json($attribute);
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|boolean',
        ]);

        $exists = Attribute::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $attribute->id)
            ->first();

        if ($exists) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This attribute already exists.'
            ], 422);
        }

        $attribute->update($validated);

        return response()->json([
            'res'       => 'success',
            'attribute' => $attribute
        ]);
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return response()->json([
            'res'     => 'success',
            'message' => 'Attribute deleted successfully'
        ]);
    }
}
