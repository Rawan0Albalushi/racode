<?php

namespace App\Models;

use App\Models\Concerns\HasPublishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasPublishing;

    protected $fillable = [
        'slug', 'pending_slug', 'template', 'is_system', 'status', 'published_at', 'scheduled_for',
        'sort_order', 'title', 'excerpt', 'body', 'seo', 'published_payload', 'scheduled_payload',
        'has_unpublished_changes',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'title' => 'array',
            'excerpt' => 'array',
            'body' => 'array',
            'seo' => 'array',
            'published_payload' => 'array',
            'scheduled_payload' => 'array',
            'has_unpublished_changes' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    /**
     * @return array<string, mixed>
     */
    public function toContentSnapshot(): array
    {
        return [
            'slug' => $this->pending_slug ?: $this->slug,
            'template' => $this->template,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'seo' => $this->seo,
            'sections' => $this->sections()->get()->map(fn (PageSection $section) => [
                'id' => $section->id,
                'type' => $section->type,
                'layout' => $section->layout,
                'theme' => $section->theme,
                'is_visible' => $section->is_visible,
                'sort_order' => $section->sort_order,
                'content' => $section->content,
            ])->values()->all(),
        ];
    }
}
