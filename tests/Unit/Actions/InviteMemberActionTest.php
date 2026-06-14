<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\InviteMemberAction;
use App\Models\Workspace;
use App\Enums\WorkspaceRole;
use App\Enums\InviteStatus;

it('invites a member', function () {
    $workspace = Workspace::factory()->create();
    
    $action = app(InviteMemberAction::class);
    $invitation = $action->execute($workspace, 'invitee@example.com', WorkspaceRole::Member);
    
    expect($invitation->email)->toBe('invitee@example.com')
        ->and($invitation->role->value)->toBe(WorkspaceRole::Member->value)
        ->and($invitation->status->value)->toBe(InviteStatus::Pending->value);
});
