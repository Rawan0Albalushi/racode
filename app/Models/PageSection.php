<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $fillable = [
        'page_id', 'type', 'layout', 'theme', 'is_visible', 'sort_order', 'content',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'content' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
