<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Media;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_drafts_stay_private_until_they_are_published(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->post('/admin/services', $this->payload())->assertRedirect();

        $this->get('/ar/services/web-platforms')->assertNotFound();

        $service = Service::query()->firstOrFail();
        $this->actingAs($admin)->post("/admin/services/{$service->id}/publish")->assertRedirect();

        $this->get('/ar/services/web-platforms')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Services/Show')
            ->where('service.name', 'منصات الويب'));

        $this->get('/en/services/web-platforms')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('service.name', 'Web platforms'));

        $this->get('/sitemap.xml')->assertSee('/ar/services/web-platforms', false);
    }

    public function test_scheduled_content_publishes_when_the_time_arrives(): void
    {
        $admin = $this->makeUser('super-admin');
        $this->actingAs($admin)->post('/admin/services', $this->payload('field-notes'))->assertRedirect();
        $service = Service::query()->where('slug', 'field-notes')->firstOrFail();

        $this->actingAs($admin)->post("/admin/services/{$service->id}/schedule", [
            'scheduled_for' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->get('/ar/services/field-notes')->assertNotFound();

        $this->travel(2)->hours();
        $this->artisan('content:publish-scheduled')->assertSuccessful();

        $this->get('/ar/services/field-notes')->assertOk();
        $this->assertSame('published', $service->fresh()->status);
    }

    public function test_contact_messages_are_stored_and_spam_is_rejected(): void
    {
        $this->post('/ar/contact', $this->inquiry(now()->timestamp - 10))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inquiries', [
            'email' => 'client@example.com',
            'project_type' => 'web',
            'status' => 'new',
            'locale' => 'ar',
        ]);

        $this->post('/en/contact', $this->inquiry(now()->timestamp - 10, ['company_website' => 'https://spam.test']))
            ->assertSessionHasErrors('company_website');

        $this->post('/ar/contact', $this->inquiry(now()->timestamp))
            ->assertSessionHasErrors('message');

        $this->post('/ar/contact', $this->inquiry(now()->timestamp - 10, ['consent' => null, 'email' => '']))
            ->assertSessionHasErrors(['consent', 'email']);

        $this->assertSame(1, Inquiry::query()->count());
    }

    public function test_media_accepts_images_and_rejects_other_files(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('content-manager');

        $this->actingAs($admin)->post('/admin/media', [
            'file' => UploadedFile::fake()->create('shell.php', 20, 'application/x-php'),
            'alt' => ['ar' => 'لا', 'en' => 'No'],
        ])->assertSessionHasErrors('file');

        $this->actingAs($admin)->post('/admin/media', [
            'file' => UploadedFile::fake()->image('plate.png', 120, 80),
            'alt' => ['ar' => 'لوحة', 'en' => 'Plate'],
        ])->assertRedirect();

        $this->assertSame(1, Media::query()->count());
        $media = Media::query()->firstOrFail();
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_only_authorized_users_change_settings_and_maintenance_hides_the_site(): void
    {
        $viewer = $this->makeUser('viewer');
        $admin = $this->makeUser('super-admin');

        $this->actingAs($viewer)->put('/admin/settings', $this->settings())->assertForbidden();

        $this->actingAs($admin)->put('/admin/settings', $this->settings(true))->assertRedirect();
        $this->assertTrue((bool) SiteSetting::query()->where('key', 'maintenance')->firstOrFail()->value['enabled']);

        auth()->logout();
        $this->get('/ar')->assertStatus(503)->assertInertia(fn (Assert $page) => $page
            ->component('website/Error')
            ->where('status', 503));

        $this->get('/admin/login')->assertOk();
        app(SettingsRepository::class)->forget();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function inquiry(int $startedAt, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rawan',
            'email' => 'client@example.com',
            'phone' => '+96890000000',
            'company' => '',
            'project_type' => 'web',
            'message' => 'A real project note that should be stored.',
            'consent' => true,
            'started_at' => $startedAt,
            'company_website' => '',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $slug = 'web-platforms'): array
    {
        return [
            'slug' => $slug,
            'name' => ['ar' => 'منصات الويب', 'en' => 'Web platforms'],
            'summary' => ['ar' => 'ملخص تجريبي', 'en' => 'A demonstration summary'],
            'description' => [],
            'features' => [],
            'technology_ids' => [],
            'seo' => [
                'title' => ['ar' => 'منصات', 'en' => 'Platforms'],
                'description' => ['ar' => 'وصف', 'en' => 'Description'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(bool $maintenance = false): array
    {
        return [
            'brand' => [
                'midnight' => '#210C17',
                'burgundy' => '#5B2038',
                'rose' => '#A83F67',
                'blush' => '#E8A3BB',
                'ivory' => '#F8F0F3',
            ],
            'company' => ['legal_name' => 'RACODE'],
            'contact' => [
                'email' => 'hello@racode.om',
                'phone' => '',
                'notify_email' => '',
                'address' => ['ar' => 'سلطنة عُمان', 'en' => 'Sultanate of Oman'],
            ],
            'social' => ['linkedin' => '', 'instagram' => '', 'x' => '', 'github' => '', 'youtube' => ''],
            'footer' => ['ar' => 'راكود', 'en' => 'RACODE'],
            'seo' => [
                'title' => ['ar' => 'راكود', 'en' => 'RACODE'],
                'description' => ['ar' => 'وصف', 'en' => 'Description'],
            ],
            'maintenance' => ['enabled' => $maintenance],
            'analytics' => ['measurement_id' => '', 'plausible_domain' => ''],
            'logo' => ['media_id' => null],
            'favicon' => ['media_id' => null],
        ];
    }
}
