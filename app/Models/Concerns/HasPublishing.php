<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasPublishing
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_payload')
            ->whereIn('status', ['published', 'scheduled'])
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function publishedArray(): array
    {
        $payload = $this->getAttribute('published_payload');

        return is_array($payload) ? $payload : [];
    }

    public function applySlug(string $slug): void
    {
        if ($this->exists && in_array($this->getAttribute('status'), ['published', 'scheduled'], true) && $slug !== $this->getAttribute('slug')) {
            $this->setAttribute('pending_slug', $slug);

            return;
        }

        $this->setAttribute('slug', $slug);
        $this->setAttribute('pending_slug', null);
    }

    public function commitPendingSlug(): void
    {
        $pending = $this->getAttribute('pending_slug');

        if (is_string($pending) && $pending !== '') {
            $this->setAttribute('slug', $pending);
            $this->setAttribute('pending_slug', null);
        }
    }
}
