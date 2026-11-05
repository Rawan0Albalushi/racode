<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\ActivityLogger;
use App\Services\ContentPublisher;
use App\Support\ContentRules;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    use CmsHelpers;

    public function index(): Response
    {
        $this->authorize('viewAny', Faq::class);

        return Inertia::render('admin/faqs/Index', [
            'faqs' => Faq::query()->orderBy('sort_order')->paginate(30)->through(fn (Faq $faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'placement' => $faq->placement,
                'status' => $faq->status,
                'is_visible' => $faq->is_visible,
                'sort_order' => $faq->sort_order,
                'has_unpublished_changes' => $faq->has_unpublished_changes,
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Faq::class);

        return Inertia::render('admin/faqs/Form', ['faq' => null, 'revisions' => []]);
    }

    public function store(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('create', Faq::class);
        $data = $this->validated($request);
        $faq = new Faq(['status' => 'draft', 'sort_order' => (int) Faq::query()->max('sort_order') + 1]);
        $this->fill($faq, $data);
        $faq->save();
        $activity->log('faq.created', 'Created an FAQ', $faq, [], $request->user());

        return redirect()->route('admin.faqs.edit', $faq)->with('success', __('racode.saved'));
    }

    public function edit(Faq $faq): Response
    {
        $this->authorize('view', $faq);

        return Inertia::render('admin/faqs/Form', [
            'faq' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'placement' => $faq->placement,
                'is_visible' => $faq->is_visible,
                'status' => $faq->status,
                'has_unpublished_changes' => $faq->has_unpublished_changes,
            ],
            'revisions' => $this->revisionList($faq),
        ]);
    }

    public function update(Request $request, Faq $faq, ActivityLogger $activity, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('update', $faq);
        $this->fill($faq, $this->validated($request));
        $publisher->saveDraft($faq);
        $activity->log('faq.updated', 'Updated an FAQ draft', $faq, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, Faq $faq, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $faq);
        $activity->log('faq.deleted', 'Deleted an FAQ', null, [], $request->user());
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', __('racode.deleted'));
    }

    public function publish(Request $request, Faq $faq, ContentPublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $faq);
        $publisher->publish($faq, $request->user());

        return back()->with('success', __('racode.published'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('faqs.update'), 403);

        foreach ($request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:faqs,id']])['ids'] as $index => $id) {
            Faq::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        return back()->with('success', __('racode.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            ...ContentRules::bilingual('question', 300),
            ...ContentRules::bilingual('answer', 2000),
            'placement' => ['required', Rule::in(['home', 'general'])],
            'is_visible' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Faq $faq, array $data): void
    {
        $faq->fill([
            'question' => Translations::pair($data['question']),
            'answer' => Translations::pair($data['answer']),
            'placement' => $data['placement'],
            'is_visible' => (bool) $data['is_visible'],
        ]);
    }
}
