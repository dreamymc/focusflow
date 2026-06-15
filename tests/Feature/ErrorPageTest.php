<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders custom error page for 404 when debug is disabled', function () {
    Config::set('app.debug', false);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/non-existent-route-random-xyz');

    $response->assertStatus(404);
    $response->assertInertia(fn ($page) => $page
        ->component('Error')
        ->where('status', 404)
    );
});
