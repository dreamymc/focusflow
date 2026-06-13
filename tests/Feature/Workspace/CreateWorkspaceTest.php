<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

it('creates a workspace successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/workspaces', [
            'name' => 'My New Workspace',
        ]);

    $response->assertCreated()
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('data.id')
                 ->where('data.name', 'My New Workspace')
                 ->etc()
        );

    $this->assertDatabaseHas('workspaces', [
        'name' => 'My New Workspace',
    ]);

    $workspace = Workspace::where('name', 'My New Workspace')->first();

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
});

it('fails to create workspace with missing name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/workspaces', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('requires authentication to create workspace', function () {
    $response = $this->postJson('/api/v1/workspaces', [
        'name' => 'My New Workspace',
    ]);

    $response->assertUnauthorized();
});
