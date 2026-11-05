<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'pages' => Page::query()->count(),
                'services' => Service::query()->count(),
                'projects' => Project::query()->count(),
                'articles' => Article::query()->count(),
                'new_inquiries' => Inquiry::query()->where('status', 'new')->count(),
            ],
            'activity' => ActivityLog::query()->with('user:id,name')->latest()->limit(8)->get()->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
                'created_at' => $log->created_at?->timezone(config('app.timezone'))->toDateTimeString(),
            ]),
        ]);
    }
}
