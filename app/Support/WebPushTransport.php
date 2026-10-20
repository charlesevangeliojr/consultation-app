<?php

namespace App\Support;

use App\Contracts\PushTransport;
use App\Models\PushSubscription;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushTransport implements PushTransport
{
    public function __construct(private ?\Psr\Http\Client\ClientInterface $client = null) {}

    public function send(PushSubscription $subscription, array $payload): string
    {
        // Validate again before sending, including previously stored subscriptions.
        if (! PushEndpoint::allowed($subscription->endpoint)) return 'expired';
        $client = $this->client ?? new Client(['timeout' => 5, 'connect_timeout' => 3, 'allow_redirects' => false]);
        $factory = new HttpFactory;
        $push = new WebPush(['VAPID' => [
            'subject' => config('webpush.subject'),
            'publicKey' => config('webpush.public_key'),
            'privateKey' => config('webpush.private_key'),
        ]], ['TTL' => 3600, 'urgency' => 'normal'], $client, $factory, $factory, null, new \Psr\Log\NullLogger);
        $report = $push->sendOneNotification(Subscription::create([
            'endpoint' => $subscription->endpoint,
            'keys' => ['p256dh' => $subscription->public_key, 'auth' => $subscription->auth_token],
            'contentEncoding' => 'aes128gcm',
        ]), json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $report->isSuccess() ? 'sent' : ($report->isSubscriptionExpired() ? 'expired' : 'failed');
    }
}
