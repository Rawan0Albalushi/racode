<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');

        if (! in_array($locale, config('racode.locales'), true)) {
            abort(404);
        }

        app()->setLocale($locale);
        cookie()->queue(cookie('locale', $locale, 60 * 24 * 365));

        return $next($request);
    }
}
