<?php

namespace App\Http\Controllers;

use App\Models\VenueBanner;
use App\Services\TenantMedia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class OwnerBannerController extends Controller
{
    public function store(Request $request, TenantMedia $media)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $data = $request->validate(['title' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:300'], 'link_url' => ['nullable', 'url', 'max:500'], 'image' => ['required', File::image()->max(12 * 1024)], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at']]);
        $path = $media->store($request->user()->tenant, $request->file('image'), 'banners', $venue->name.' '.$data['title']);
        unset($data['image']);
        $venue->banners()->create($data + ['image_path' => $path, 'position' => (int) $venue->banners()->max('position') + 10]);

        return back()->with('status', 'Banner publicado.');
    }

    public function update(Request $request, VenueBanner $banner, TenantMedia $media)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($banner->venue_id === $venue->id, 404);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', File::image()->max(12 * 1024)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'active' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            $newPath = $media->store($request->user()->tenant, $request->file('image'), 'banners', $venue->name.' '.$data['title']);
            $media->delete($banner->image_path, $request->user()->tenant);
            $data['image_path'] = $newPath;
        }

        unset($data['image']);
        $banner->update($data);

        return back()->with('status', 'Banner atualizado.');
    }

    public function destroy(Request $request, VenueBanner $banner, TenantMedia $media)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($banner->venue_id === $venue->id, 404);
        $media->delete($banner->image_path, $request->user()->tenant);
        $banner->delete();

        return back()->with('status', 'Banner removido.');
    }
}
