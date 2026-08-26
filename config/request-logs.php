<?php

return [

    'apifycr' => [
        'path' => storage_path('logs/request/apifycr.log'),
        'level' => 'info',
        'max_files' => 30,
    ],

    'backups' => [
        'path' => storage_path('logs/request/backups.log'),
        'level' => 'debug',
        'max_files' => 30,
    ],

];
