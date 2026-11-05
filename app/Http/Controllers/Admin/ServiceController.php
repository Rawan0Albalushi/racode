<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Technology;
use App\Services\ActivityLogger;
use App\Services\ContentPublisher;
use App\Services\MediaLibrary;
use App\Support\Blocks;
use App\Support\ContentRules;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use CmsHelpers;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Service::class);
        $term = $this->searchTerm($request);

        return Inertia::render('admin/services/Index', [
            'services' => Service::query()
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $query->where('slug', 'like', "%{$term}%")
                        ->orWhere('name->ar', 'like', "%{$term}%")
                        ->orWhere('name->en', 'like', "%{$term}%");
                }))
                ->orderBy('sort_order')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Service $service) => [
                    'id' => $service->id,
                    'slug' => $service->slug,
                    'name' => $service->name,
                    'status' => $service->status,
                    'sort_order' => $service->sort_order,
                    'has_unpublished_changes' => $service->has_unpublished_changes,
                ]),
            'filters' => ['q' => $term],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Service::class);

        return Inertia::render('admin/services/Form', [
            'service' => null,
            'technologies' => $this->technologies(),
            'media' => $this->mediaOptions(),
            'revisions' => [],
        ]);
    }

    public function store(Request $request, ActivityLogger $activity, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('create', Service::class);
        $data = $this->validated($request);
        $service = new Service(['status' => 'draft', 'sort_order' => (int) Service::query()->max('sort_order') + 1]);
        $this->fill($service, $data);
        $service->save();
        $service->technologies()->sync($data['technology_ids'] ?? []);
        $media->syncUsage($service, [$service->cover_media_id], 'cover');
        $activity->log('service.created', 'Created a service', $service, ['slug' => $service->slug], $request->user());

        return redirect()->route('admin.services.edit', $service)->with('success', __('racode.saved'));
    }

    public function edit(Service $service): Response
    {
        $this->authorize('view', $service);

        return Inertia::render('admin/services/Form', [
            'service' => $this->payload($service),
            'technologies' => $this->technologies(),
            'media' => $this->mediaOptions(),
            'revisions' => $this->revisionList($service),
        ]);
    }

    public function update(Request $request, Service $service, ActivityLogger $activity, ContentPublisher $publisher, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('update', $service);
        $data = $this->validated($request, $service->id);
        $this->fill($service, $data);
        $publisher->saveDraft($service);
        $service->technologies()->sync($data['technology_ids'] ?? []);
        $media->syncUsage($service, [$service->cover_media_id], 'cover');
        $activity->log('service.updated', 'Updated a service draft', $service, ['slug' => $service->slug], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Service $service, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $service);
        $activity->log('service.deleted', 'Deleted a service', null, ['slug' => $service->slug], $request->user());
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', __('racode.deleted'));
    }

    public function publish(Request $request, Service $service, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $service);
        $publisher->publish($service, $request->user());

        return back()->with('success', __('racode.published'));
    }

    public function schedule(Request $request, Service $service, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $service);
        $when = Carbon::parse($request->validate(['scheduled_for' => ['required', 'date', 'after:now']])['scheduled_for']);
        $publisher->schedule($service, $request->user(), $when);

        return back()->with('success', __('racode.scheduled'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('services.update'), 403);

        foreach ($request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:services,id']])['ids'] as $index => $id) {
            Service::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        return back()->with('success', __('racode.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'slug' => ContentRules::slug('services', $id),
            ...ContentRules::bilingual('name', 160),
            ...ContentRules::bilingual('summary', 400),
            ...Blocks::rules('description'),
            'features' => ['nullable', 'array', 'max:20'],
            'features.*.ar' => ['nullable', 'string', 'max:200'],
            'features.*.en' => ['nullable', 'string', 'max:200'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'technology_ids' => ['nullable', 'array'],
            'technology_ids.*' => ['integer', 'exists:technologies,id'],
            ...ContentRules::seo(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Service $service, array $data): void
    {
        $service->applySlug($data['slug']);
        $service->fill([
            'name' => Translations::pair($data['name']),
            'summary' => Translations::pair($data['summary'] ?? []),
            'description' => Blocks::sanitize($data['description'] ?? []),
            'features' => ContentRules::pairs($data['features'] ?? []),
            'cover_media_id' => $data['cover_media_id'] ?? null,
            'seo' => [
                'title' => Translations::pair($data['seo']['title'] ?? []),
                'description' => Translations::pair($data['seo']['description'] ?? []),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Service $service): array
    {
        return [
            'id' => $service->id,
            'slug' => $service->pending_slug ?: $service->slug,
            'public_slug' => $service->slug,
            'status' => $service->status,
            'name' => $service->name,
            'summary' => $service->summary,
            'description' => $service->description ?? [],
            'features' => $service->features ?? [],
            'cover_media_id' => $service->cover_media_id,
            'technology_ids' => $service->technologies()->pluck('technologies.id'),
            'seo' => $service->seo ?? ['title' => ['ar' => '', 'en' => ''], 'description' => ['ar' => '', 'en' => '']],
            'has_unpublished_changes' => $service->has_unpublished_changes,
            'scheduled_for' => optional($service->scheduled_for)?->format('Y-m-d\TH:i'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function technologies(): array
    {
        return Technology::query()->orderBy('sort_order')->get(['id', 'name', 'slug'])->all();
    }
}
