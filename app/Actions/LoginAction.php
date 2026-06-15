<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class LoginAction
{
    public function execute(string $email, string $password, string $ip): User
    {
        $throttleKey = mb_strtolower($email).'|'.$ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw new ThrottleRequestsException('Too many login attempts. Please try again in ' . $seconds . ' seconds.');
        }

        $user = User::where('email', $email)->first();

        // Always check the password hash to prevent timing attacks / user enumeration
        $passwordMatches = Hash::check($password, $user ? $user->password : '$2y$12$uq.z/sF/X07vN/q3L.5tE.1oWc3yG/uT9pB5bZl2v1R3d4e5f6g7h');

        if (! $user || ! $passwordMatches) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        return $user;
    }
}
