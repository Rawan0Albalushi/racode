<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_guests_are_sent_to_the_login_screen(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('admin/Login'));
    }

    public function test_an_active_admin_can_sign_in_and_out(): void
    {
        $admin = $this->makeUser('super-admin', ['email' => 'admin@racode.test']);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page->component('admin/Dashboard'));

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        $user = $this->makeUser('editor', [
            'email' => 'paused@racode.test',
            'is_active' => false,
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_login_failures_are_throttled(): void
    {
        $this->makeUser('viewer', ['email' => 'viewer@racode.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', [
                'email' => 'viewer@racode.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/admin/login', [
            'email' => 'viewer@racode.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_a_viewer_cannot_create_content(): void
    {
        $viewer = $this->makeUser('viewer');

        $this->actingAs($viewer)->post('/admin/services', $this->servicePayload())->assertForbidden();
    }

    public function test_an_editor_cannot_publish(): void
    {
        $admin = $this->makeUser('super-admin');
        $editor = $this->makeUser('editor');

        $this->actingAs($admin)->post('/admin/services', $this->servicePayload())->assertRedirect();
        $serviceId = \App\Models\Service::query()->firstOrFail()->id;

        $this->actingAs($editor)->post("/admin/services/{$serviceId}/publish")->assertForbidden();
    }

    public function test_the_last_active_super_admin_cannot_be_removed(): void
    {
        $admin = $this->makeUser('super-admin', ['email' => 'only@racode.test']);

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'locale' => 'ar',
            'is_active' => false,
            'role' => 'super-admin',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHasErrors('role');
        $this->assertTrue(User::query()->whereKey($admin->id)->where('is_active', true)->exists());
    }

    public function test_user_creation_does_not_log_the_password(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Editor Two',
            'email' => 'editor.two@racode.test',
            'locale' => 'en',
            'is_active' => true,
            'role' => 'editor',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ])->assertRedirect(route('admin.users.index'));

        $log = ActivityLog::query()->where('action', 'user.created')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('Str0ng-Passw0rd!', json_encode($log->properties));
    }

    /**
     * @return array<string, mixed>
     */
    private function servicePayload(): array
    {
        return [
            'slug' => 'web-platforms',
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
}
