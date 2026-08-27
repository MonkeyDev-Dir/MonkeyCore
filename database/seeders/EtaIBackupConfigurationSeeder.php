<?php

namespace Database\Seeders;

use App\Helpers\RandomHelper;
use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Project;
use App\Models\StoredFile;
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
            'email' => 'info@casc.ed.cr',
            'phone' => '2475-6622',
            'website' => 'https://www.casc.ed.cr/casc/',
        ])->save();

        $this->seedClientLogo($client);

        $project = $this->createProject($client, 'Metai - Etai', 'Gestor estudiantil');

        BackupConnection::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'project_id' => $project->id,
                'name' => 'Metai Backup Config',
            ],
            [
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

        $cascProject = $this->createProject($client, 'CASC - Escuela/Colegio', 'Gestor estudiantil');

        BackupConnection::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'project_id' => $cascProject->id,
                'name' => 'CASC Backup Config',
            ],
            [
                'ssh_host' => '204.48.27.226',
                'ssh_port' => 22,
                'ssh_user' => 'forge',
                'postgres_host' => 'localhost',
                'postgres_port' => 5432,
                'postgres_database' => 'casc_iacsa_pg',
                'postgres_user' => 'forge',
                'postgres_password' => '79ouqiUx7R1LzOzQotWn',
                'is_active' => true,
            ],
        );

        $portalProject = $this->createProject($client, 'Portal estudiantil - Etai', 'Plataforma de consulta estudiantil');

        BackupConnection::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'project_id' => $portalProject->id,
                'name' => 'Portal Backup Config',
            ],
            [
                'ssh_host' => '204.48.27.226',
                'ssh_port' => 22,
                'ssh_user' => 'forge',
                'postgres_host' => 'localhost',
                'postgres_port' => 5432,
                'postgres_database' => 'etai_student_profile_iacsa_pg',
                'postgres_user' => 'forge',
                'postgres_password' => '79ouqiUx7R1LzOzQotWn',
                'is_active' => true,
            ],
        );
    }

    private function seedClientLogo(Client $client): void
    {
        $fileTypeId = FileType::query()->where('key', FileType::ClientLogo)->firstOrFail()->id;
        $identifier = 'b012479f-08b3-4144-b20a-1cf991447a54';
        $path = 'clients/client-logo-b012479f-08b3-4144-b20a-1cf991447a54.webp';

        StoredFile::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'file_type_id' => $fileTypeId,
            ],
            [
                'identifier' => $identifier,
                'user_id' => null,
                'name' => 'client-logo-b012479f-08b3-4144-b20a-1cf991447a54.webp',
                'url' => 'https://monkey-core-bucket.s3.us-east-2.amazonaws.com/clients/client-logo-b012479f-08b3-4144-b20a-1cf991447a54.webp',
                'size_mb' => 0.01838493,
                'format' => 'webp',
                'width' => 217,
                'height' => 256,
                'bucket' => 'monkey-core-bucket',
                'disk' => 's3',
                'path' => $path,
                'mime_type' => 'image/webp',
            ],
        );

        $client->update(['image_path' => $path]);
    }

    private function createProject(Client $client, string $name, string $description): Project
    {
        $project = Project::query()->firstOrNew([
            'client_id' => $client->id,
            'name' => $name,
        ]);

        if (! $project->exists || $project->code === null) {
            $project->code = RandomHelper::generateUniqueAlphanumeric(7, 'projects', 'PROJ');
        }

        $project->description = $description;
        $project->save();

        return $project;
    }
}
