<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Services\SlackNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSlackNotification implements ShouldQueue
{
    public string $queue = 'slack';

    public function __construct(private SlackNotificationService $slackService)
    {
    }

    public function handle(TaskCompleted $event): void
    {
        $webhookUrl = config('services.slack.webhook_url', 'https://hooks.slack.com/services/test');
        $this->slackService->send($webhookUrl, [
            'text' => "Task '{$event->task->title}' has been completed!",
        ]);
    }
}
