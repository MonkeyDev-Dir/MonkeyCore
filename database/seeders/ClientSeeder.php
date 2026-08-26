<?php

namespace Database\Seeders;

use App\Helpers\RandomHelper;
use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory()
            ->count(10)
            ->state(fn (): array => [
                'code' => RandomHelper::generateUniqueDigits(6, 'clients'),
            ])
            ->create();
    }
}
