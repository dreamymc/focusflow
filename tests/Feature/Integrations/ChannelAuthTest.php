<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('authorizes workspace members for workspace channel', function () {
    $workspace = Workspace::factory()->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member, ['role' => 'member']);
    
    $nonMember = User::factory()->create();

    // Test member access to private channel
    $response = $this->actingAs($member)->postJson('/broadcasting/auth', [
        'channel_name' => "private-workspace.{$workspace->id}",
        'socket_id' => '1234.5678',
    ]);
    
    $response->assertSuccessful();
    
    // Test non-member access to private channel
    $response2 = $this->actingAs($nonMember)->postJson('/broadcasting/auth', [
        'channel_name' => "private-workspace.{$workspace->id}",
        'socket_id' => '1234.5678',
    ]);
    
    $response2->assertForbidden();
});

it('authorizes workspace members for task presence channel', function () {
    $workspace = Workspace::factory()->create();
    $member = User::factory()->create();
    $workspace->users()->attach($member, ['role' => 'member']);
    
    $project = Project::create([
        'workspace_id' => $workspace->id,
        'name' => 'Project A',
    ]);
    
    $task = Task::create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'title' => 'Test Task',
    ]);
    
    $nonMember = User::factory()->create();

    // Test member access to presence channel
    $response = $this->actingAs($member)->postJson('/broadcasting/auth', [
        'channel_name' => "presence-task.{$task->id}",
        'socket_id' => '1234.5678',
    ]);
    
    $response->assertSuccessful();
    $response->assertJsonStructure(['auth', 'channel_data']);

    // Test non-member access to presence channel
    $response2 = $this->actingAs($nonMember)->postJson('/broadcasting/auth', [
        'channel_name' => "presence-task.{$task->id}",
        'socket_id' => '1234.5678',
    ]);
    
    $response2->assertForbidden();
});
