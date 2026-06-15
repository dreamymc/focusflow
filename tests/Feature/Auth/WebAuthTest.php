<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders the login screen', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
});

it('renders the register screen', function () {
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
});

it('logs in a user via session successfully', function () {
    $user = User::factory()->create([
        'email' => 'web@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'web@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('fails web login with validation errors', function () {
    User::factory()->create([
        'email' => 'web@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'web@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

it('registers a user via session successfully', function () {
    $response = $this->post('/register', [
        'name' => 'Web User',
        'email' => 'webuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'Web User',
        'email' => 'webuser@example.com',
    ]);
});

it('fails web registration if passwords do not match', function () {
    $response = $this->post('/register', [
        'name' => 'Web User',
        'email' => 'webuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Different123!',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

it('logs out a web session successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});
