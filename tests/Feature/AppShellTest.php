<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Task;
use App\Enums\WorkspaceRole;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('unauthenticated users are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users are redirected to dashboard from root', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
});

test('authenticated users can render dashboard page with shared properties', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['name' => 'Acme Inc']);
    
    // Attach workspace membership with pivot role
    $workspace->users()->attach($user->id, ['role' => WorkspaceRole::Admin->value]);
    
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Main App',
    ]);
    
    $task = Task::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'title' => 'Implement App Shell',
        'status' => TaskStatus::InProgress,
    ]);
    $task->assignees()->attach($user->id);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('stats', fn (Assert $stats) => $stats
            ->where('totalTasks', 1)
            ->where('activeTasks', 1)
            ->where('completedToday', 0)
        )
        ->has('recentTasks', 1)
        ->has('recentTasks.0', fn (Assert $rt) => $rt
            ->where('title', 'Implement App Shell')
            ->where('status', 'in_progress')
            ->where('project_name', 'Main App')
            ->etc()
        )
        // Check shared data
        ->has('workspaces', 1)
        ->where('workspaces.0.name', 'Acme Inc')
        ->where('currentWorkspace.id', $workspace->id)
        ->where('currentWorkspace.name', 'Acme Inc')
        ->has('currentWorkspace.projects', 1)
        ->where('currentWorkspace.projects.0.name', 'Main App')
        ->where('userRole', 'admin')
    );
});
