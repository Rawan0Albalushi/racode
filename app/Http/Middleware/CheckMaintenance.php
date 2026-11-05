<?php

namespace App\Http\Middleware;

use App\Services\SettingsRepository;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    public function __construct(private SettingsRepository $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) ($this->settings->get('maintenance', [])['enabled'] ?? false);

        if (! $enabled || $request->user() || $request->is('admin', 'admin/*')) {
            return $next($request);
        }

        return Inertia::render('website/Error', ['status' => 503])
            ->toResponse($request)
            ->setStatusCode(503);
    }
}
