<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Support\WebPushTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Minishlink\WebPush\VAPID;
use Tests\TestCase;

class WebPushTransportTest extends TestCase
{
    public function test_actual_web_push_payload_is_encrypted_signed_and_expiration_is_handled(): void
    {
        $keys = VAPID::createVapidKeys();
        $device = VAPID::createVapidKeys();
        config(['webpush.public_key' => $keys['publicKey'], 'webpush.private_key' => $keys['privateKey'], 'webpush.subject' => 'https://school.example']);
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201), new Response(410)]));
        $stack->push(Middleware::history($history));
        $transport = new WebPushTransport(new Client(['handler' => $stack, 'allow_redirects' => false]));
        $subscription = new PushSubscription(['endpoint' => 'https://fcm.googleapis.com/fcm/send/example-device',
            'public_key' => $device['publicKey'], 'auth_token' => rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=')]);
        $payload = ['title' => 'School', 'body' => 'A consultation was updated.', 'url' => '/consultations'];
        $this->assertSame('sent', $transport->send($subscription, $payload));
        $request = $history[0]['request'];
        $this->assertSame('aes128gcm', $request->getHeaderLine('Content-Encoding'));
        $this->assertStringContainsString('vapid', $request->getHeaderLine('Authorization'));
        $this->assertStringNotContainsString($payload['body'], (string) $request->getBody());
        $this->assertSame('expired', $transport->send($subscription, $payload));
    }
}
