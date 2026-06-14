<?php

use App\Mail\WeeklyDigestMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('queues the weekly digest mail when command is executed', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->artisan('digest:weekly')->assertSuccessful();

    Mail::assertQueued(WeeklyDigestMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});
