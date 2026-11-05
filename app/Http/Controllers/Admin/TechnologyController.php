<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Technology;
use App\Services\ActivityLogger;
use App\Support\ContentRules;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()?->can('technologies.view'), 403);

        return Inertia::render('admin/technologies/Index', [
            'technologies' => Technology::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('technologies.update'), 403);
        $data = $request->validate([
            'slug' => ContentRules::slug('technologies', null, false),
            'name' => ['required', 'string', 'max:80'],
            ...ContentRules::bilingual('description', 400, false),
            'is_visible' => ['required', 'boolean'],
        ]);
        $technology = Technology::query()->create([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => Translations::pair($data['description'] ?? []),
            'is_visible' => (bool) $data['is_visible'],
            'sort_order' => (int) Technology::query()->max('sort_order') + 1,
        ]);
        $activity->log('technology.created', 'Created a technology', $technology, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function update(Request $request, Technology $technology, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('technologies.update'), 403);
        $data = $request->validate([
            'slug' => ContentRules::slug('technologies', $technology->id, false),
            'name' => ['required', 'string', 'max:80'],
            ...ContentRules::bilingual('description', 400, false),
            'is_visible' => ['required', 'boolean'],
        ]);
        $technology->update([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => Translations::pair($data['description'] ?? []),
            'is_visible' => (bool) $data['is_visible'],
        ]);
        $activity->log('technology.updated', 'Updated a technology', $technology, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Technology $technology, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('technologies.update'), 403);
        $activity->log('technology.deleted', 'Deleted a technology', null, ['slug' => $technology->slug], $request->user());
        $technology->delete();

        return back()->with('success', __('racode.deleted'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('technologies.update'), 403);

        foreach ($request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:technologies,id']])['ids'] as $index => $id) {
            Technology::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        return back()->with('success', __('racode.saved'));
    }
}
