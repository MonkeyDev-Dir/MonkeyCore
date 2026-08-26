<?php

namespace Database\Seeders;

use App\Models\FileType;
use Illuminate\Database\Seeder;

class FileTypeSeeder extends Seeder
{
    public function run(): void
    {
        FileType::query()->updateOrCreate(
            ['key' => FileType::ClientLogo],
            ['name' => 'Logo de cliente'],
        );
    }
}
