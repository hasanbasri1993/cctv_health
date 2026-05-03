<?php

return [
    'polling' => [
        'channel_interval' => env('POLLING_CHANNEL_INTERVAL', 1),
        'storage_interval' => env('POLLING_STORAGE_INTERVAL', 5),
        'device_interval' => env('POLLING_DEVICE_INTERVAL', 2),
    ],
    'notification_reminder_interval' => env('NOTIFICATION_REMINDER_INTERVAL', 60),
    'email_recipients' => array_filter(explode(',', env('ALERT_EMAIL_RECIPIENTS', ''))),
];
