<?php

namespace App\Models;

use App\Models\Concerns\HasPublishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasPublishing;

    protected $fillable = [
        'slug', 'pending_slug', 'article_category_id', 'author_id', 'title', 'excerpt', 'body', 'seo',
        'cover_media_id', 'is_featured', 'status', 'published_at', 'scheduled_for', 'published_payload',
        'scheduled_payload', 'has_unpublished_changes',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'body' => 'array',
            'seo' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'published_payload' => 'array',
            'scheduled_payload' => 'array',
            'has_unpublished_changes' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toContentSnapshot(): array
    {
        return [
            'slug' => $this->pending_slug ?: $this->slug,
            'article_category_id' => $this->article_category_id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'seo' => $this->seo,
            'cover_media_id' => $this->cover_media_id,
            'is_featured' => $this->is_featured,
            'tag_ids' => $this->tags()->pluck('tags.id')->all(),
        ];
    }
}
