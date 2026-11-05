<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

final class Locale
{
    public static function current(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, config('racode.locales'), true) ? $locale : config('racode.default_locale');
    }

    public static function direction(?string $locale = null): string
    {
        return ($locale ?? self::current()) === 'ar' ? 'rtl' : 'ltr';
    }

    public static function switchUrl(string $locale): string
    {
        $path = trim(Request::path(), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        if (isset($segments[0]) && in_array($segments[0], config('racode.locales'), true)) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $url = url(implode('/', $segments));
        $query = Request::getQueryString();

        return $query ? $url.'?'.$query : $url;
    }

    public static function path(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');

        if ($path === '/') {
            return '/'.self::current();
        }

        return '/'.self::current().$path;
    }
}
