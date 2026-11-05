<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ActivityLogger;
use App\Services\MediaLibrary;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class MediaController extends Controller
{
    use CmsHelpers;

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('media.view'), 403);
        $term = $this->searchTerm($request);

        return Inertia::render('admin/media/Index', [
            'media' => Media::query()->withCount('usages')
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $query->where('filename', 'like', "%{$term}%")
                        ->orWhere('alt->ar', 'like', "%{$term}%")
                        ->orWhere('alt->en', 'like', "%{$term}%");
                }))
                ->latest()
                ->paginate(24)
                ->withQueryString()
                ->through(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->url,
                    'filename' => $media->filename,
                    'mime' => $media->mime,
                    'size' => $media->size,
                    'width' => $media->width,
                    'height' => $media->height,
                    'alt' => $media->alt,
                    'usages' => $media->usages_count,
                ]),
            'filters' => ['q' => $term],
        ]);
    }

    public function store(Request $request, MediaLibrary $library): RedirectResponse
    {
        abort_unless($request->user()?->can('media.upload'), 403);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'alt' => ['nullable', 'array'],
            'alt.ar' => ['nullable', 'string', 'max:180'],
            'alt.en' => ['nullable', 'string', 'max:180'],
        ]);

        try {
            $library->store($data['file'], Translations::pair($data['alt'] ?? []), $request->user());
        } catch (RuntimeException) {
            return back()->with('error', __('racode.media_rejected'));
        }

        return back()->with('success', __('racode.saved'));
    }

    public function update(Request $request, Media $medium, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('media.upload'), 403);
        $data = $request->validate([
            'alt.ar' => ['nullable', 'string', 'max:180'],
            'alt.en' => ['nullable', 'string', 'max:180'],
        ]);
        $medium->update(['alt' => Translations::pair($data['alt'] ?? $request->input('alt', []))]);
        $activity->log('media.updated', 'Updated media text', $medium, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function replace(Request $request, Media $medium, MediaLibrary $library): RedirectResponse
    {
        abort_unless($request->user()?->can('media.upload'), 403);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        try {
            $library->replace($medium, $data['file'], $request->user());
        } catch (RuntimeException) {
            return back()->with('error', __('racode.media_rejected'));
        }

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Media $medium, MediaLibrary $library, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('media.delete'), 403);

        try {
            $library->delete($medium);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'in_use') {
                return back()->with('error', __('racode.media_in_use'));
            }

            throw $exception;
        }

        $activity->log('media.deleted', 'Deleted media', null, ['id' => $medium->id], $request->user());

        return back()->with('success', __('racode.deleted'));
    }
}
