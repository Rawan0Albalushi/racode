<?php

namespace App\Models;

use App\Models\Concerns\HasPublishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasPublishing;

    protected $fillable = [
        'slug', 'pending_slug', 'name', 'summary', 'description', 'features', 'seo', 'cover_media_id',
        'status', 'published_at', 'scheduled_for', 'sort_order', 'published_payload', 'scheduled_payload',
        'has_unpublished_changes',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'summary' => 'array',
            'description' => 'array',
            'features' => 'array',
            'seo' => 'array',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'published_payload' => 'array',
            'scheduled_payload' => 'array',
            'has_unpublished_changes' => 'boolean',
        ];
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toContentSnapshot(): array
    {
        return [
            'slug' => $this->pending_slug ?: $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'description' => $this->description,
            'features' => $this->features ?? [],
            'seo' => $this->seo,
            'cover_media_id' => $this->cover_media_id,
            'technology_ids' => $this->technologies()->pluck('technologies.id')->all(),
            'sort_order' => $this->sort_order,
        ];
    }
}
