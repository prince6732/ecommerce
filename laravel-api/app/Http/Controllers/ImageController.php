<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function getFiles(string $filePath)
    {
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([], 200);
        }

        $files = collect(Storage::disk('public')->files($filePath))
            ->map(fn($file) => Storage::url($file));

        return response()->json($files);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'directory' => 'nullable|string'
        ]);

        $file = $request->file('image');
        $directory = $request->input('directory', 'products');

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $files = Storage::disk('public')->files($directory);

        $numbers = collect($files)->map(function ($file) {
            return (int) pathinfo($file, PATHINFO_FILENAME);
        })->filter()->toArray();

        $nextNumber = empty($numbers) ? 1 : (max($numbers) + 1);

        $fileName = str_pad($nextNumber, 15, '0', STR_PAD_LEFT) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($directory, $fileName, 'public');

        return response()->json([
            'isSuccess' => true,
            'result' => Storage::url($path),
            'message' => 'Image uploaded successfully.'
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        $path = str_replace('/storage/', '', $path);

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'File not found.'
            ], 404);
        }

        Storage::disk('public')->delete($path);

        $image = Image::where('path', $path)->first();
        if ($image) {
            $image->delete();
        }

        return response()->json([
            'isSuccess' => true,
            'message' => 'Image deleted successfully.'
        ]);
    }
}
