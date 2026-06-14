<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Http;

it('sends a slack notification', function () {
    Http::fake();
    
    $service = app(SlackNotificationService::class);
    $service->send('https://hooks.slack.com/services/T000/B000/XXX', ['text' => 'Hello']);
    
    Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
        return $request->url() === 'https://hooks.slack.com/services/T000/B000/XXX' &&
               $request['text'] === 'Hello';
    });
});
