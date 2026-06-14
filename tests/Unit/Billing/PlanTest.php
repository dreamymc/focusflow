<?php

use App\Models\Plan;

it('has free and pro tiers with feature flags', function () {
    $freePlan = Plan::where('slug', 'free')->firstOrFail();
    $freePlan->update(['features' => ['max_members' => 3]]);

    $proPlan = Plan::where('slug', 'pro')->firstOrFail();
    $proPlan->update(['features' => ['max_members' => -1]]);

    expect($freePlan->features['max_members'])->toBe(3)
        ->and($proPlan->features['max_members'])->toBe(-1);
});
