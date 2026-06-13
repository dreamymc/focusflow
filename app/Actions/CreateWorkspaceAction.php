<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Enums\WorkspaceRole;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateWorkspaceAction
{
    public function execute(string $name, User $user): Workspace
    {
        $workspace = Workspace::create([
            'name' => $name,
        ]);

        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::Admin->value,
        ]);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($workspace->id);

        $adminRole = Role::findOrCreate(WorkspaceRole::Admin->value, 'web');
        Role::findOrCreate(WorkspaceRole::Member->value, 'web');
        Role::findOrCreate(WorkspaceRole::Viewer->value, 'web');

        $user->assignRole($adminRole);

        return $workspace;
    }
}
