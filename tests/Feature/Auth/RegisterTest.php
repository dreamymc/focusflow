<?php

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

it('registers a user successfully', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('data.user')
                 ->where('data.user.name', 'John Doe')
                 ->where('data.user.email', 'john@example.com')
                 ->has('data.token')
        );

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

it('requires name, email, and password to register', function () {
    $response = $this->postJson('/api/v1/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('requires a valid email address to register', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'not-an-email',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('requires password to be at least 8 characters', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('cannot register with an already existing email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
