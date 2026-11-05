<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentRevision;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait CmsHelpers
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mediaOptions(): array
    {
        return Media::query()->latest()->limit(100)->get()->map(fn (Media $media) => [
            'id' => $media->id,
            'url' => $media->url,
            'filename' => $media->filename,
            'alt' => $media->alt,
            'width' => $media->width,
            'height' => $media->height,
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function revisionList(Model $model): array
    {
        return ContentRevision::query()
            ->with('user:id,name')
            ->where('revisionable_type', $model->getMorphClass())
            ->where('revisionable_id', $model->getKey())
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (ContentRevision $revision) => [
                'id' => $revision->id,
                'note' => $revision->note,
                'created_at' => $revision->created_at?->timezone(config('app.timezone'))->toDateTimeString(),
                'user' => $revision->user?->name,
            ])->all();
    }

    protected function searchTerm(Request $request): string
    {
        return trim(str_replace(['%', '_'], '', $request->string('q')->toString()));
    }
}
