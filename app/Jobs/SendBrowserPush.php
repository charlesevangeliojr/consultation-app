<?php

namespace App\Jobs;

use App\Support\BrowserPush;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBrowserPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $userId, public string $body, public string $url) {}

    public function handle(BrowserPush $push): void
    {
        $push->deliver($this->userId, $this->body, $this->url);
    }
}
