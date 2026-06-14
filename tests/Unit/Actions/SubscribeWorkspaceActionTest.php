<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\SubscribeWorkspaceAction;
use App\Models\Workspace;
use Laravel\Cashier\SubscriptionBuilder;
use Mockery\MockInterface;

it('subscribes a workspace', function () {
    $workspace = Mockery::mock(Workspace::class);
    $builder = Mockery::mock(SubscriptionBuilder::class);
    
    $workspace->shouldReceive('newSubscription')
        ->with('default', 'price_abc')
        ->once()
        ->andReturn($builder);
        
    $builder->shouldReceive('create')
        ->with('pm_123')
        ->once()
        ->andReturn(Mockery::mock(\Laravel\Cashier\Subscription::class));

    config()->set('plans.pro.stripe_price_id', 'price_abc');

    $action = app(SubscribeWorkspaceAction::class);
    $action->execute($workspace, 'pm_123', 'pro');
});
