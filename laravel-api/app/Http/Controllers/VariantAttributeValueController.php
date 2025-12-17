<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class VariantAttributeValueController extends Controller
{
    public function index(Variant $variant)
    {
        $variant->load('attributeValues.attribute');
        return response()->json($variant->attributeValues);
    }

    public function store(Request $request, Variant $variant)
    {
        $validated = $request->validate([
            'attribute_value_ids'   => 'required|array',
            'attribute_value_ids.*' => 'exists:attribute_values,id',
        ]);

        $variant->attributeValues()->syncWithoutDetaching($validated['attribute_value_ids']);

        return response()->json([
            'res'    => 'success',
            'values' => $variant->attributeValues()->with('attribute')->get()
        ]);
    }

    public function destroy(Variant $variant, AttributeValue $attributeValue)
    {
        $variant->attributeValues()->detach($attributeValue->id);

        return response()->json([
            'res'     => 'success',
            'message' => 'Attribute value detached successfully'
        ]);
    }
}
