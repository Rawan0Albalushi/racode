<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class ContentRules
{
    /**
     * @return array<string, mixed>
     */
    public static function bilingual(string $key, int $max = 255, bool $required = true): array
    {
        $rule = $required ? 'required' : 'nullable';

        return [
            $key => [$rule, 'array'],
            $key.'.ar' => [$rule, 'string', 'max:'.$max],
            $key.'.en' => [$rule, 'string', 'max:'.$max],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function seo(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            ...self::bilingual('seo.title', 160, false),
            ...self::bilingual('seo.description', 320, false),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function slug(string $table, ?int $ignore = null, bool $pending = true): array
    {
        $rules = [
            'required',
            'string',
            'max:120',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique($table, 'slug')->ignore($ignore),
        ];

        if ($pending) {
            $rules[] = Rule::unique($table, 'pending_slug')->ignore($ignore);
        }

        return $rules;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{ar: string, en: string}>
     */
    public static function pairs(array $rows, int $limit = 20): array
    {
        $clean = [];

        foreach (array_slice($rows, 0, $limit) as $row) {
            $pair = Translations::pair(is_array($row) ? $row : []);

            if ($pair['ar'] !== '' || $pair['en'] !== '') {
                $clean[] = $pair;
            }
        }

        return $clean;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{title: array{ar: string, en: string}, body: array{ar: string, en: string}}>
     */
    public static function titled(array $rows): array
    {
        $clean = [];

        foreach (array_slice($rows, 0, 12) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $item = [
                'title' => Translations::pair($row['title'] ?? []),
                'body' => Translations::pair($row['body'] ?? []),
            ];

            if ($item['title']['ar'] !== '' || $item['title']['en'] !== '' || $item['body']['ar'] !== '' || $item['body']['en'] !== '') {
                $clean[] = $item;
            }
        }

        return $clean;
    }
}
