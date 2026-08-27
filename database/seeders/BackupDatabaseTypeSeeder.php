<?php

namespace Database\Seeders;

use App\Models\BackupDatabaseType;
use Illuminate\Database\Seeder;

class BackupDatabaseTypeSeeder extends Seeder
{
    public function run(): void
    {
        BackupDatabaseType::query()->updateOrCreate(
            ['key' => 'postgresql'],
            [
                'name' => 'PostgreSQL',
                'backup_command' => 'pg_dump',
                'is_active' => true,
            ],
        );

        BackupDatabaseType::query()->updateOrCreate(
            ['key' => 'mysql'],
            [
                'name' => 'MySQL',
                'backup_command' => 'mysqldump',
                'is_active' => true,
            ],
        );
    }
}
