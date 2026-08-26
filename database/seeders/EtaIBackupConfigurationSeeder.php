<?php

namespace Database\Seeders;

use App\Helpers\RandomHelper;
use App\Models\BackupConnection;
use App\Models\Client;
use Illuminate\Database\Seeder;

class EtaIBackupConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()->firstOrNew(['tax_id' => '3101007178']);

        if (! $client->exists) {
            $client->code = RandomHelper::generateUniqueDigits(6, 'clients');
        }

        $client->fill([
            'type' => 'company',
            'name' => 'Instituto Agropecuario Costarricense S.A.',
            'legal_name' => 'Instituto Agropecuario Costarricense S.A.',
            'status' => 'active',
        ])->save();

        BackupConnection::query()->updateOrCreate(
            ['client_id' => $client->id, 'name' => 'Metai'],
            [
                'project_id' => null,
                'ssh_host' => '204.48.27.226',
                'ssh_port' => 22,
                'ssh_user' => 'forge',
                'postgres_host' => 'localhost',
                'postgres_port' => 5432,
                'postgres_database' => 'etai_iacsa_pg',
                'postgres_user' => 'forge',
                'postgres_password' => '79ouqiUx7R1LzOzQotWn',
                'is_active' => true,
            ],
        );
    }
}
