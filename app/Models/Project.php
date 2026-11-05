<?php

namespace App\Models;

use App\Models\Concerns\HasPublishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasPublishing;

    protected $fillable = [
        'slug', 'pending_slug', 'project_category_id', 'title', 'summary', 'description', 'challenges',
        'solutions', 'features', 'results', 'results_verified', 'is_demonstration', 'is_featured', 'seo',
        'cover_media_id', 'status', 'published_at', 'scheduled_for', 'sort_order', 'published_payload',
        'scheduled_payload', 'has_unpublished_changes',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'summary' => 'array',
            'description' => 'array',
            'challenges' => 'array',
            'solutions' => 'array',
            'features' => 'array',
            'results' => 'array',
            'results_verified' => 'boolean',
            'is_demonstration' => 'boolean',
            'is_featured' => 'boolean',
            'seo' => 'array',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'published_payload' => 'array',
            'scheduled_payload' => 'array',
            'has_unpublished_changes' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class);
    }

    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'project_media')->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /**
     * @return array<string, mixed>
     */
    public function toContentSnapshot(): array
    {
        return [
            'slug' => $this->pending_slug ?: $this->slug,
            'project_category_id' => $this->project_category_id,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'challenges' => $this->challenges ?? [],
            'solutions' => $this->solutions ?? [],
            'features' => $this->features ?? [],
            'results' => $this->results_verified ? ($this->results ?? []) : [],
            'results_verified' => $this->results_verified,
            'is_demonstration' => $this->is_demonstration,
            'is_featured' => $this->is_featured,
            'seo' => $this->seo,
            'cover_media_id' => $this->cover_media_id,
            'gallery_ids' => $this->gallery()->pluck('media.id')->all(),
            'technology_ids' => $this->technologies()->pluck('technologies.id')->all(),
            'sort_order' => $this->sort_order,
        ];
    }
}
