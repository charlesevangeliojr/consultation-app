<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['siteSettings' => SiteSetting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:1024'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'queue_name_visibility' => ['sometimes', 'in:teacher_choice,hide_all,show_all'],
        ]);

        if (($request->hasFile('logo') && $request->boolean('remove_logo'))
            || ($request->hasFile('favicon') && $request->boolean('remove_favicon'))) {
            throw ValidationException::withMessages([
                'logo' => 'Upload a replacement or select remove for each image, but not both for the same image.',
            ]);
        }

        $settings = SiteSetting::current();
        $oldFiles = [];

        if ($request->hasFile('logo')) {
            $oldFiles[] = $settings->logo_path;
            $settings->logo_path = $request->file('logo')->store('logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $oldFiles[] = $settings->logo_path;
            $settings->logo_path = null;
        }

        if ($request->hasFile('favicon')) {
            $oldFiles[] = $settings->favicon_path;
            $settings->favicon_path = $request->file('favicon')->store('favicons', 'public');
        } elseif ($request->boolean('remove_favicon')) {
            $oldFiles[] = $settings->favicon_path;
            $settings->favicon_path = null;
        }

        $settings->name = $validated['name'];
        if (isset($validated['queue_name_visibility'])) $settings->queue_name_visibility = $validated['queue_name_visibility'];
        $settings->save();

        Storage::disk('public')->delete(array_filter($oldFiles));

        return back()->with('status', 'School settings updated.');
    }
}
