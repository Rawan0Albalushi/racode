<?php

namespace App\Models;

use App\Models\Concerns\HasPublishing;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasPublishing;

    protected $fillable = [
        'question', 'answer', 'placement', 'sort_order', 'is_visible', 'status', 'published_at',
        'scheduled_for', 'published_payload', 'scheduled_payload', 'has_unpublished_changes',
    ];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
            'is_visible' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'published_payload' => 'array',
            'scheduled_payload' => 'array',
            'has_unpublished_changes' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toContentSnapshot(): array
    {
        return [
            'question' => $this->question,
            'answer' => $this->answer,
            'placement' => $this->placement,
            'sort_order' => $this->sort_order,
            'is_visible' => $this->is_visible,
        ];
    }
}
