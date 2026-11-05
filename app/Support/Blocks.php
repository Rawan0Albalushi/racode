<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class Blocks
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(string $key = 'body'): array
    {
        return [
            $key => ['nullable', 'array', 'max:80'],
            $key.'.*.type' => ['required', Rule::in(['heading', 'paragraph', 'list', 'quote'])],
            $key.'.*.level' => ['nullable', 'integer', Rule::in([2, 3])],
            $key.'.*.text' => ['nullable', 'array'],
            $key.'.*.text.ar' => ['nullable', 'string', 'max:5000'],
            $key.'.*.text.en' => ['nullable', 'string', 'max:5000'],
            $key.'.*.items' => ['nullable', 'array', 'max:30'],
            $key.'.*.items.*.ar' => ['nullable', 'string', 'max:500'],
            $key.'.*.items.*.en' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @param  array<int, mixed>|null  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function sanitize(?array $blocks): array
    {
        if ($blocks === null) {
            return [];
        }

        $clean = [];

        foreach (array_slice($blocks, 0, 80) as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? '';

            if (! in_array($type, ['heading', 'paragraph', 'list', 'quote'], true)) {
                continue;
            }

            $entry = [
                'type' => $type,
                'text' => Translations::pair($block['text'] ?? []),
            ];

            if ($type === 'heading') {
                $level = (int) ($block['level'] ?? 2);
                $entry['level'] = in_array($level, [2, 3], true) ? $level : 2;
            }

            if ($type === 'list') {
                $items = [];

                foreach (array_slice($block['items'] ?? [], 0, 30) as $item) {
                    $pair = Translations::pair(is_array($item) ? $item : []);

                    if ($pair['ar'] !== '' || $pair['en'] !== '') {
                        $items[] = $pair;
                    }
                }

                $entry['items'] = $items;
            }

            $clean[] = $entry;
        }

        return $clean;
    }
}
