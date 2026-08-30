<?php

namespace Database\Seeders;

use App\Models\ApiConsumer;
use Illuminate\Database\Seeder;

class ApiConsumerSeeder extends Seeder
{
    public function run(): void
    {
        ApiConsumer::query()->updateOrCreate(
            ['name' => 'Postman'],
            [
                'description' => 'Llave para pruebas con Postman',
                'active' => true,
            ],
        );
    }
}
