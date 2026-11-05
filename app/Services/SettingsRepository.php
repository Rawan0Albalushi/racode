<?php

namespace App\Services;

use App\Models\Media;
use App\Models\SiteSetting;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Cache;

class SettingsRepository
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember('site.settings', 300, function (): array {
            return SiteSetting::query()->pluck('value', 'key')->all();
        });
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function put(string $key, array $value): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('site.settings');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function forget(): void
    {
        Cache::forget('site.settings');
    }

    /**
     * @return array<string, mixed>
     */
    public function publicBrand(): array
    {
        $colors = $this->get('brand', []);
        $defaults = config('racode.colors');
        $company = $this->get('company', []);
        $contact = $this->get('contact', []);
        $social = $this->get('social', []);
        $footer = $this->get('footer', []);
        $seo = $this->get('seo', []);
        $analytics = $this->get('analytics', []);
        $logoId = $this->get('logo', [])['media_id'] ?? null;
        $faviconId = $this->get('favicon', [])['media_id'] ?? null;
        $logo = $logoId ? Media::query()->find($logoId) : null;
        $favicon = $faviconId ? Media::query()->find($faviconId) : null;
        $locale = app()->getLocale();

        $cleanSocial = [];

        foreach (['linkedin', 'instagram', 'x', 'github', 'youtube'] as $network) {
            $url = SafeUrl::clean($social[$network] ?? null);

            if ($url) {
                $cleanSocial[$network] = $url;
            }
        }

        $measurement = (string) ($analytics['measurement_id'] ?? '');
        $plausible = (string) ($analytics['plausible_domain'] ?? '');

        return [
            'name' => 'RACODE',
            'name_ar' => 'راكود',
            'philosophy' => 'Beyond the Code',
            'tagline' => $locale === 'ar'
                ? 'أفكار طموحة. حلول بلا حدود.'
                : 'Ambitious Ideas. Limitless Solutions.',
            'logo_url' => $logo?->url ?? '/brand/mark.svg',
            'favicon_url' => $favicon?->url ?? '/favicon.svg',
            'colors' => [
                'midnight' => SafeUrl::hex($colors['midnight'] ?? null, $defaults['midnight']),
                'burgundy' => SafeUrl::hex($colors['burgundy'] ?? null, $defaults['burgundy']),
                'rose' => SafeUrl::hex($colors['rose'] ?? null, $defaults['rose']),
                'blush' => SafeUrl::hex($colors['blush'] ?? null, $defaults['blush']),
                'ivory' => SafeUrl::hex($colors['ivory'] ?? null, $defaults['ivory']),
                'white' => '#FFFFFF',
            ],
            'company' => [
                'legal_name' => (string) ($company['legal_name'] ?? 'RACODE'),
                'country' => $locale === 'ar' ? 'سلطنة عُمان' : 'Sultanate of Oman',
            ],
            'contact' => [
                'email' => filter_var($contact['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $contact['email'] : '',
                'phone' => (string) ($contact['phone'] ?? ''),
                'address' => (string) (($contact['address'][$locale] ?? '') ?: ($locale === 'ar' ? 'سلطنة عُمان' : 'Sultanate of Oman')),
            ],
            'social' => $cleanSocial,
            'footer' => (string) ($footer[$locale] ?? $footer['ar'] ?? ''),
            'seo' => [
                'title' => (string) ($seo['title'][$locale] ?? 'RACODE'),
                'description' => (string) ($seo['description'][$locale] ?? ''),
            ],
            'analytics' => [
                'ga4' => preg_match('/^G-[A-Z0-9]+$/', $measurement) === 1 ? $measurement : null,
                'plausible' => preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $plausible) === 1 ? $plausible : null,
            ],
            'maintenance' => (bool) ($this->get('maintenance', [])['enabled'] ?? false),
        ];
    }
}
