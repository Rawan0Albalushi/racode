<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Services\ActivityLogger;
use App\Services\PublicContent;
use App\Support\SafeUrl;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NavigationController extends Controller
{
    public function edit(): Response
    {
        abort_unless(request()->user()?->can('navigation.update') || request()->user()?->can('pages.view'), 403);

        return Inertia::render('admin/navigation/Index', [
            'items' => NavigationItem::query()->orderBy('location')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, ActivityLogger $activity, PublicContent $content): RedirectResponse
    {
        abort_unless($request->user()?->can('navigation.update'), 403);
        $data = $request->validate([
            'items' => ['required', 'array', 'max:40'],
            'items.*.location' => ['required', Rule::in(['header', 'footer'])],
            'items.*.label.ar' => ['required', 'string', 'max:80'],
            'items.*.label.en' => ['required', 'string', 'max:80'],
            'items.*.path' => ['required', 'string', 'max:200'],
            'items.*.is_visible' => ['required', 'boolean'],
            'items.*.open_in_new_tab' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            NavigationItem::query()->delete();

            foreach (array_values($data['items']) as $index => $item) {
                $path = SafeUrl::clean($item['path']);

                if (! $path) {
                    continue;
                }

                NavigationItem::query()->create([
                    'location' => $item['location'],
                    'label' => Translations::pair($item['label']),
                    'link_type' => 'path',
                    'path' => $path,
                    'sort_order' => $index,
                    'is_visible' => (bool) $item['is_visible'],
                    'open_in_new_tab' => (bool) $item['open_in_new_tab'],
                ]);
            }
        });

        $content->forgetNavigation();
        $activity->log('navigation.updated', 'Updated navigation', null, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }
}
