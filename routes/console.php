<?php

use App\Jobs\PollChannelStatusJob;
use App\Jobs\PollDeviceHealthJob;
use App\Jobs\PollStorageStatusJob;
use App\Models\Device;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Device::where('status', '!=', 'disabled')->each(function (Device $device) {
        PollDeviceHealthJob::dispatch($device);
    });
})->everyTwoMinutes()->name('poll-device-health')->withoutOverlapping();

Schedule::call(function () {
    Device::where('status', '!=', 'disabled')->each(function (Device $device) {
        PollChannelStatusJob::dispatch($device);
    });
})->everyMinute()->name('poll-channel-status')->withoutOverlapping();

Schedule::call(function () {
    Device::where('status', '!=', 'disabled')->each(function (Device $device) {
        PollStorageStatusJob::dispatch($device);
    });
})->everyFiveMinutes()->name('poll-storage-status')->withoutOverlapping();

Schedule::command('monitor:prune-logs --days=30')->daily()->name('prune-health-logs');
