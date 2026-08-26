<?php

return [
    'disk' => env('BACKUPS_DISK', 's3'),
    'path' => trim((string) env('BACKUPS_PATH', 'database-backups'), '/'),
    'pg_dump_binary' => env('BACKUPS_PG_DUMP_BINARY', 'pg_dump'),
    'retention' => [
        'months' => (int) env('BACKUPS_RETENTION_MONTHS', 12),
    ],
    'timezone' => env('BACKUPS_TIMEZONE', 'America/Guatemala'),
];
