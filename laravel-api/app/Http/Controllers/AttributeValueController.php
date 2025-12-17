<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index(Request $request)
    {
        $attributeId = $request->get('attribute_id');

        if (!$attributeId) {
            return response()->json([
                'res' => 'error',
                'message' => 'attribute_id is required'
            ], 400);
        }

        $attribute = Attribute::find($attributeId);

        if (!$attribute) {
            return response()->json([
                'res' => 'error',
                'message' => 'Attribute not found'
            ], 404);
        }

        $values = AttributeValue::where('attribute_id', $attributeId)->get();

        return response()->json([
            'res' => 'success',
            'attribute' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'description' => $attribute->description,
                'status' => $attribute->status,
            ],
            'values' => $values
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'attributeValues' => 'required|array|min:1',
            'attributeValues.*.value' => 'required|string|max:100',
            'attributeValues.*.description' => 'nullable|string',
            'attributeValues.*.status' => 'required|boolean',
        ]);

        $attributeId = $validated['attribute_id'];
        $values = $validated['attributeValues'];
        $created = [];

        foreach ($values as $data) {
            $exists = AttributeValue::where('attribute_id', $attributeId)
                ->whereRaw('LOWER(value) = ?', [strtolower($data['value'])])
                ->first();

            if ($exists) {
                continue;
            }

            $created[] = AttributeValue::create([
                'attribute_id' => $attributeId,
                'value' => $data['value'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);
        }

        return response()->json([
            'res' => 'success',
            'message' => count($created)
                ? 'Attribute values created successfully.'
                : 'No new values were added (duplicates skipped).',
            'created' => $created,
        ], 201);
    }

    public function show(AttributeValue $attributeValue)
    {
        $attributeValue->load('attribute');
        return response()->json($attributeValue);
    }

    public function update(Request $request, AttributeValue $attributeValue)
    {
        $validated = $request->validate([
            'value'        => 'required|string|max:100',
            'description'  => 'nullable|string',
            'attribute_id' => 'required|exists:attributes,id',
            'status'       => 'required|boolean',
        ]);

        $exists = AttributeValue::where('attribute_id', $validated['attribute_id'])
            ->whereRaw('LOWER(value) = ?', [strtolower($validated['value'])])
            ->where('id', '!=', $attributeValue->id)
            ->first();

        if ($exists) {
            return response()->json([
                'res'     => 'error',
                'message' => 'This value already exists for the selected attribute.'
            ], 422);
        }

        $attributeValue->update($validated);

        return response()->json([
            'res'   => 'success',
            'value' => $attributeValue
        ]);
    }

    public function destroy(AttributeValue $attributeValue)
    {
        $attributeValue->delete();

        return response()->json([
            'res'     => 'success',
            'message' => 'Attribute value deleted successfully'
        ]);
    }
}
