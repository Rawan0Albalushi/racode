<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\ActivityLogger;
use App\Services\ContentPublisher;
use App\Services\MediaLibrary;
use App\Support\Sections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function store(Request $request, Page $page, ActivityLogger $activity, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('update', $page);
        $type = $request->validate([
            'type' => ['required', Rule::in(Sections::types())],
        ])['type'];
        $blank = Sections::blank($type);
        $section = $page->sections()->create([
            ...$blank,
            'sort_order' => (int) $page->sections()->max('sort_order') + 1,
        ]);
        $publisher->saveDraft($page);
        $activity->log('section.created', 'Added a homepage section', $section, ['type' => $type], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function update(Request $request, Page $page, PageSection $section, ActivityLogger $activity, ContentPublisher $publisher, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('update', $page);
        abort_unless($section->page_id === $page->id, 404);
        $data = $request->validate(Sections::rules());
        $clean = Sections::sanitize($section->type, $data['layout'], $data['theme'], $data['content']);
        $section->update([
            'layout' => $clean['layout'],
            'theme' => $clean['theme'],
            'is_visible' => $request->boolean('is_visible'),
            'content' => $clean['content'],
        ]);
        $media->syncUsage($section, [$clean['content']['image_id']], 'section');
        $publisher->saveDraft($page);
        $activity->log('section.updated', 'Updated a homepage section', $section, ['type' => $section->type], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Page $page, PageSection $section, ActivityLogger $activity, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('update', $page);
        abort_unless($section->page_id === $page->id, 404);
        $activity->log('section.deleted', 'Removed a homepage section', null, ['type' => $section->type], $request->user());
        $section->delete();
        $publisher->saveDraft($page);

        return back()->with('success', __('racode.deleted'));
    }

    public function reorder(Request $request, Page $page, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('update', $page);
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ])['ids'];

        foreach (array_values($ids) as $index => $id) {
            PageSection::query()->where('page_id', $page->id)->whereKey($id)->update(['sort_order' => $index]);
        }

        $publisher->saveDraft($page);

        return back()->with('success', __('racode.saved'));
    }
}
