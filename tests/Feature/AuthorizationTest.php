<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Enums\WorkspaceRole;
use App\Enums\TaskStatus;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

dataset('roles_and_endpoints', function () {
    return [
        // Project endpoints
        [WorkspaceRole::Admin, 'create_project', 201],
        [WorkspaceRole::Member, 'create_project', 201],
        [WorkspaceRole::Viewer, 'create_project', 403],

        [WorkspaceRole::Admin, 'update_project', 200],
        [WorkspaceRole::Member, 'update_project', 200],
        [WorkspaceRole::Viewer, 'update_project', 403],

        [WorkspaceRole::Admin, 'delete_project', 204],
        [WorkspaceRole::Member, 'delete_project', 204],
        [WorkspaceRole::Viewer, 'delete_project', 403],

        // Task endpoints
        [WorkspaceRole::Admin, 'create_task', 201],
        [WorkspaceRole::Member, 'create_task', 201],
        [WorkspaceRole::Viewer, 'create_task', 403],

        [WorkspaceRole::Admin, 'update_task', 200],
        [WorkspaceRole::Member, 'update_task', 200],
        [WorkspaceRole::Viewer, 'update_task', 403],

        [WorkspaceRole::Admin, 'delete_task', 204],
        [WorkspaceRole::Member, 'delete_task', 204],
        [WorkspaceRole::Viewer, 'delete_task', 403],

        [WorkspaceRole::Admin, 'move_task', 200],
        [WorkspaceRole::Member, 'move_task', 200],
        [WorkspaceRole::Viewer, 'move_task', 403],
    ];
});

it('enforces role authorization matrix', function (WorkspaceRole $role, string $action, int $expectedStatus) {
    [$workspace, $user] = createWorkspaceWithUser($role);
    
    $project = Project::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Project',
    ]);
    
    $task = Task::create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'title' => 'Test Task',
        'status' => TaskStatus::Backlog->value,
    ]);

    $response = $this->actingAs($user);

    switch ($action) {
        case 'create_project':
            $response = $response->postJson("/api/v1/workspaces/{$workspace->id}/projects", [
                'name' => 'New Project',
            ]);
            break;
        case 'update_project':
            $response = $response->putJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}", [
                'name' => 'Updated Project',
            ]);
            break;
        case 'delete_project':
            $response = $response->deleteJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}");
            break;
        case 'create_task':
            $response = $response->postJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}/tasks", [
                'title' => 'New Task',
            ]);
            break;
        case 'update_task':
            $response = $response->putJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Updated Task',
            ]);
            break;
        case 'delete_task':
            $response = $response->deleteJson("/api/v1/workspaces/{$workspace->id}/projects/{$project->id}/tasks/{$task->id}");
            break;
        case 'move_task':
            $response = $response->putJson("/api/v1/workspaces/{$workspace->id}/tasks/{$task->id}/move", [
                'status' => TaskStatus::Done->value,
            ]);
            break;
    }

    $response->assertStatus($expectedStatus);
})->with('roles_and_endpoints');
