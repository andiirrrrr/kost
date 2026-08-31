<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate-monthly')
    ->monthlyOn(1, '00:05')
    ->withoutOverlapping(60)
    ->onOneServer();

Schedule::command('invoices:update-overdue')
    ->dailyAt('00:10')
    ->withoutOverlapping(30)
    ->onOneServer();

Schedule::command('whatsapp:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping(60)
    ->onOneServer();
