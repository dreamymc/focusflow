<?php

namespace App\Actions;

use App\Models\Workspace;

final class CancelSubscriptionAction
{
    public function execute(Workspace $workspace): void
    {
        $workspace->subscription('default')->cancel();
    }
}
