<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SlackNotificationService
{
    public function send(string $webhookUrl, array $payload): void
    {
        Http::post($webhookUrl, $payload);
    }
}
