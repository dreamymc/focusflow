<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('accepts an invite successfully', function () {
    $user = User::factory()->create(['email' => 'invited@example.com']);
    $workspace = Workspace::factory()->create();
    
    $token = Str::random(32);
    DB::table('workspace_invites')->insert([
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'role' => 'member',
        'token' => $token,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/workspaces/invites/{$token}/accept");

    $response->assertOk()
        ->assertJson(['message' => 'Invite accepted']);

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'member',
    ]);

    $this->assertDatabaseMissing('workspace_invites', [
        'token' => $token,
    ]);
});

it('rejects invalid invite tokens', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/v1/workspaces/invites/invalid-token/accept");

    $response->assertNotFound();
});

it('requires authenticated user to have matching email', function () {
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $workspace = Workspace::factory()->create();
    
    $token = Str::random(32);
    DB::table('workspace_invites')->insert([
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'role' => 'member',
        'token' => $token,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($wrongUser)
        ->postJson("/api/v1/workspaces/invites/{$token}/accept");

    $response->assertForbidden();
});
