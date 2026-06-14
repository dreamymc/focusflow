<?php

use App\Events\TaskCompleted;
use App\Listeners\SendSlackNotification;
use App\Models\Task;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

it('registers SendSlackNotification listener for TaskCompleted event', function () {
    Event::fake();

    Event::assertListening(
        TaskCompleted::class,
        SendSlackNotification::class
    );
});

it('sends slack notification when listener handles event', function () {
    $task = Task::factory()->create();
    $event = new TaskCompleted($task);
    
    $this->mock(SlackNotificationService::class, function (MockInterface $mock) {
        $mock->shouldReceive('send')->once();
    });

    $listener = app(SendSlackNotification::class);
    $listener->handle($event);
});
