<?php

namespace App\Services;

use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RevisionService
{
    public function store(Model $model, ?User $user, string $note, array $snapshot): ContentRevision
    {
        $revision = $model->morphMany(ContentRevision::class, 'revisionable')->create([
            'user_id' => $user?->id,
            'snapshot' => $snapshot,
            'note' => $note,
        ]);

        $keep = $model->morphMany(ContentRevision::class, 'revisionable')
            ->latest('id')
            ->limit(30)
            ->pluck('id');

        if ($keep->isNotEmpty()) {
            ContentRevision::query()
                ->where('revisionable_type', $model->getMorphClass())
                ->where('revisionable_id', $model->getKey())
                ->whereNotIn('id', $keep)
                ->delete();
        }

        return $revision;
    }
}
