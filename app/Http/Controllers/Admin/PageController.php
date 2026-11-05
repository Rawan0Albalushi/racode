<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\ActivityLogger;
use App\Services\ContentPublisher;
use App\Support\Blocks;
use App\Support\ContentRules;
use App\Support\Sections;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    use CmsHelpers;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Page::class);
        $term = $this->searchTerm($request);

        $pages = Page::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $query->where('slug', 'like', "%{$term}%")
                        ->orWhere('title->ar', 'like', "%{$term}%")
                        ->orWhere('title->en', 'like', "%{$term}%");
                });
            })
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Page $page) => [
                'id' => $page->id,
                'slug' => $page->slug,
                'pending_slug' => $page->pending_slug,
                'template' => $page->template,
                'title' => $page->title,
                'status' => $page->status,
                'is_system' => $page->is_system,
                'sort_order' => $page->sort_order,
                'has_unpublished_changes' => $page->has_unpublished_changes,
                'scheduled_for' => optional($page->scheduled_for)->toDateTimeString(),
            ]);

        return Inertia::render('admin/pages/Index', [
            'pages' => $pages,
            'filters' => ['q' => $term],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Page::class);

        return Inertia::render('admin/pages/Form', [
            'page' => null,
            'revisions' => [],
        ]);
    }

    public function store(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('create', Page::class);
        $data = $this->validated($request);
        $page = new Page(['template' => 'document', 'status' => 'draft', 'is_system' => false]);
        $page->sort_order = (int) Page::query()->max('sort_order') + 1;
        $this->fill($page, $data);
        $page->save();
        $activity->log('page.created', 'Created a page', $page, ['slug' => $page->slug], $request->user());

        return redirect()->route('admin.pages.edit', $page)->with('success', __('racode.saved'));
    }

    public function edit(Page $page): Response
    {
        $this->authorize('view', $page);

        if ($page->template === 'homepage') {
            return Inertia::render('admin/pages/Builder', [
                'page' => $this->pagePayload($page),
                'sections' => $page->sections()->get(),
                'catalog' => Sections::catalog(),
                'media' => $this->mediaOptions(),
                'revisions' => $this->revisionList($page),
            ]);
        }

        return Inertia::render('admin/pages/Form', [
            'page' => $this->pagePayload($page),
            'revisions' => $this->revisionList($page),
        ]);
    }

    public function update(Request $request, Page $page, ActivityLogger $activity, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('update', $page);
        $data = $this->validated($request, $page->id);
        $this->fill($page, $data);
        $publisher->saveDraft($page);
        $activity->log('page.updated', 'Updated a page draft', $page, ['slug' => $page->slug], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Page $page, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $page);

        if ($page->is_system) {
            return back()->with('error', __('racode.system_page'));
        }

        $activity->log('page.deleted', 'Deleted a page', null, ['slug' => $page->slug], $request->user());
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', __('racode.deleted'));
    }

    public function publish(Request $request, Page $page, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $page);
        $publisher->publish($page, $request->user());

        return back()->with('success', __('racode.published'));
    }

    public function schedule(Request $request, Page $page, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $page);
        $when = Carbon::parse($request->validate([
            'scheduled_for' => ['required', 'date', 'after:now'],
        ])['scheduled_for']);
        $publisher->schedule($page, $request->user(), $when);

        return back()->with('success', __('racode.scheduled'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('pages.update'), 403);
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:pages,id'],
        ])['ids'];

        foreach ($ids as $index => $id) {
            Page::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        return back()->with('success', __('racode.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'slug' => ContentRules::slug('pages', $id),
            ...ContentRules::bilingual('title', 180),
            ...ContentRules::bilingual('excerpt', 400, false),
            ...Blocks::rules('body'),
            ...ContentRules::seo(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Page $page, array $data): void
    {
        $page->applySlug($data['slug']);
        $page->fill([
            'title' => Translations::pair($data['title']),
            'excerpt' => Translations::pair($data['excerpt'] ?? []),
            'body' => Blocks::sanitize($data['body'] ?? []),
            'seo' => [
                'title' => Translations::pair($data['seo']['title'] ?? []),
                'description' => Translations::pair($data['seo']['description'] ?? []),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(Page $page): array
    {
        return [
            'id' => $page->id,
            'slug' => $page->pending_slug ?: $page->slug,
            'public_slug' => $page->slug,
            'template' => $page->template,
            'is_system' => $page->is_system,
            'status' => $page->status,
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'body' => $page->body ?? [],
            'seo' => $page->seo ?? ['title' => ['ar' => '', 'en' => ''], 'description' => ['ar' => '', 'en' => '']],
            'has_unpublished_changes' => $page->has_unpublished_changes,
            'scheduled_for' => optional($page->scheduled_for)?->format('Y-m-d\TH:i'),
        ];
    }
}
