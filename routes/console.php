<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Mail\WeeklyDigestMail;
use Illuminate\Support\Facades\Mail;

Artisan::command('digest:weekly', function () {
    User::chunk(100, function ($users) {
        foreach ($users as $user) {
            Mail::to($user)->queue(new WeeklyDigestMail());
        }
    });
})->describe('Send weekly digest mail');

Schedule::command('digest:weekly')->weeklyOn(1, '08:00');
