<?php

return [
    'enabled' => env('ZKTECO_ADMS_ENABLED', true),
    'route_prefix' => env('ZKTECO_ADMS_ROUTE_PREFIX', 'iclock'),
    'middleware' => [],
    'command_route_prefix' => env('ZKTECO_ADMS_COMMAND_ROUTE_PREFIX', 'zkteco-adms/commands'),
    'command_middleware' => [],
    'ui_route_prefix' => env('ZKTECO_ADMS_UI_ROUTE_PREFIX', 'shadow046/adms'),
    'ui_middleware' => ['web'],

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

    'python' => [
        'enabled' => env('ZKTECO_ADMS_PYTHON_ENABLED', false),
        'bin' => env('ZKTECO_ADMS_PYTHON_BIN', 'python3'),
        'timeout' => env('ZKTECO_ADMS_PYTHON_TIMEOUT', 180),
        'pyzk_root' => env('ZKTECO_ADMS_PYZK_ROOT', ''),
        'scripts_path' => env('ZKTECO_ADMS_PYTHON_SCRIPTS_PATH', ''),
        'backup_disk' => env('ZKTECO_ADMS_PYTHON_BACKUP_DISK', 'local'),
        'backup_directory' => env('ZKTECO_ADMS_PYTHON_BACKUP_DIRECTORY', 'zkteco_adms_backups'),
    ],
];
