<?php

namespace App\Support;

final class Translations
{
    public static function localize(mixed $value, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        if (! is_array($value)) {
            return $value;
        }

        if (self::isTranslation($value)) {
            $text = $value[$locale] ?? $value['ar'] ?? $value['en'] ?? '';

            return is_string($text) ? $text : '';
        }

        $localized = [];

        foreach ($value as $key => $item) {
            $localized[$key] = self::localize($item, $locale);
        }

        return $localized;
    }

    /**
     * @param  array<mixed>  $value
     */
    public static function isTranslation(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        foreach ($value as $key => $item) {
            if (! in_array($key, ['ar', 'en'], true)) {
                return false;
            }

            if (! is_string($item) && $item !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ar: string, en: string}
     */
    public static function pair(mixed $value): array
    {
        $value = is_array($value) ? $value : [];

        return [
            'ar' => trim((string) ($value['ar'] ?? '')),
            'en' => trim((string) ($value['en'] ?? '')),
        ];
    }
}
