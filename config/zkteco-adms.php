<?php

return [
    'enabled' => env('ZKTECO_ADMS_ENABLED', true),
    'route_prefix' => env('ZKTECO_ADMS_ROUTE_PREFIX', 'iclock'),
    'middleware' => [],

    'attendance_table' => env('ZKTECO_ADMS_ATTENDANCE_TABLE', 'inout_raw'),
    'dtr_table' => env('ZKTECO_ADMS_DTR_TABLE', 'dtr'),
    'operation_logs_table' => 'operation_logs',
    'attendance_photos_table' => 'attendance_photos',
    'device_polls_table' => 'adms_device_polls',
    'device_commands_table' => 'device_commands',
    'http_logs_table' => 'adms_http_logs',
    'userinfo_table' => 'adms_userinfo',
    'fingertmp_table' => 'adms_fingertmp',
    'device_state_table' => 'adms_device_state',

    'photo_disk' => env('ZKTECO_ADMS_PHOTO_DISK', 'local'),
    'photo_directory' => env('ZKTECO_ADMS_PHOTO_DIRECTORY', 'adms_photos'),

    'dtr_pairing' => [
        'enabled' => env('ZKTECO_ADMS_DTR_PAIRING_ENABLED', true),
    ],
];
