<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()->firstOrNew(['code' => '512122']);

        if (! $client->exists) {
            $client->fill([
                'type' => 'company',
                'name' => 'Cliente de prueba',
                'status' => 'active',
            ]);
            $client->code = '512122';
            $client->save();
        }
    }
}
