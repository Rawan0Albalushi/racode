<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContentPublisher
{
    public function __construct(
        private RevisionService $revisions,
        private ActivityLogger $activity,
    ) {}

    public function saveDraft(Model $model): void
    {
        if ($model->getAttribute('published_payload') !== null || $model->getAttribute('status') === 'published') {
            $model->setAttribute('has_unpublished_changes', true);
        }

        $model->save();
    }

    public function publish(Model $model, User $user): void
    {
        if (method_exists($model, 'commitPendingSlug')) {
            $model->commitPendingSlug();
        }

        $snapshot = $model->toContentSnapshot();
        $model->setAttribute('published_payload', $snapshot);
        $model->setAttribute('scheduled_payload', null);
        $model->setAttribute('scheduled_for', null);
        $model->setAttribute('status', 'published');
        $model->setAttribute('published_at', now());
        $model->setAttribute('has_unpublished_changes', false);
        $model->save();

        $this->revisions->store($model, $user, 'publish', $snapshot);
        $this->activity->log('content.published', 'Published '.$model->getMorphClass().' #'.$model->getKey(), $model, [], $user);
    }

    public function schedule(Model $model, User $user, Carbon $when): void
    {
        $snapshot = $model->toContentSnapshot();
        $model->setAttribute('scheduled_payload', $snapshot);
        $model->setAttribute('scheduled_for', $when);
        $model->setAttribute('status', 'scheduled');
        $model->setAttribute('has_unpublished_changes', false);
        $model->save();

        $this->revisions->store($model, $user, 'schedule', $snapshot);
        $this->activity->log('content.scheduled', 'Scheduled publication', $model, ['scheduled_for' => $when->toIso8601String()], $user);
    }

    public function publishDue(): int
    {
        $count = 0;

        foreach ([Page::class, Service::class, Project::class, Article::class] as $class) {
            $class::query()
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '<=', now())
                ->each(function (Model $model) use (&$count): void {
                    $payload = $model->getAttribute('scheduled_payload');

                    if (! is_array($payload)) {
                        return;
                    }

                    if (method_exists($model, 'commitPendingSlug')) {
                        $model->commitPendingSlug();
                    }

                    if (isset($payload['slug']) && is_string($payload['slug'])) {
                        $model->setAttribute('slug', $payload['slug']);
                        $payload['slug'] = $model->slug;
                    }

                    $model->setAttribute('published_payload', $payload);
                    $model->setAttribute('scheduled_payload', null);
                    $model->setAttribute('scheduled_for', null);
                    $model->setAttribute('status', 'published');
                    $model->setAttribute('published_at', now());
                    $model->setAttribute('has_unpublished_changes', false);
                    $model->save();
                    $count++;
                });
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function restore(Model $model, array $snapshot, User $user, bool $publish): void
    {
        $this->applySnapshot($model, $snapshot);
        $model->setAttribute('has_unpublished_changes', ! $publish);

        if ($publish) {
            if (method_exists($model, 'commitPendingSlug')) {
                $model->commitPendingSlug();
            }

            $fresh = $model->toContentSnapshot();
            $model->setAttribute('published_payload', $fresh);
            $model->setAttribute('status', 'published');
            $model->setAttribute('published_at', now());
            $model->setAttribute('has_unpublished_changes', false);
        }

        $model->save();
        $this->revisions->store($model, $user, 'restore', $model->toContentSnapshot());
        $this->activity->log('content.restored', 'Restored a previous version', $model, [], $user);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function applySnapshot(Model $model, array $snapshot): void
    {
        if ($model instanceof Page) {
            $model->fill([
                'title' => $snapshot['title'] ?? $model->title,
                'excerpt' => $snapshot['excerpt'] ?? $model->excerpt,
                'body' => $snapshot['body'] ?? $model->body,
                'seo' => $snapshot['seo'] ?? $model->seo,
            ]);

            foreach ($snapshot['sections'] ?? [] as $section) {
                if (! isset($section['id'])) {
                    continue;
                }

                $model->sections()->whereKey($section['id'])->update([
                    'layout' => $section['layout'] ?? 'editorial',
                    'theme' => $section['theme'] ?? 'light',
                    'is_visible' => (bool) ($section['is_visible'] ?? true),
                    'sort_order' => (int) ($section['sort_order'] ?? 0),
                    'content' => $section['content'] ?? [],
                ]);
            }

            return;
        }

        if ($model instanceof Service) {
            $model->fill([
                'name' => $snapshot['name'] ?? $model->name,
                'summary' => $snapshot['summary'] ?? $model->summary,
                'description' => $snapshot['description'] ?? $model->description,
                'features' => $snapshot['features'] ?? $model->features,
                'seo' => $snapshot['seo'] ?? $model->seo,
                'cover_media_id' => $snapshot['cover_media_id'] ?? null,
                'sort_order' => $snapshot['sort_order'] ?? $model->sort_order,
            ]);
            $model->technologies()->sync($snapshot['technology_ids'] ?? []);

            return;
        }

        if ($model instanceof Project) {
            $model->fill([
                'title' => $snapshot['title'] ?? $model->title,
                'summary' => $snapshot['summary'] ?? $model->summary,
                'description' => $snapshot['description'] ?? $model->description,
                'challenges' => $snapshot['challenges'] ?? [],
                'solutions' => $snapshot['solutions'] ?? [],
                'features' => $snapshot['features'] ?? [],
                'results' => $snapshot['results'] ?? [],
                'results_verified' => (bool) ($snapshot['results_verified'] ?? false),
                'is_demonstration' => (bool) ($snapshot['is_demonstration'] ?? true),
                'is_featured' => (bool) ($snapshot['is_featured'] ?? false),
                'seo' => $snapshot['seo'] ?? $model->seo,
                'cover_media_id' => $snapshot['cover_media_id'] ?? null,
                'project_category_id' => $snapshot['project_category_id'] ?? null,
                'sort_order' => $snapshot['sort_order'] ?? $model->sort_order,
            ]);
            $model->technologies()->sync($snapshot['technology_ids'] ?? []);
            $model->gallery()->sync($this->orderedSync($snapshot['gallery_ids'] ?? []));

            return;
        }

        if ($model instanceof Article) {
            $model->fill([
                'title' => $snapshot['title'] ?? $model->title,
                'excerpt' => $snapshot['excerpt'] ?? $model->excerpt,
                'body' => $snapshot['body'] ?? $model->body,
                'seo' => $snapshot['seo'] ?? $model->seo,
                'cover_media_id' => $snapshot['cover_media_id'] ?? null,
                'article_category_id' => $snapshot['article_category_id'] ?? null,
                'is_featured' => (bool) ($snapshot['is_featured'] ?? false),
            ]);
            $model->tags()->sync($snapshot['tag_ids'] ?? []);

            return;
        }

        if ($model instanceof Faq) {
            $model->fill([
                'question' => $snapshot['question'] ?? $model->question,
                'answer' => $snapshot['answer'] ?? $model->answer,
                'placement' => $snapshot['placement'] ?? $model->placement,
                'sort_order' => $snapshot['sort_order'] ?? $model->sort_order,
                'is_visible' => (bool) ($snapshot['is_visible'] ?? true),
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, array{sort_order: int}>
     */
    private function orderedSync(array $ids): array
    {
        $sync = [];

        foreach (array_values($ids) as $index => $id) {
            $sync[(int) $id] = ['sort_order' => $index];
        }

        return $sync;
    }
}
