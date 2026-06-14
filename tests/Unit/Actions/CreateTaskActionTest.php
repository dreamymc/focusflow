<?php

use App\Actions\CreateTaskAction;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Models\User;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a task with correct data', function () {
    $workspace = Workspace::factory()->create();
    $project = Project::create([
        'workspace_id' => $workspace->id,
        'name' => 'Project A',
    ]);

    $action = new CreateTaskAction();
    $task = $action->execute($project, [
        'title' => 'New Task',
        'description' => 'Task description',
        'status' => TaskStatus::InProgress->value,
        'priority' => TaskPriority::High->value,
    ]);

    $this->assertInstanceOf(Task::class, $task);
    $this->assertEquals('New Task', $task->title);
    $this->assertEquals('Task description', $task->description);
    $this->assertEquals(TaskStatus::InProgress, $task->status);
    $this->assertEquals(TaskPriority::High, $task->priority);
    $this->assertEquals($project->id, $task->project_id);
    $this->assertEquals($workspace->id, $task->workspace_id);
});

it('creates a task with default status and priority', function () {
    $workspace = Workspace::factory()->create();
    $project = Project::create([
        'workspace_id' => $workspace->id,
        'name' => 'Project A',
    ]);

    $action = new CreateTaskAction();
    $task = $action->execute($project, [
        'title' => 'New Task',
    ]);

    $this->assertEquals(TaskStatus::Backlog, $task->status);
    $this->assertEquals(TaskPriority::Medium, $task->priority);
});
