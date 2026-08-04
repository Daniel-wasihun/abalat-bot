<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule role processing to run daily at midnight
Schedule::command('roles:process-scheduled')->daily()->at('00:00');
Schedule::command('permissions:process-scheduled')->daily()->at('00:00');

// Run every minute for near real-time activation/expiration
Schedule::command('roles:process-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('permissions:process-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

// Auto-reject borrow requests that exceed the 2-minute approval window
Schedule::command('borrows:expire-requests')
    ->everyMinute()
    ->withoutOverlapping();
