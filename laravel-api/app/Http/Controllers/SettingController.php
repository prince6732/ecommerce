<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all();
        return response()->json($settings);
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(Setting $setting) {}

    public function edit(Setting $setting) {}

    public function update(Request $request, Setting $setting)
    {
        $settings = $request->except('_token', '_method');
        foreach ($settings as $key => $value) {
            switch ($key) {
                case ('logo'):
                    if ($request->croppedImage != null) {
                        $extension = explode('/', mime_content_type($request->croppedImage))[1];
                        $imageName = "logo-" . now()->timestamp . "." . $extension;
                        Storage::disk('public')->put(
                            $imageName,
                            file_get_contents($request->croppedImage)
                        );
                        $setting = Setting::where('key', $key)->first();
                        $setting->value = $imageName;
                        $setting->save();
                    }
                    break;

                case ('fav_icon'):
                    if ($request->croppedImage != null) {
                        $extension = explode('/', mime_content_type($request->croppedImage))[1];
                        $imageName = "favicon-" . now()->timestamp . "." . $extension;
                        Storage::disk('public')->put(
                            $imageName,
                            file_get_contents($request->croppedImage)
                        );
                        $setting = Setting::where('key', $key)->first();
                        $setting->value = $imageName;
                        $setting->save();
                    }
                    break;

                default:
                    $setting = Setting::where('key', $key)->first();
                    if ($setting) {
                        $setting->value = $value ?? '';
                        $setting->save();
                    }
                    break;
            }
        }

        return response()->json([
            'result' => 'success',
            'message' => 'Setting updated successfully',
            'setting' => $setting
        ]);
    }

    public function destroy(Setting $setting) {}
}
