<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\CancelSubscriptionAction;
use App\Models\Workspace;
use Laravel\Cashier\Subscription;

it('cancels a subscription', function () {
    $workspace = Mockery::mock(Workspace::class);
    $subscription = Mockery::mock(Subscription::class);

    $workspace->shouldReceive('subscription')
        ->with('default')
        ->once()
        ->andReturn($subscription);
        
    $subscription->shouldReceive('cancel')
        ->once();

    $action = app(CancelSubscriptionAction::class);
    $action->execute($workspace);
});
