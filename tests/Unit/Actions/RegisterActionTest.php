<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\RegisterAction;
use Illuminate\Support\Facades\Hash;

it('registers a user', function () {
    $action = app(RegisterAction::class);
    $user = $action->execute([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);
    
    expect($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com');
        
    $this->assertTrue(Hash::check('password123', $user->password));
});
