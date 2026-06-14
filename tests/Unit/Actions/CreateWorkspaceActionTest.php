<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\CreateWorkspaceAction;
use App\Models\User;
use App\Enums\WorkspaceRole;

it('creates a workspace and assigns user as admin', function () {
    $user = User::factory()->create();
    
    $action = app(CreateWorkspaceAction::class);
    $workspace = $action->execute('My Workspace', $user);
    
    expect($workspace->name)->toBe('My Workspace');
    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Admin->value,
    ]);
});
