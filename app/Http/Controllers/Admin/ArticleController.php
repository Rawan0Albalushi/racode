<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Tag;
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

class ArticleController extends Controller
{
    use CmsHelpers;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Article::class);
        $term = $this->searchTerm($request);

        return Inertia::render('admin/articles/Index', [
            'articles' => Article::query()->with('category')
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $query->where('slug', 'like', "%{$term}%")
                        ->orWhere('title->ar', 'like', "%{$term}%")
                        ->orWhere('title->en', 'like', "%{$term}%");
                }))
                ->latest()
                ->paginate(20)->withQueryString()
                ->through(fn (Article $article) => [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'status' => $article->status,
                    'is_featured' => $article->is_featured,
                    'category' => $article->category?->name,
                    'has_unpublished_changes' => $article->has_unpublished_changes,
                ]),
            'categories' => ArticleCategory::query()->orderBy('sort_order')->get(),
            'tags' => Tag::query()->orderBy('slug')->get(),
            'filters' => ['q' => $term],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Article::class);

        return Inertia::render('admin/articles/Form', $this->props(null));
    }

    public function store(Request $request, ActivityLogger $activity, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('create', Article::class);
        $data = $this->validated($request);
        $article = new Article(['status' => 'draft', 'author_id' => $request->user()->id]);
        $this->fill($article, $data);
        $article->save();
        $article->tags()->sync($data['tag_ids'] ?? []);
        $media->syncUsage($article, [$article->cover_media_id], 'cover');
        $activity->log('article.created', 'Created an article', $article, ['slug' => $article->slug], $request->user());

        return redirect()->route('admin.articles.edit', $article)->with('success', __('racode.saved'));
    }

    public function edit(Article $article): Response
    {
        $this->authorize('view', $article);

        return Inertia::render('admin/articles/Form', $this->props($article));
    }

    public function update(Request $request, Article $article, ActivityLogger $activity, ContentPublisher $publisher, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('update', $article);
        $data = $this->validated($request, $article->id);
        $this->fill($article, $data);
        $publisher->saveDraft($article);
        $article->tags()->sync($data['tag_ids'] ?? []);
        $media->syncUsage($article, [$article->cover_media_id], 'cover');
        $activity->log('article.updated', 'Updated an article draft', $article, ['slug' => $article->slug], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Article $article, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $article);
        $activity->log('article.deleted', 'Deleted an article', null, ['slug' => $article->slug], $request->user());
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', __('racode.deleted'));
    }

    public function publish(Request $request, Article $article, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $article);
        $publisher->publish($article, $request->user());

        return back()->with('success', __('racode.published'));
    }

    public function schedule(Request $request, Article $article, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $article);
        $when = Carbon::parse($request->validate(['scheduled_for' => ['required', 'date', 'after:now']])['scheduled_for']);
        $publisher->schedule($article, $request->user(), $when);

        return back()->with('success', __('racode.scheduled'));
    }

    public function storeCategory(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('articles.update'), 403);
        $data = $request->validate(['slug' => ContentRules::slug('article_categories', null, false), ...ContentRules::bilingual('name', 120)]);
        $category = ArticleCategory::query()->create([
            'slug' => $data['slug'],
            'name' => Translations::pair($data['name']),
            'sort_order' => (int) ArticleCategory::query()->max('sort_order') + 1,
        ]);
        $activity->log('article_category.created', 'Created an article category', $category, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function storeTag(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('articles.update'), 403);
        $data = $request->validate(['slug' => ContentRules::slug('tags', null, false), ...ContentRules::bilingual('name', 80)]);
        $tag = Tag::query()->create(['slug' => $data['slug'], 'name' => Translations::pair($data['name'])]);
        $activity->log('tag.created', 'Created a tag', $tag, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'slug' => ContentRules::slug('articles', $id),
            'article_category_id' => ['nullable', 'integer', 'exists:article_categories,id'],
            ...ContentRules::bilingual('title', 180),
            ...ContentRules::bilingual('excerpt', 400, false),
            ...Blocks::rules('body'),
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'is_featured' => ['required', 'boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            ...ContentRules::seo(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Article $article, array $data): void
    {
        $article->applySlug($data['slug']);
        $article->fill([
            'article_category_id' => $data['article_category_id'] ?? null,
            'title' => Translations::pair($data['title']),
            'excerpt' => Translations::pair($data['excerpt'] ?? []),
            'body' => Blocks::sanitize($data['body'] ?? []),
            'cover_media_id' => $data['cover_media_id'] ?? null,
            'is_featured' => (bool) $data['is_featured'],
            'seo' => [
                'title' => Translations::pair($data['seo']['title'] ?? []),
                'description' => Translations::pair($data['seo']['description'] ?? []),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(?Article $article): array
    {
        return [
            'article' => $article ? [
                'id' => $article->id,
                'slug' => $article->pending_slug ?: $article->slug,
                'public_slug' => $article->slug,
                'status' => $article->status,
                'article_category_id' => $article->article_category_id,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'body' => $article->body ?? [],
                'cover_media_id' => $article->cover_media_id,
                'is_featured' => $article->is_featured,
                'tag_ids' => $article->tags()->pluck('tags.id'),
                'seo' => $article->seo ?? ['title' => ['ar' => '', 'en' => ''], 'description' => ['ar' => '', 'en' => '']],
                'has_unpublished_changes' => $article->has_unpublished_changes,
                'scheduled_for' => optional($article->scheduled_for)?->format('Y-m-d\TH:i'),
            ] : null,
            'categories' => ArticleCategory::query()->orderBy('sort_order')->get(),
            'tags' => Tag::query()->orderBy('slug')->get(),
            'media' => $this->mediaOptions(),
            'revisions' => $article ? $this->revisionList($article) : [],
        ];
    }
}
