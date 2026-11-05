<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use App\Services\MediaLibrary;
use App\Services\RevisionService;
use App\Services\SettingsRepository;
use App\Support\SafeUrl;
use App\Support\Translations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    use CmsHelpers;

    public function edit(SettingsRepository $settings): Response
    {
        abort_unless(request()->user()?->can('settings.update'), 403);

        return Inertia::render('admin/settings/Edit', [
            'settings' => [
                'brand' => $settings->get('brand', config('racode.colors')),
                'company' => $settings->get('company', []),
                'contact' => $settings->get('contact', []),
                'social' => $settings->get('social', []),
                'footer' => $settings->get('footer', ['ar' => '', 'en' => '']),
                'seo' => $settings->get('seo', ['title' => ['ar' => '', 'en' => ''], 'description' => ['ar' => '', 'en' => '']]),
                'maintenance' => $settings->get('maintenance', ['enabled' => false]),
                'analytics' => $settings->get('analytics', ['measurement_id' => '', 'plausible_domain' => '']),
                'logo' => $settings->get('logo', ['media_id' => null]),
                'favicon' => $settings->get('favicon', ['media_id' => null]),
            ],
            'media' => $this->mediaOptions(),
            'defaults' => config('racode.colors'),
        ]);
    }

    public function update(Request $request, SettingsRepository $settings, ActivityLogger $activity, RevisionService $revisions, MediaLibrary $media): RedirectResponse
    {
        abort_unless($request->user()?->can('settings.update'), 403);
        $data = $request->validate([
            'brand.midnight' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand.burgundy' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand.rose' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand.blush' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand.ivory' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'company.legal_name' => ['required', 'string', 'max:160'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:40'],
            'contact.notify_email' => ['nullable', 'email', 'max:255'],
            'contact.address.ar' => ['nullable', 'string', 'max:255'],
            'contact.address.en' => ['nullable', 'string', 'max:255'],
            'social.linkedin' => ['nullable', 'string', 'max:255'],
            'social.instagram' => ['nullable', 'string', 'max:255'],
            'social.x' => ['nullable', 'string', 'max:255'],
            'social.github' => ['nullable', 'string', 'max:255'],
            'social.youtube' => ['nullable', 'string', 'max:255'],
            'footer.ar' => ['nullable', 'string', 'max:500'],
            'footer.en' => ['nullable', 'string', 'max:500'],
            'seo.title.ar' => ['nullable', 'string', 'max:160'],
            'seo.title.en' => ['nullable', 'string', 'max:160'],
            'seo.description.ar' => ['nullable', 'string', 'max:320'],
            'seo.description.en' => ['nullable', 'string', 'max:320'],
            'maintenance.enabled' => ['required', 'boolean'],
            'analytics.measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$|^$/'],
            'analytics.plausible_domain' => ['nullable', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$|^$/i'],
            'logo.media_id' => ['nullable', 'integer', 'exists:media,id'],
            'favicon.media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $groups = [
            'brand' => [
                'midnight' => SafeUrl::hex($data['brand']['midnight'], '#210C17'),
                'burgundy' => SafeUrl::hex($data['brand']['burgundy'], '#5B2038'),
                'rose' => SafeUrl::hex($data['brand']['rose'], '#A83F67'),
                'blush' => SafeUrl::hex($data['brand']['blush'], '#E8A3BB'),
                'ivory' => SafeUrl::hex($data['brand']['ivory'], '#F8F0F3'),
            ],
            'company' => ['legal_name' => $data['company']['legal_name']],
            'contact' => [
                'email' => $data['contact']['email'] ?? '',
                'phone' => $data['contact']['phone'] ?? '',
                'notify_email' => $data['contact']['notify_email'] ?? '',
                'address' => Translations::pair($data['contact']['address'] ?? []),
            ],
            'social' => collect(['linkedin', 'instagram', 'x', 'github', 'youtube'])
                ->mapWithKeys(fn (string $network) => [$network => SafeUrl::clean($data['social'][$network] ?? null)])
                ->all(),
            'footer' => Translations::pair($data['footer'] ?? []),
            'seo' => [
                'title' => Translations::pair($data['seo']['title'] ?? []),
                'description' => Translations::pair($data['seo']['description'] ?? []),
            ],
            'maintenance' => ['enabled' => (bool) $data['maintenance']['enabled']],
            'analytics' => [
                'measurement_id' => strtoupper((string) ($data['analytics']['measurement_id'] ?? '')),
                'plausible_domain' => strtolower((string) ($data['analytics']['plausible_domain'] ?? '')),
            ],
            'logo' => ['media_id' => $data['logo']['media_id'] ?? null],
            'favicon' => ['media_id' => $data['favicon']['media_id'] ?? null],
        ];

        foreach ($groups as $key => $value) {
            $settings->put($key, $value);
        }

        $anchor = SiteSetting::query()->where('key', 'brand')->first();

        if ($anchor) {
            $media->syncUsage($anchor, [$groups['logo']['media_id']], 'logo');
            $media->syncUsage($anchor, [$groups['favicon']['media_id']], 'favicon');
            $revisions->store($anchor, $request->user(), 'settings', $groups);
        }
        $activity->log('settings.updated', 'Updated site settings', null, [], $request->user());

        return back()->with('success', __('racode.saved'));
    }
}
