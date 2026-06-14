<?php

use App\Models\User;
use App\Models\Workspace;
use App\Enums\WorkspaceRole;

it('returns a billing portal url', function () {
    [$workspace, $user] = createWorkspaceWithUser(WorkspaceRole::Admin);
    $workspace->update(['stripe_id' => 'cus_123']);

    // Partial mock to bypass actual Stripe API call
    $workspaceMock = Mockery::mock($workspace)->makePartial();
    $workspaceMock->shouldReceive('billingPortalUrl')
        ->andReturn('https://billing.stripe.com/p/session/123');
    
    \Illuminate\Support\Facades\Route::bind('workspace', function ($value) use ($workspaceMock, $workspace) {
        return $value == $workspace->id ? $workspaceMock : Workspace::findOrFail($value);
    });

    $response = $this->actingAs($user)
        ->getJson("/api/v1/workspaces/{$workspace->id}/billing-portal");

    $response->assertOk()
        ->assertJsonPath('data.url', 'https://billing.stripe.com/p/session/123');
});
