<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Services\SlackNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSlackNotification implements ShouldQueue
{
    public function __construct(private SlackNotificationService $slackService)
    {
    }

    public function handle(TaskCompleted $event): void
    {
        $this->slackService->send('https://hooks.slack.com/services/test', [
            'text' => "Task '{$event->task->title}' has been completed!",
        ]);
    }
}
