<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Support\BrowserPush;
use App\Support\PushEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PushSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isTeacher() || $user->isStudent() || $user->hasAdminAccess(), 403);
        return view('notification-settings', ['notificationLayout' => $user->isTeacher() || $user->isStudent() ? 'layouts.consultations' : 'admin.layout']);
    }

    private function endpoint(Request $request): string
    {
        abort_unless($request->user()->isTeacher() || $request->user()->isStudent() || $request->user()->hasAdminAccess(), 403);
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);
        if (! PushEndpoint::allowed($data['endpoint'])) {
            throw ValidationException::withMessages(['endpoint' => 'This is not a supported browser push endpoint.']);
        }
        return $data['endpoint'];
    }

    public function status(Request $request): JsonResponse
    {
        $endpoint = $this->endpoint($request);
        if (!Schema::hasTable('push_subscriptions')) return response()->json(['subscribed' => false, 'message' => 'Notification database setup is missing. Run migrations.'], 503);
        $subscription = PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $endpoint))
            ->where('vapid_key_hash', hash('sha256', (string) config('webpush.public_key')))->first();
        if ($subscription) $request->session()->put('push_subscription_id', $subscription->id);
        return response()->json(['subscribed' => (bool) $subscription])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(Schema::hasTable('push_subscriptions'), 503, 'Notification database setup is missing. Run migrations before enabling push.');
        abort_unless(BrowserPush::configured(), 503, 'The school has not configured push delivery yet.');
        $endpoint = $this->endpoint($request);
        $data = $request->validate([
            'keys.p256dh' => ['required', 'string', 'max:100', 'regex:/\A[A-Za-z0-9_-]+={0,2}\z/'],
            'keys.auth' => ['required', 'string', 'max:30', 'regex:/\A[A-Za-z0-9_-]+={0,2}\z/'],
        ]);
        $public = base64_decode(strtr($data['keys']['p256dh'], '-_', '+/'), true);
        $auth = base64_decode(strtr($data['keys']['auth'], '-_', '+/'), true);
        if ($public === false || strlen($public) !== 65 || $public[0] !== "\x04" || $auth === false || strlen($auth) !== 16) {
            throw ValidationException::withMessages(['keys' => 'Invalid browser subscription keys.']);
        }
        $subscription = DB::transaction(function () use ($request, $endpoint, $data) {
            $subscription = PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->lockForUpdate()->first();
            if ($subscription && $subscription->user_id !== $request->user()->id) {
                // Owning only a leaked endpoint URL must not allow account reassignment.
                abort_unless(hash_equals($subscription->public_key, $data['keys']['p256dh']) && hash_equals($subscription->auth_token, $data['keys']['auth']), 403);
            }
            if ((! $subscription || $subscription->user_id !== $request->user()->id) && PushSubscription::where('user_id', $request->user()->id)->count() >= 20) {
                throw ValidationException::withMessages(['endpoint' => 'Too many subscribed devices. Disable notifications on an unused device first.']);
            }
            $subscription ??= new PushSubscription;
            $subscription->fill([
                'user_id' => $request->user()->id, 'endpoint_hash' => hash('sha256', $endpoint), 'endpoint' => $endpoint,
                'public_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth'],
                'vapid_key_hash' => hash('sha256', config('webpush.public_key')),
            ])->save();
            return $subscription;
        });
        $request->session()->put('push_subscription_id', $subscription->id);
        return response()->json(['subscribed' => true])->header('Cache-Control', 'no-store');
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = $this->endpoint($request);
        if (Schema::hasTable('push_subscriptions')) PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $endpoint))->delete();
        $request->session()->forget('push_subscription_id');
        return response()->json(['subscribed' => false]);
    }

    public function test(Request $request, BrowserPush $push): JsonResponse
    {
        $endpoint = $this->endpoint($request);
        abort_unless(Schema::hasTable('push_subscriptions'), 503, 'Notification database setup is missing. Run migrations before testing push.');
        $sent = $push->deliver($request->user()->id, 'Notifications are enabled for this account on this device.',
            $request->user()->isTeacher() || $request->user()->isStudent() ? '/consultations' : '/admin', hash('sha256', $endpoint));
        return response()->json(['sent' => $sent, 'message' => $sent
            ? 'Test accepted by the push service. Check your device notifications.'
            : 'Test could not be delivered. Check server setup, device permission, and subscription.'], $sent ? 200 : 502);
    }

    public static function forgetSession(Request $request): void
    {
        if (Schema::hasTable('push_subscriptions') && ($id = $request->session()->get('push_subscription_id'))) {
            PushSubscription::where('id', $id)->where('user_id', $request->user()->id)->delete();
            $request->session()->forget('push_subscription_id');
        }
    }
}
