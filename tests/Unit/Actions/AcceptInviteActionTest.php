<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\AcceptInviteAction;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\WorkspaceRole;
use App\Enums\InviteStatus;

it('accepts an invitation and adds user to workspace', function () {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['email' => 'test@example.com']);
    
    $invitation = Invitation::create([
        'workspace_id' => $workspace->id,
        'email' => 'test@example.com',
        'role' => WorkspaceRole::Member->value,
        'token' => 'test-token',
        'status' => InviteStatus::Pending->value,
    ]);

    $action = app(AcceptInviteAction::class);
    $resultWorkspace = $action->execute('test-token', $user);

    expect($resultWorkspace->id)->toBe($workspace->id);
    $this->assertDatabaseMissing('invitations', ['token' => 'test-token']);
    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Member->value,
    ]);
});
