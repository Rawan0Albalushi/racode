<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('activity.view'), 403);
        $term = trim(str_replace(['%', '_'], '', $request->string('q')->toString()));

        return Inertia::render('admin/activity/Index', [
            'logs' => ActivityLog::query()->with('user:id,name')
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $query->where('description', 'like', "%{$term}%")
                        ->orWhere('action', 'like', "%{$term}%");
                }))
                ->latest()
                ->paginate(30)
                ->withQueryString()
                ->through(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->name,
                    'created_at' => $log->created_at?->timezone(config('app.timezone'))->toDateTimeString(),
                ]),
            'filters' => ['q' => $term],
        ]);
    }
}
