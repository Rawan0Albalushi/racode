<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('admin/Login');
    }

    public function store(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $key = strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = $request->user();

        if (! $user || ! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($key);
            throw ValidationException::withMessages([
                'email' => __('racode.inactive'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $activity->log('auth.login', 'Signed in', $user, [], $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $activity->log('auth.logout', 'Signed out', $request->user(), [], $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function locale(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', 'in:ar,en'],
        ])['locale'];

        $request->session()->put('admin_locale', $locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back();
    }
}
