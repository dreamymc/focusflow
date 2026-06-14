<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\MoveTaskAction;
use App\Models\Task;
use App\Enums\TaskStatus;
use Illuminate\Support\Facades\Event;
use App\Events\TaskMoved;
use App\Events\TaskCompleted;

it('moves a task', function () {
    Event::fake();
    
    $task = Task::factory()->create(['status' => TaskStatus::Backlog->value]);
    
    $action = app(MoveTaskAction::class);
    $updatedTask = $action->execute($task, TaskStatus::InProgress);
    
    expect($updatedTask->status->value)->toBe(TaskStatus::InProgress->value);
    
    Event::assertDispatched(TaskMoved::class, function ($event) use ($task) {
        return $event->task->id === $task->id;
    });
    Event::assertNotDispatched(TaskCompleted::class);
});

it('dispatches TaskCompleted when moved to Done', function () {
    Event::fake();
    
    $task = Task::factory()->create(['status' => TaskStatus::InProgress->value]);
    
    $action = app(MoveTaskAction::class);
    $updatedTask = $action->execute($task, TaskStatus::Done);
    
    expect($updatedTask->status->value)->toBe(TaskStatus::Done->value);
    
    Event::assertDispatched(TaskCompleted::class, function ($event) use ($task) {
        return $event->task->id === $task->id;
    });
});
