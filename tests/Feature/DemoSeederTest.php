<?php

use App\Models\User;
use App\Models\Workspace;
use App\Enums\WorkspaceRole;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the database and correctly assigns Spatie roles matching pivot roles under workspace teams', function () {
    // Run the DemoSeeder
    $this->seed(DemoSeeder::class);

    // Get the seeded workspaces
    $workspaceCamp = Workspace::where('name', 'MCBERNARD CAMP')->first();
    $workspaceRussel = Workspace::where('name', 'Vance Russel LLC')->first();

    expect($workspaceCamp)->not->toBeNull();
    expect($workspaceRussel)->not->toBeNull();

    // Check users for MCBERNARD CAMP
    $usersCamp = $workspaceCamp->users()->withPivot('role')->get();
    expect($usersCamp)->toHaveCount(5);

    $registrar = app(PermissionRegistrar::class);

    foreach ($usersCamp as $user) {
        $pivotRole = $user->pivot->role; // Admin, Member, or Viewer (string value)

        // Set the team scope to MCBERNARD CAMP
        $registrar->setPermissionsTeamId($workspaceCamp->id);

        // Verify the user has the Spatie role assigned under this team
        expect($user->hasRole($pivotRole))->toBeTrue();

        // Let's assert the Spatie model_has_roles table record directly to be 100% sure
        $roleModel = Role::where('name', $pivotRole)->where('team_id', $workspaceCamp->id)->first();
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $roleModel->id,
            'model_type' => User::class,
            'model_id' => $user->id,
            'team_id' => $workspaceCamp->id,
        ]);
    }

    // Check users for Vance Russel LLC
    $usersRussel = $workspaceRussel->users()->withPivot('role')->get();
    expect($usersRussel)->toHaveCount(5);

    foreach ($usersRussel as $user) {
        $pivotRole = $user->pivot->role; // Admin, Member, or Viewer (string value)

        // Set the team scope to Vance Russel LLC
        $registrar->setPermissionsTeamId($workspaceRussel->id);

        // Verify the user has the Spatie role assigned under this team
        expect($user->hasRole($pivotRole))->toBeTrue();

        // Verify direct table record
        $roleModel = Role::where('name', $pivotRole)->where('team_id', $workspaceRussel->id)->first();
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $roleModel->id,
            'model_type' => User::class,
            'model_id' => $user->id,
            'team_id' => $workspaceRussel->id,
        ]);
    }
});
