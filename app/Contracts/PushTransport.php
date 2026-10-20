<?php

namespace App\Contracts;

use App\Models\PushSubscription;

interface PushTransport
{
    /** Returns sent, expired, or failed. Never return/log endpoint secrets. */
    public function send(PushSubscription $subscription, array $payload): string;
}
