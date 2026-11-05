<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
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
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use CmsHelpers;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);
        $term = $this->searchTerm($request);

        return Inertia::render('admin/projects/Index', [
            'projects' => Project::query()->with('category')
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $query->where('slug', 'like', "%{$term}%")
                        ->orWhere('title->ar', 'like', "%{$term}%")
                        ->orWhere('title->en', 'like', "%{$term}%");
                }))
                ->orderBy('sort_order')
                ->paginate(20)->withQueryString()
                ->through(fn (Project $project) => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->title,
                    'status' => $project->status,
                    'is_featured' => $project->is_featured,
                    'is_demonstration' => $project->is_demonstration,
                    'category' => $project->category?->name,
                    'has_unpublished_changes' => $project->has_unpublished_changes,
                ]),
            'categories' => ProjectCategory::query()->orderBy('sort_order')->get(),
            'filters' => ['q' => $term],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('admin/projects/Form', $this->formProps(null));
    }

    public function store(Request $request, ActivityLogger $activity, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('create', Project::class);
        $data = $this->validated($request);
        $project = new Project([
            'status' => 'draft',
            'is_demonstration' => true,
            'sort_order' => (int) Project::query()->max('sort_order') + 1,
        ]);
        $this->fill($project, $data);
        $project->save();
        $this->syncRelations($project, $data, $media);
        $activity->log('project.created', 'Created a project', $project, ['slug' => $project->slug], $request->user());

        return redirect()->route('admin.projects.edit', $project)->with('success', __('racode.saved'));
    }

    public function edit(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('admin/projects/Form', $this->formProps($project));
    }

    public function update(Request $request, Project $project, ActivityLogger $activity, ContentPublisher $publisher, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $this->validated($request, $project->id);
        $this->fill($project, $data);
        $publisher->saveDraft($project);
        $this->syncRelations($project, $data, $media);
        $activity->log('project.updated', 'Updated a project draft', $project, ['slug' => $project->slug], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Project $project, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $project);
        $activity->log('project.deleted', 'Deleted a project', null, ['slug' => $project->slug], $request->user());
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', __('racode.deleted'));
    }

    public function publish(Request $request, Project $project, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $project);
        $publisher->publish($project, $request->user());

        return back()->with('success', __('racode.published'));
    }

    public function schedule(Request $request, Project $project, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $project);
        $when = Carbon::parse($request->validate(['scheduled_for' => ['required', 'date', 'after:now']])['scheduled_for']);
        $publisher->schedule($project, $request->user(), $when);

        return back()->with('success', __('racode.scheduled'));
    }

    public function storeCategory(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('projects.update'), 403);
        $data = $request->validate([
            'slug' => ContentRules::slug('project_categories', null, false),
            ...ContentRules::bilingual('name', 120),
        ]);
        $category = ProjectCategory::query()->create([
            'slug' => $data['slug'],
            'name' => Translations::pair($data['name']),
            'sort_order' => (int) ProjectCategory::query()->max('sort_order') + 1,
        ]);
        $activity->log('project_category.created', 'Created a project category', $category, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroyCategory(Request $request, ProjectCategory $category, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('projects.delete'), 403);
        $activity->log('project_category.deleted', 'Deleted a project category', null, ['slug' => $category->slug], $request->user());
        $category->delete();

        return back()->with('success', __('racode.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'slug' => ContentRules::slug('projects', $id),
            'project_category_id' => ['nullable', 'integer', 'exists:project_categories,id'],
            ...ContentRules::bilingual('title', 180),
            ...ContentRules::bilingual('summary', 500),
            ...Blocks::rules('description'),
            'challenges' => ['nullable', 'array', 'max:8'],
            'challenges.*.title.ar' => ['nullable', 'string', 'max:180'],
            'challenges.*.title.en' => ['nullable', 'string', 'max:180'],
            'challenges.*.body.ar' => ['nullable', 'string', 'max:1000'],
            'challenges.*.body.en' => ['nullable', 'string', 'max:1000'],
            'solutions' => ['nullable', 'array', 'max:8'],
            'solutions.*.title.ar' => ['nullable', 'string', 'max:180'],
            'solutions.*.title.en' => ['nullable', 'string', 'max:180'],
            'solutions.*.body.ar' => ['nullable', 'string', 'max:1000'],
            'solutions.*.body.en' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*.ar' => ['nullable', 'string', 'max:200'],
            'features.*.en' => ['nullable', 'string', 'max:200'],
            'results' => ['nullable', 'array', 'max:8'],
            'results.*.title.ar' => ['nullable', 'string', 'max:180'],
            'results.*.title.en' => ['nullable', 'string', 'max:180'],
            'results.*.body.ar' => ['nullable', 'string', 'max:1000'],
            'results.*.body.en' => ['nullable', 'string', 'max:1000'],
            'results_verified' => ['required', 'boolean'],
            'is_demonstration' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'gallery_ids' => ['nullable', 'array', 'max:12'],
            'gallery_ids.*' => ['integer', 'exists:media,id'],
            'technology_ids' => ['nullable', 'array'],
            'technology_ids.*' => ['integer', 'exists:technologies,id'],
            ...ContentRules::seo(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Project $project, array $data): void
    {
        $project->applySlug($data['slug']);
        $project->fill([
            'project_category_id' => $data['project_category_id'] ?? null,
            'title' => Translations::pair($data['title']),
            'summary' => Translations::pair($data['summary'] ?? []),
            'description' => Blocks::sanitize($data['description'] ?? []),
            'challenges' => ContentRules::titled($data['challenges'] ?? []),
            'solutions' => ContentRules::titled($data['solutions'] ?? []),
            'features' => ContentRules::pairs($data['features'] ?? []),
            'results' => (bool) $data['results_verified'] ? ContentRules::titled($data['results'] ?? []) : [],
            'results_verified' => (bool) $data['results_verified'],
            'is_demonstration' => (bool) $data['is_demonstration'],
            'is_featured' => (bool) $data['is_featured'],
            'cover_media_id' => $data['cover_media_id'] ?? null,
            'seo' => [
                'title' => Translations::pair($data['seo']['title'] ?? []),
                'description' => Translations::pair($data['seo']['description'] ?? []),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Project $project, array $data, MediaLibrary $media): void
    {
        $project->technologies()->sync($data['technology_ids'] ?? []);
        $gallery = [];

        foreach (array_values($data['gallery_ids'] ?? []) as $index => $id) {
            $gallery[(int) $id] = ['sort_order' => $index];
        }

        $project->gallery()->sync($gallery);
        $media->syncUsage($project, array_filter([$project->cover_media_id, ...($data['gallery_ids'] ?? [])]), 'gallery');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Project $project): array
    {
        return [
            'project' => $project ? [
                'id' => $project->id,
                'slug' => $project->pending_slug ?: $project->slug,
                'public_slug' => $project->slug,
                'status' => $project->status,
                'project_category_id' => $project->project_category_id,
                'title' => $project->title,
                'summary' => $project->summary,
                'description' => $project->description ?? [],
                'challenges' => $project->challenges ?? [],
                'solutions' => $project->solutions ?? [],
                'features' => $project->features ?? [],
                'results' => $project->results ?? [],
                'results_verified' => $project->results_verified,
                'is_demonstration' => $project->is_demonstration,
                'is_featured' => $project->is_featured,
                'cover_media_id' => $project->cover_media_id,
                'gallery_ids' => $project->gallery()->pluck('media.id'),
                'technology_ids' => $project->technologies()->pluck('technologies.id'),
                'seo' => $project->seo ?? ['title' => ['ar' => '', 'en' => ''], 'description' => ['ar' => '', 'en' => '']],
                'has_unpublished_changes' => $project->has_unpublished_changes,
                'scheduled_for' => optional($project->scheduled_for)?->format('Y-m-d\TH:i'),
            ] : null,
            'categories' => ProjectCategory::query()->orderBy('sort_order')->get(),
            'technologies' => Technology::query()->orderBy('name')->get(['id', 'name']),
            'media' => $this->mediaOptions(),
            'revisions' => $project ? $this->revisionList($project) : [],
            'suggested_slug' => Str::slug('demonstration'),
        ];
    }
}
