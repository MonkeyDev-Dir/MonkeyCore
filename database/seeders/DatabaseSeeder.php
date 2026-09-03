<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FileTypeSeeder::class,
            BackupDatabaseTypeSeeder::class,
            ClientSeeder::class,
            EtaIBackupConfigurationSeeder::class,
            UserSeeder::class,
            ApiConsumerSeeder::class,
            WorkItemTypeSeeder::class,
        ]);
    }
}
