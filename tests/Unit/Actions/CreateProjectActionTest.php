<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\CreateProjectAction;
use App\Models\Workspace;

it('creates a project', function () {
    $workspace = Workspace::factory()->create();
    
    $action = app(CreateProjectAction::class);
    $project = $action->execute($workspace, [
        'name' => 'New Project',
        'description' => 'Project Description'
    ]);
    
    expect($project->name)->toBe('New Project')
        ->and($project->workspace_id)->toBe($workspace->id);
});
