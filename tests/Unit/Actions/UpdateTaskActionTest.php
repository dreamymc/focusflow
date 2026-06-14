<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\UpdateTaskAction;
use App\Models\Task;
use App\Models\User;

it('updates a task', function () {
    $task = Task::factory()->create([
        'title' => 'Old Title',
    ]);
    $user = User::factory()->create();
    
    $action = app(UpdateTaskAction::class);
    $updatedTask = $action->execute($task, [
        'title' => 'New Title',
        'assignee_ids' => [$user->id],
    ]);
    
    expect($updatedTask->title)->toBe('New Title');
    // Assuming assignees() relation is correct
    $this->assertCount(1, $updatedTask->assignees);
});
