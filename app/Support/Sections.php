<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class Sections
{
    /**
     * @return array<string, array{layouts: array<int, string>, themes: array<int, string>}>
     */
    public static function catalog(): array
    {
        return config('racode.section_types');
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return array_keys(self::catalog());
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'layout' => ['required', 'string', 'max:40'],
            'theme' => ['required', Rule::in(['light', 'dark'])],
            'is_visible' => ['required', 'boolean'],
            'content' => ['required', 'array'],
            'content.heading' => ['nullable', 'array'],
            'content.heading.ar' => ['nullable', 'string', 'max:300'],
            'content.heading.en' => ['nullable', 'string', 'max:300'],
            'content.description' => ['nullable', 'array'],
            'content.description.ar' => ['nullable', 'string', 'max:2000'],
            'content.description.en' => ['nullable', 'string', 'max:2000'],
            'content.primary_button' => ['nullable', 'array'],
            'content.primary_button.label.ar' => ['nullable', 'string', 'max:80'],
            'content.primary_button.label.en' => ['nullable', 'string', 'max:80'],
            'content.primary_button.url' => ['nullable', 'string', 'max:500'],
            'content.secondary_button' => ['nullable', 'array'],
            'content.secondary_button.label.ar' => ['nullable', 'string', 'max:80'],
            'content.secondary_button.label.en' => ['nullable', 'string', 'max:80'],
            'content.secondary_button.url' => ['nullable', 'string', 'max:500'],
            'content.image_id' => ['nullable', 'integer', 'exists:media,id'],
            'content.items' => ['nullable', 'array', 'max:12'],
            'content.items.*.title.ar' => ['nullable', 'string', 'max:200'],
            'content.items.*.title.en' => ['nullable', 'string', 'max:200'],
            'content.items.*.body.ar' => ['nullable', 'string', 'max:1000'],
            'content.items.*.body.en' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function sanitize(string $type, string $layout, string $theme, array $content): array
    {
        $catalog = self::catalog()[$type] ?? null;

        if ($catalog === null) {
            abort(422, 'Unknown section type.');
        }

        if (! in_array($layout, $catalog['layouts'], true)) {
            $layout = $catalog['layouts'][0];
        }

        if (! in_array($theme, $catalog['themes'], true)) {
            $theme = $catalog['themes'][0];
        }

        $items = [];

        foreach (array_slice($content['items'] ?? [], 0, 12) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'title' => Translations::pair($item['title'] ?? []),
                'body' => Translations::pair($item['body'] ?? []),
            ];
        }

        return [
            'layout' => $layout,
            'theme' => $theme,
            'content' => [
                'heading' => Translations::pair($content['heading'] ?? []),
                'description' => Translations::pair($content['description'] ?? []),
                'primary_button' => [
                    'label' => Translations::pair(data_get($content, 'primary_button.label', [])),
                    'url' => SafeUrl::clean(data_get($content, 'primary_button.url')),
                ],
                'secondary_button' => [
                    'label' => Translations::pair(data_get($content, 'secondary_button.label', [])),
                    'url' => SafeUrl::clean(data_get($content, 'secondary_button.url')),
                ],
                'image_id' => isset($content['image_id']) ? (int) $content['image_id'] : null,
                'items' => $items,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function blank(string $type): array
    {
        $catalog = self::catalog()[$type];

        return [
            'type' => $type,
            'layout' => $catalog['layouts'][0],
            'theme' => $catalog['themes'][0],
            'is_visible' => true,
            'content' => [
                'heading' => ['ar' => '', 'en' => ''],
                'description' => ['ar' => '', 'en' => ''],
                'primary_button' => ['label' => ['ar' => '', 'en' => ''], 'url' => null],
                'secondary_button' => ['label' => ['ar' => '', 'en' => ''], 'url' => null],
                'image_id' => null,
                'items' => [],
            ],
        ];
    }
}
