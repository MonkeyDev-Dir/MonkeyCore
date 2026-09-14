<?php

return [

    'civil_registry' => [
        'path' => storage_path('logs/integrations/civil-registry/civil-registry.log'),
        'level' => 'info',
        'max_files' => 30,
    ],

    'backups' => [
        'path' => storage_path('logs/request/backups.log'),
        'level' => 'debug',
        'max_files' => 30,
    ],

    'bccr' => [
        'path' => storage_path('logs/request/bccr.log'),
        'level' => 'debug',
        'max_files' => 30,
    ],

];
