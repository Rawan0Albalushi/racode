<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\InquiryRecorder;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function store(Request $request, InquiryRecorder $recorder): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'company' => ['nullable', 'string', 'max:160'],
            'project_type' => ['required', Rule::in(config('racode.project_types'))],
            'message' => ['required', 'string', 'max:5000'],
            'consent' => ['accepted'],
            'started_at' => ['required', 'integer'],
            'company_website' => ['nullable', 'string', 'max:0'],
        ]);

        $elapsed = now()->timestamp - (int) $data['started_at'];

        if ($elapsed < 2 || $elapsed > 86400) {
            throw ValidationException::withMessages([
                'message' => __('racode.spam'),
            ]);
        }

        $recorder->store($data, Locale::current(), $request->ip(), $request->userAgent());

        return back()->with('success', __('racode.inquiry_saved'));
    }
}
