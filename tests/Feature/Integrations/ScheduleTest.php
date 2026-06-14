<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules the weekly digest every Monday', function () {
    $schedule = app(Schedule::class);

    $events = collect($schedule->events())->filter(function ($event) {
        return str_contains($event->command, 'digest:weekly');
    });

    expect($events)->not->toBeEmpty('digest:weekly command is not scheduled.');

    $event = $events->first();
    expect($event->expression)->toMatch('/1$/'); // Ends with 1 (Monday)
});
