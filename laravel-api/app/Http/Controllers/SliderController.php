<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function index()
    {
        $items = Slider::orderBy('order')->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|string',
            'description' => 'nullable|string',
            'link' => 'nullable|string|max:500',
            'open_in_new_tab' => 'boolean',
            'status' => 'required|boolean',
            'order' => 'nullable|integer|min:1',
        ]);

        $validated['open_in_new_tab'] = $validated['open_in_new_tab'] ?? false;
        $validated['order'] = $validated['order'] ?? 1;

        $slider = Slider::create($validated);

        return response()->json([
            'res' => 'success',
            'message' => 'Slider created successfully',
            'slider' => $slider
        ], 201);
    }

    public function show($id)
    {
        $item = Slider::findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, Slider $slider)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|string|max:500',
            'open_in_new_tab' => 'boolean',
            'order' => 'nullable|integer|min:1',
            'status' => 'required|boolean',
            'image' => 'nullable|string',
        ]);

        $slider->update($validated);

        return response()->json([
            'res' => 'success',
            'message' => 'Slider updated successfully',
            'slider' => $slider
        ]);
    }

    public function destroy(Slider $slider)
    {
        if ($slider->image && $slider->image !== 'default-image.jpg') {
            Storage::disk('public')->delete($slider->image);
        }

        $slider->delete();

        return response()->json([
            'res' => 'success',
            'message' => 'Slider deleted successfully'
        ]);
    }

    public function changeStatus($id)
    {
        $item = Slider::findOrFail($id);
        $item->status = !$item->status;
        $item->save();

        return response()->json([
            'result' => 'success',
            'message' => 'Status updated successfully',
            'status' => $item->status
        ]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:sliders,id'
        ]);

        $ids = $request->input('order', []);

        foreach ($ids as $index => $id) {
            Slider::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json([
            'res' => 'success',
            'message' => 'Slider order updated successfully'
        ]);
    }
}
