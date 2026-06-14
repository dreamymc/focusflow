<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\DeleteProjectAction;
use App\Models\Project;

it('deletes a project', function () {
    $project = Project::factory()->create();
    
    $action = app(DeleteProjectAction::class);
    $result = $action->execute($project);
    
    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});
