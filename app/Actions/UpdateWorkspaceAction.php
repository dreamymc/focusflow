<?php

namespace App\Actions;

use App\Models\Workspace;

class UpdateWorkspaceAction
{
    public function execute(Workspace $workspace, string $name): Workspace
    {
        $workspace->update([
            'name' => $name,
        ]);

        return $workspace;
    }
}
