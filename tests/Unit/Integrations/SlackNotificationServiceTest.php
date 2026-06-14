<?php

use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Http;

it('sends a webhook to slack', function () {
    Http::fake([
        '*' => Http::response('ok', 200),
    ]);

    $service = new SlackNotificationService();
    $service->send('https://hooks.slack.com/services/test', ['text' => 'Hello World']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://hooks.slack.com/services/test'
            && $request['text'] === 'Hello World';
    });
});
