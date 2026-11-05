<?php

namespace App\Http\Middleware;

use App\Models\Inquiry;
use App\Services\PublicContent;
use App\Services\SettingsRepository;
use App\Support\Locale;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private SettingsRepository $settings,
        private PublicContent $content,
    ) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $this->applyLocale($request);
        $user = $request->user();
        $brand = $this->settings->publicBrand();

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'dir' => Locale::direction(),
            'context' => $request->is('admin', 'admin/*') ? 'admin' : 'public',
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'locale' => $user->locale,
                    'roles' => $user->getRoleNames()->values()->all(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'nav' => [
                'header' => $this->content->navigation('header'),
                'footer' => $this->content->navigation('footer'),
            ],
            'site' => $brand,
            'seo' => [
                'title' => $brand['seo']['title'] ?: 'RACODE',
                'description' => $brand['seo']['description'] ?: $brand['tagline'],
                'canonical' => url()->current(),
                'alternates' => [
                    'ar' => Locale::switchUrl('ar'),
                    'en' => Locale::switchUrl('en'),
                    'x-default' => Locale::switchUrl('ar'),
                ],
                'image' => url('/brand/og.png'),
                'locale' => app()->getLocale() === 'ar' ? 'ar_OM' : 'en_US',
                'json_ld' => null,
            ],
            'unreadInquiries' => $user && $user->can('inquiries.view')
                ? Inquiry::query()->where('status', 'new')->count()
                : 0,
        ];
    }

    private function applyLocale(Request $request): void
    {
        if ($request->is('admin', 'admin/*')) {
            $locale = (string) $request->session()->get('admin_locale', $request->user()?->locale ?? 'ar');
        } else {
            $locale = (string) ($request->route('locale') ?: $request->cookie('locale', 'ar'));
        }

        if (! in_array($locale, config('racode.locales'), true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);
    }
}
