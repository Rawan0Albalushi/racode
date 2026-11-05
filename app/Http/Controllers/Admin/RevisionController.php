<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ContentRevision;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function restore(Request $request, ContentRevision $revision, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($request->user()?->can('revisions.restore'), 403);
        $model = $revision->revisionable;
        abort_if($model === null || ! method_exists($model, 'toContentSnapshot'), 404);

        $permission = match ($model::class) {
            Page::class => 'pages.publish',
            Service::class => 'services.publish',
            Project::class => 'projects.publish',
            Article::class => 'articles.publish',
            Faq::class => 'faqs.publish',
            default => 'revisions.restore',
        };

        abort_unless($request->user()->can($permission), 403);
        $publisher->restore($model, $revision->snapshot ?? [], $request->user(), true);

        return back()->with('success', __('racode.restored'));
    }
}
