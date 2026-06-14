<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\UpdateProjectAction;
use App\Models\Project;

it('updates a project', function () {
    $project = Project::factory()->create([
        'name' => 'Old Name',
        'description' => 'Old Desc',
    ]);
    
    $action = app(UpdateProjectAction::class);
    $updatedProject = $action->execute($project, [
        'name' => 'New Name',
    ]);
    
    expect($updatedProject->name)->toBe('New Name')
        ->and($updatedProject->description)->toBe('Old Desc');
});
