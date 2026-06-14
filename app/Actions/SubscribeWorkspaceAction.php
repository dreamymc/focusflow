<?php

namespace App\Actions;

use App\Models\Workspace;
use Laravel\Cashier\Subscription;

final class SubscribeWorkspaceAction
{
    public function execute(Workspace $workspace, string $stripePaymentMethodId, string $plan): Subscription
    {
        return $workspace
            ->newSubscription('default', config("plans.{$plan}.stripe_price_id", $plan))
            ->create($stripePaymentMethodId);
    }
}
