<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\Media;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\Technology;
use App\Support\Locale;
use App\Support\Translations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PublicContent
{
    /** @var array<int, array<string, mixed>|null> */
    private array $media = [];

    public function __construct(private SettingsRepository $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function home(bool $preview): array
    {
        $page = Page::query()->where('template', 'homepage')->first();
        $sections = [];

        if ($page) {
            $source = $preview
                ? $page->sections->map(fn ($section) => $section->only(['id', 'type', 'layout', 'theme', 'is_visible', 'sort_order', 'content']))->all()
                : ($page->publishedArray()['sections'] ?? []);

            $sections = collect($source)
                ->filter(fn ($section) => ($section['is_visible'] ?? false) === true)
                ->sortBy('sort_order')
                ->map(fn ($section) => $this->section($section))
                ->values()
                ->all();
        }

        return [
            'previewing' => $preview,
            'available' => $sections !== [],
            'sections' => $sections,
            'services' => $this->serviceCards(),
            'projects' => $this->projectCards(),
            'technologies' => $this->technologyCards(),
            'faqs' => $this->faqs('home'),
            'seo' => $this->seo(
                $this->text($page?->publishedArray()['seo']['title'] ?? null, $this->settings->publicBrand()['tagline']),
                $this->text($page?->publishedArray()['seo']['description'] ?? null, ''),
                $this->organization(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function document(string $template, ?string $slug = null, bool $preview = false, bool $customOnly = false): array
    {
        $query = Page::query()->where('template', $template);

        if ($slug) {
            $query->where('slug', $slug);
        }

        if ($customOnly) {
            $query->where('is_system', false);
        }

        $page = $query->firstOrFail();
        $source = $preview ? [
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'body' => $page->body,
            'seo' => $page->seo,
        ] : $page->publishedArray();

        if (! $preview && $source === []) {
            abort(404);
        }

        $localized = Translations::localize($source);

        return [
            'previewing' => $preview,
            'page' => [
                'slug' => $page->slug,
                'title' => $localized['title'] ?? '',
                'excerpt' => $localized['excerpt'] ?? '',
                'body' => $localized['body'] ?? [],
            ],
            'seo' => $this->seo(
                $this->text($source['seo']['title'] ?? null, (string) ($localized['title'] ?? 'RACODE')),
                $this->text($source['seo']['description'] ?? null, (string) ($localized['excerpt'] ?? '')),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function servicesIndex(bool $preview): array
    {
        $intro = $this->optionalIntro('services_index', $preview);

        return [
            'previewing' => $preview,
            'intro' => $intro['page'],
            'services' => $this->serviceCards(detailed: true),
            'seo' => $intro['seo'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function service(string $slug, bool $preview): array
    {
        $service = Service::query()->where('slug', $slug)->firstOrFail();
        $source = $this->payload($service, $preview);

        if ($source === []) {
            abort(404);
        }

        $localized = Translations::localize($source);

        return [
            'previewing' => $preview,
            'service' => [
                'slug' => $service->slug,
                'name' => $localized['name'] ?? '',
                'summary' => $localized['summary'] ?? '',
                'description' => $localized['description'] ?? [],
                'features' => $localized['features'] ?? [],
                'cover' => $this->mediaCard($source['cover_media_id'] ?? null),
                'technologies' => $this->technologyCards($source['technology_ids'] ?? []),
            ],
            'seo' => $this->seo(
                $this->text($source['seo']['title'] ?? null, (string) ($localized['name'] ?? '')),
                $this->text($source['seo']['description'] ?? null, (string) ($localized['summary'] ?? '')),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectsIndex(?string $category, bool $preview): array
    {
        $intro = $this->optionalIntro('work_index', $preview);
        $categories = ProjectCategory::query()->orderBy('sort_order')->get()->map(fn (ProjectCategory $category) => [
            'slug' => $category->slug,
            'name' => Translations::localize($category->name),
        ])->all();

        return [
            'previewing' => $preview,
            'intro' => $intro['page'],
            'category' => $category,
            'categories' => $categories,
            'projects' => $this->projectCards($category),
            'seo' => $intro['seo'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function project(string $slug, bool $preview): array
    {
        $project = Project::query()->where('slug', $slug)->firstOrFail();
        $source = $this->payload($project, $preview);

        if ($source === []) {
            abort(404);
        }

        $localized = Translations::localize($source);
        $category = isset($source['project_category_id'])
            ? ProjectCategory::query()->find($source['project_category_id'])
            : null;

        return [
            'previewing' => $preview,
            'project' => [
                'slug' => $project->slug,
                'title' => $localized['title'] ?? '',
                'summary' => $localized['summary'] ?? '',
                'description' => $localized['description'] ?? [],
                'challenges' => $localized['challenges'] ?? [],
                'solutions' => $localized['solutions'] ?? [],
                'features' => $localized['features'] ?? [],
                'results' => ($source['results_verified'] ?? false) ? ($localized['results'] ?? []) : [],
                'results_verified' => (bool) ($source['results_verified'] ?? false),
                'is_demonstration' => (bool) ($source['is_demonstration'] ?? true),
                'cover' => $this->mediaCard($source['cover_media_id'] ?? null),
                'gallery' => collect($source['gallery_ids'] ?? [])->map(fn ($id) => $this->mediaCard((int) $id))->filter()->values()->all(),
                'technologies' => $this->technologyCards($source['technology_ids'] ?? []),
                'category' => $category ? [
                    'slug' => $category->slug,
                    'name' => Translations::localize($category->name),
                ] : null,
            ],
            'seo' => $this->seo(
                $this->text($source['seo']['title'] ?? null, (string) ($localized['title'] ?? '')),
                $this->text($source['seo']['description'] ?? null, (string) ($localized['summary'] ?? '')),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function technologies(bool $preview): array
    {
        $intro = $this->optionalIntro('technologies_index', $preview);

        return [
            'previewing' => $preview,
            'intro' => $intro['page'],
            'technologies' => Technology::query()->where('is_visible', true)->orderBy('sort_order')->get()->map(fn (Technology $technology) => [
                'slug' => $technology->slug,
                'name' => $technology->name,
                'description' => Translations::localize($technology->description),
            ])->all(),
            'seo' => $intro['seo'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function articlesIndex(?string $category, bool $preview): array
    {
        $intro = $this->optionalIntro('insights_index', $preview);
        $categoryModel = $category ? ArticleCategory::query()->where('slug', $category)->first() : null;

        $articles = Article::query()->visible()->latest('published_at')->get()
            ->filter(function (Article $article) use ($categoryModel, $category) {
                if (! $category) {
                    return true;
                }

                return $categoryModel && ($article->publishedArray()['article_category_id'] ?? null) === $categoryModel->id;
            })
            ->map(function (Article $article) {
                $source = $article->publishedArray();
                $localized = Translations::localize($source);
                $itemCategory = isset($source['article_category_id'])
                    ? ArticleCategory::query()->find($source['article_category_id'])
                    : null;

                return [
                    'slug' => $article->slug,
                    'title' => $localized['title'] ?? '',
                    'excerpt' => $localized['excerpt'] ?? '',
                    'cover' => $this->mediaCard($source['cover_media_id'] ?? null),
                    'is_featured' => (bool) ($source['is_featured'] ?? false),
                    'published_at' => optional($article->published_at)->toDateString(),
                    'category' => $itemCategory ? Translations::localize($itemCategory->name) : null,
                    'category_slug' => $itemCategory?->slug,
                ];
            })->values()->all();

        return [
            'previewing' => $preview,
            'intro' => $intro['page'],
            'category' => $category,
            'categories' => ArticleCategory::query()->orderBy('sort_order')->get()->map(fn (ArticleCategory $item) => [
                'slug' => $item->slug,
                'name' => Translations::localize($item->name),
            ])->all(),
            'articles' => $articles,
            'seo' => $intro['seo'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function article(string $slug, bool $preview): array
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();
        $source = $this->payload($article, $preview);

        if ($source === []) {
            abort(404);
        }

        $localized = Translations::localize($source);
        $category = isset($source['article_category_id']) ? ArticleCategory::query()->find($source['article_category_id']) : null;

        return [
            'previewing' => $preview,
            'article' => [
                'slug' => $article->slug,
                'title' => $localized['title'] ?? '',
                'excerpt' => $localized['excerpt'] ?? '',
                'body' => $localized['body'] ?? [],
                'cover' => $this->mediaCard($source['cover_media_id'] ?? null),
                'published_at' => optional($article->published_at)->toDateString(),
                'category' => $category ? [
                    'slug' => $category->slug,
                    'name' => Translations::localize($category->name),
                ] : null,
            ],
            'seo' => $this->seo(
                $this->text($source['seo']['title'] ?? null, (string) ($localized['title'] ?? '')),
                $this->text($source['seo']['description'] ?? null, (string) ($localized['excerpt'] ?? '')),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contact(bool $preview): array
    {
        $intro = $this->optionalIntro('contact', $preview);

        return [
            'previewing' => $preview,
            'intro' => $intro['page'],
            'project_types' => config('racode.project_types'),
            'started_at' => now()->timestamp,
            'seo' => $intro['seo'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(string $location): array
    {
        $menus = Cache::remember('site.navigation', 300, function (): array {
            return NavigationItem::query()
                ->with('page')
                ->where('is_visible', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('location')
                ->map(fn (Collection $items) => $items->map(fn (NavigationItem $item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'path' => $this->navigationPath($item),
                    'external' => $item->open_in_new_tab,
                ])->values()->all())
                ->all();
        });

        return collect($menus[$location] ?? [])->map(function (array $item) {
            $item['label'] = Translations::localize($item['label']);

            return $item;
        })->all();
    }

    public function forgetNavigation(): void
    {
        Cache::forget('site.navigation');
    }

    /**
     * @param  array<string, mixed>|null  $seo
     */
    public function seo(mixed $title, mixed $description, ?array $jsonLd = null): array
    {
        $brand = $this->settings->publicBrand();
        $titleText = $this->text($title, $brand['seo']['title'] ?: 'RACODE');
        $descriptionText = $this->text($description, $brand['seo']['description'] ?: $brand['tagline']);

        if (! str_contains($titleText, 'RACODE') && ! str_contains($titleText, 'راكود')) {
            $titleText .= ' — RACODE';
        }

        return [
            'title' => $titleText,
            'description' => $descriptionText,
            'canonical' => url()->current(),
            'alternates' => [
                'ar' => Locale::switchUrl('ar'),
                'en' => Locale::switchUrl('en'),
                'x-default' => Locale::switchUrl('ar'),
            ],
            'image' => url('/brand/og.png'),
            'locale' => app()->getLocale() === 'ar' ? 'ar_OM' : 'en_US',
            'json_ld' => $jsonLd,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serviceCards(bool $detailed = false): array
    {
        return Service::query()->visible()->orderBy('sort_order')->get()->map(function (Service $service) use ($detailed) {
            $source = $service->publishedArray();
            $localized = Translations::localize($source);
            $card = [
                'slug' => $service->slug,
                'name' => $localized['name'] ?? '',
                'summary' => $localized['summary'] ?? '',
                'cover' => $this->mediaCard($source['cover_media_id'] ?? null),
            ];

            if ($detailed) {
                $card['features'] = array_slice($localized['features'] ?? [], 0, 3);
            }

            return $card;
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projectCards(?string $categorySlug = null): array
    {
        $categoryId = $categorySlug
            ? ProjectCategory::query()->where('slug', $categorySlug)->value('id')
            : null;

        return Project::query()->visible()->orderBy('sort_order')->get()->filter(function (Project $project) use ($categorySlug, $categoryId) {
            if (! $categorySlug) {
                return true;
            }

            return ($project->publishedArray()['project_category_id'] ?? null) === $categoryId;
        })->map(function (Project $project) {
            $source = $project->publishedArray();
            $localized = Translations::localize($source);
            $category = isset($source['project_category_id']) ? ProjectCategory::query()->find($source['project_category_id']) : null;

            return [
                'slug' => $project->slug,
                'title' => $localized['title'] ?? '',
                'summary' => $localized['summary'] ?? '',
                'is_demonstration' => (bool) ($source['is_demonstration'] ?? true),
                'is_featured' => (bool) ($source['is_featured'] ?? false),
                'cover' => $this->mediaCard($source['cover_media_id'] ?? null),
                'category' => $category ? Translations::localize($category->name) : null,
                'category_slug' => $category?->slug,
            ];
        })->values()->all();
    }

    /**
     * @param  array<int, int>|null  $only
     * @return array<int, array<string, mixed>>
     */
    private function technologyCards(?array $only = null): array
    {
        $query = Technology::query()->where('is_visible', true)->orderBy('sort_order');

        if ($only !== null) {
            $query->whereIn('id', $only === [] ? [0] : $only);
        }

        return $query->get()->map(fn (Technology $technology) => [
            'slug' => $technology->slug,
            'name' => $technology->name,
            'description' => Translations::localize($technology->description),
        ])->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function faqs(string $placement): array
    {
        return Faq::query()->visible()->where('placement', $placement)->orderBy('sort_order')->get()->map(function (Faq $faq) {
            $localized = Translations::localize($faq->publishedArray());

            return [
                'question' => $localized['question'] ?? '',
                'answer' => $localized['answer'] ?? '',
            ];
        })->filter(fn ($faq) => ($faq['question'] ?? '') !== '')->values()->all();
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function section(array $section): array
    {
        $content = $section['content'] ?? [];
        $localized = Translations::localize($content);
        $localized['image'] = $this->mediaCard(isset($content['image_id']) ? (int) $content['image_id'] : null);

        return [
            'id' => $section['id'] ?? null,
            'type' => $section['type'],
            'layout' => $section['layout'],
            'theme' => $section['theme'],
            'content' => $localized,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Service|Project|Article $model, bool $preview): array
    {
        if ($preview) {
            return $model->toContentSnapshot();
        }

        return $model->publishedArray();
    }

    /**
     * @return array{page: array<string, mixed>, seo: array<string, mixed>}
     */
    private function optionalIntro(string $template, bool $preview): array
    {
        $page = Page::query()->where('template', $template)->first();

        if (! $page) {
            return [
                'page' => ['title' => '', 'excerpt' => '', 'body' => []],
                'seo' => $this->seo('RACODE', ''),
            ];
        }

        $source = $preview ? [
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'body' => $page->body,
            'seo' => $page->seo,
        ] : $page->publishedArray();

        $localized = Translations::localize($source);

        return [
            'page' => [
                'title' => $localized['title'] ?? '',
                'excerpt' => $localized['excerpt'] ?? '',
                'body' => $localized['body'] ?? [],
            ],
            'seo' => $this->seo(
                $this->text($source['seo']['title'] ?? null, (string) ($localized['title'] ?? 'RACODE')),
                $this->text($source['seo']['description'] ?? null, (string) ($localized['excerpt'] ?? '')),
            ),
        ];
    }

    /**
     * @return array{url: string, alt: string, width: int|null, height: int|null}|null
     */
    private function mediaCard(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        if (! array_key_exists($id, $this->media)) {
            $media = Media::query()->find($id);
            $this->media[$id] = $media ? [
                'url' => $media->url,
                'alt' => (string) Translations::localize($media->alt),
                'width' => $media->width,
                'height' => $media->height,
            ] : null;
        }

        return $this->media[$id];
    }

    private function text(mixed $value, string $fallback): string
    {
        $localized = Translations::localize($value);

        if (is_string($localized) && trim($localized) !== '') {
            return trim($localized);
        }

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $fallback;
    }

    private function navigationPath(NavigationItem $item): string
    {
        if ($item->page) {
            return match ($item->page->template) {
                'homepage' => '/',
                'services_index' => '/services',
                'work_index' => '/work',
                'technologies_index' => '/technologies',
                'insights_index' => '/insights',
                'contact' => '/contact',
                default => $item->page->is_system ? '/'.$item->page->slug : '/p/'.$item->page->slug,
            };
        }

        return $item->path ?: '/';
    }

    /**
     * @return array<string, mixed>
     */
    private function organization(): array
    {
        $brand = $this->settings->publicBrand();
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'RACODE',
            'alternateName' => 'راكود',
            'url' => url('/ar'),
            'slogan' => 'Beyond the Code',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'OM',
            ],
        ];

        if ($brand['contact']['email'] !== '') {
            $data['email'] = $brand['contact']['email'];
        }

        return $data;
    }
}
