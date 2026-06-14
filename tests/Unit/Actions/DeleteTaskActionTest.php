<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\DeleteTaskAction;
use App\Models\Task;

it('deletes a task', function () {
    $task = Task::factory()->create();
    
    $action = app(DeleteTaskAction::class);
    $result = $action->execute($task);
    
    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});
