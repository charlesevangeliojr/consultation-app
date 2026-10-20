<?php

namespace Tests\Feature;

use App\Contracts\PushTransport;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PushSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
    }

    private function configured(): void
    {
        config(['webpush.enabled' => true, 'webpush.public_key' => 'test-public-key', 'webpush.private_key' => 'test-private-key', 'webpush.subject' => 'https://school.example']);
    }

    private function subscription(string $device = 'example-device'): array
    {
        return ['endpoint' => 'https://fcm.googleapis.com/fcm/send/'.$device, 'keys' => [
            'p256dh' => rtrim(strtr(base64_encode("\x04".str_repeat("\x01", 64)), '+/', '-_'), '='),
            'auth' => rtrim(strtr(base64_encode(str_repeat("\x02", 16)), '+/', '-_'), '='),
        ]];
    }

    public function test_subscription_is_owned_encrypted_and_removable_only_by_its_account(): void
    {
        $this->configured();
        $student = $this->student();
        $other = $this->student();
        $data = $this->subscription();
        $this->actingAs($student)->postJson(route('notifications.store'), $data)->assertOk()->assertJson(['subscribed' => true]);
        $stored = PushSubscription::firstOrFail();
        $this->assertSame($student->id, $stored->user_id);
        $this->assertSame($data['endpoint'], $stored->endpoint);
        $this->assertNotSame($data['endpoint'], DB::table('push_subscriptions')->value('endpoint'));
        $this->assertNotSame($data['keys']['auth'], DB::table('push_subscriptions')->value('auth_token'));
        $this->assertArrayNotHasKey('endpoint', $stored->toArray());
        $this->postJson(route('notifications.status'), ['endpoint' => $data['endpoint']])->assertOk()->assertJson(['subscribed' => true]);
        $this->actingAs($other)->postJson(route('notifications.status'), ['endpoint' => $data['endpoint']])->assertOk()->assertJson(['subscribed' => false]);
        $this->deleteJson(route('notifications.destroy'), ['endpoint' => $data['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->actingAs($student)->deleteJson(route('notifications.destroy'), ['endpoint' => $data['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_arbitrary_urls_and_malformed_keys_are_rejected_without_network_requests(): void
    {
        $this->configured();
        $this->actingAs($this->student());
        foreach (['http://fcm.googleapis.com/example', 'https://127.0.0.1/push', 'https://169.254.169.254/push', 'https://fcm.googleapis.com.evil.example/push', 'https://fcm.googleapis.com:8443/push', 'https://user:pass@fcm.googleapis.com/push'] as $endpoint) {
            $data = $this->subscription();
            $data['endpoint'] = $endpoint;
            $this->postJson(route('notifications.store'), $data)->assertUnprocessable();
        }
        $this->postJson(route('notifications.store'), ['endpoint' => $this->subscription()['endpoint'], 'keys' => ['p256dh' => 'bad', 'auth' => 'bad']])->assertUnprocessable();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_endpoint_alone_cannot_hijack_another_account_but_device_secret_can_rebind(): void
    {
        $this->configured();
        $first = $this->student();
        $second = $this->student();
        $data = $this->subscription();
        $this->actingAs($first)->postJson(route('notifications.store'), $data)->assertOk();
        $changed = $data;
        $changed['keys']['auth'] = rtrim(strtr(base64_encode(str_repeat("\x03", 16)), '+/', '-_'), '=');
        $this->actingAs($second)->postJson(route('notifications.store'), $changed)->assertForbidden();
        $this->assertSame($first->id, PushSubscription::first()->user_id);
        $this->postJson(route('notifications.store'), $data)->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertSame($second->id, PushSubscription::first()->user_id);
    }

    public function test_logout_detaches_current_device_but_not_other_devices(): void
    {
        $this->configured();
        $this->actingAs($this->student());
        $this->postJson(route('notifications.store'), $this->subscription('another-device'))->assertOk();
        $this->postJson(route('notifications.store'), $this->subscription('current-device'))->assertOk();
        $this->post(route('consultations.logout'))->assertRedirect(route('portal.login'));
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertSame(hash('sha256', $this->subscription('another-device')['endpoint']), PushSubscription::first()->endpoint_hash);
    }

    public function test_settings_and_endpoints_require_login_and_work_for_incomplete_teachers(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('portal.login'));
        $this->postJson(route('notifications.store'), $this->subscription())->assertUnauthorized();
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $teacher->teacher()->create();
        $this->actingAs($teacher)->get(route('notifications.settings'))->assertOk()->assertSee('Enable notifications')->assertSee('iOS/iPadOS 16.4');
        $this->postJson(route('notifications.store'), $this->subscription())->assertStatus(503);
        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('notifications.settings'))->assertOk()->assertSee('Notifications on this device');
    }

    public function test_device_controls_are_in_settings_not_admin_dashboard_or_inbox(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('data-push-test', false)->assertSee('data-notification-bell', false);
        $this->get(route('admin.settings'))->assertOk()->assertSee('Send test notification')
            ->assertSee('data-push-test', false)->assertSee('data-push-enable', false)
            ->assertSee('this device')->assertSee(route('notifications.index'), false)
            ->assertDontSee('Send to all users');
        $this->get(route('notifications.index'))->assertOk()->assertDontSee('data-push-test', false)->assertSee('Notification settings');
    }

    public function test_missing_push_table_returns_setup_guidance_instead_of_sql_errors(): void
    {
        $this->configured();
        $this->actingAs($this->student());
        \Illuminate\Support\Facades\Schema::drop('push_subscriptions');
        $this->postJson(route('notifications.store'), $this->subscription())->assertStatus(503)->assertSee('database setup is missing')->assertDontSee('SQLSTATE');
        $this->postJson(route('notifications.status'), ['endpoint' => $this->subscription()['endpoint']])->assertStatus(503);
        $this->get(route('notifications.settings'))->assertOk()->assertSee('Notification database setup is missing');
        $this->deleteJson(route('notifications.destroy'), ['endpoint' => $this->subscription()['endpoint']])->assertOk();
    }

    public function test_send_test_is_limited_to_own_current_device_and_removes_expired_subscriptions(): void
    {
        $this->configured();
        $student = $this->student();
        $data = $this->subscription();
        $this->actingAs($student)->postJson(route('notifications.store'), $data)->assertOk();
        $transport = new class implements PushTransport {
            public array $payloads = [];
            public function send(PushSubscription $subscription, array $payload): string { $this->payloads[] = $payload; return 'sent'; }
        };
        $this->app->instance(PushTransport::class, $transport);
        $this->postJson(route('notifications.test'), ['endpoint' => $data['endpoint']])->assertOk()->assertJson(['sent' => true]);
        $this->assertCount(1, $transport->payloads);
        $this->assertStringNotContainsString($student->email, json_encode($transport->payloads));
        $this->app->instance(PushTransport::class, new class implements PushTransport {
            public function send(PushSubscription $subscription, array $payload): string { return 'expired'; }
        });
        $this->postJson(route('notifications.test'), ['endpoint' => $data['endpoint']])->assertStatus(502)->assertJson(['sent' => false]);
        $this->assertDatabaseCount('push_subscriptions', 0);
    }
}
