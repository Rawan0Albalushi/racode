<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryNote;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('inquiries.view'), 403);
        $term = trim(str_replace(['%', '_'], '', $request->string('q')->toString()));
        $status = $request->string('status')->toString();

        return Inertia::render('admin/inquiries/Index', [
            'inquiries' => Inquiry::query()->with('assignee:id,name')
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('company', 'like', "%{$term}%");
                }))
                ->when(in_array($status, ['new', 'in_review', 'replied', 'archived'], true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => ['q' => $term, 'status' => $status],
            'assignees' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Inquiry $inquiry): Response
    {
        abort_unless(request()->user()?->can('inquiries.view'), 403);
        $inquiry->load(['notes.user:id,name', 'assignee:id,name']);

        return Inertia::render('admin/inquiries/Show', [
            'inquiry' => $inquiry,
            'assignees' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Inquiry $inquiry, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('inquiries.update'), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_review', 'replied', 'archived'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] && ! $request->user()->can('inquiries.assign')) {
            abort(403);
        }

        $inquiry->update($data);
        $activity->log('inquiry.updated', 'Updated an inquiry', $inquiry, ['status' => $inquiry->status], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function note(Request $request, Inquiry $inquiry, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->can('inquiries.update'), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        InquiryNote::query()->create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $activity->log('inquiry.note', 'Added an inquiry note', $inquiry, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('inquiries.export'), 403);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'name', 'email', 'phone', 'company', 'project_type', 'status', 'locale', 'created_at', 'message']);

            Inquiry::query()->orderBy('id')->each(function (Inquiry $inquiry) use ($handle): void {
                fputcsv($handle, [
                    $inquiry->id,
                    $inquiry->name,
                    $inquiry->email,
                    $inquiry->phone,
                    $inquiry->company,
                    $inquiry->project_type,
                    $inquiry->status,
                    $inquiry->locale,
                    optional($inquiry->created_at)->toDateTimeString(),
                    $inquiry->message,
                ]);
            });

            fclose($handle);
        }, 'inquiries.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
