<?php

namespace Database\Seeders;

use App\Helpers\PhoneFormatHelper;
use App\Helpers\RandomHelper;
use App\Models\Client;
use App\Models\Domain;
use App\Models\FileType;
use App\Models\Project;
use App\Models\StoredFile;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::withTrashed()
            ->where('code', '512122')
            ->get()
            ->each(function (Client $client): void {
                $client->forceDelete();
            });

        $granitosYMarmoles = Client::query()->firstOrNew(['tax_id' => '3101796338']);

        if (! $granitosYMarmoles->exists) {
            $granitosYMarmoles->code = RandomHelper::generateUniqueDigits(6, 'clients');
        }

        $granitosYMarmoles->fill([
            'type' => 'company',
            'name' => 'Granitos y Mármoles CR',
            'legal_name' => 'Granitos y Mármoles CR',
            'tax_id' => '3101796338',
            'phone' => PhoneFormatHelper::normalize('8562 6443'),
            'website' => 'https://gymcr.co.cr/',
            'status' => 'active',
        ])->save();

        $this->seedClientLogo($granitosYMarmoles);

        Domain::query()->updateOrCreate(
            [
                'client_id' => $granitosYMarmoles->id,
                'name' => 'gymcr.co.cr',
            ],
            [
                'hosting_provider' => 'Dominios CR',
                'annual_cost' => 28.25,
                'currency' => 'USD',
                'expires_at' => '2027-07-12',
                'renewal_period_years' => 1,
            ],
        );

        $project = Project::query()->firstOrNew([
            'client_id' => $granitosYMarmoles->id,
            'name' => 'Sitio web',
        ]);

        if (! $project->exists || $project->code === null) {
            $project->code = RandomHelper::generateUniqueAlphanumeric(7, 'projects', 'PROJ');
        }

        $project->description = 'Sitio web desarrollado con Wordpress.';
        $project->save();

        $this->seedMonkeySolutions();
    }

    private function seedMonkeySolutions(): void
    {
        $client = Client::query()->firstOrNew(['tax_id' => '113420689']);

        if (! $client->exists) {
            $client->code = RandomHelper::generateUniqueDigits(6, 'clients');
        }

        $client->fill([
            'type' => 'company',
            'name' => 'MonkeySolutions',
            'legal_name' => 'MonkeySolutions',
            'tax_id' => '113420689',
            'email' => 'info@monkeysolutions.co',
            'website' => 'https://monkeysolutions.co',
            'status' => 'active',
        ])->save();

        Domain::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'name' => 'pruebayerror.com',
            ],
            [
                'hosting_provider' => 'DonDominio',
                'annual_cost' => 18.53,
                'currency' => 'USD',
                'expires_at' => '2027-01-14',
                'renewal_period_years' => 1,
            ],
        );

        Domain::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'name' => 'monkeysolutions.co',
            ],
            [
                'hosting_provider' => 'Namecheap',
                'annual_cost' => 45.48,
                'currency' => 'USD',
                'expires_at' => '2027-08-20',
                'renewal_period_years' => 1,
            ],
        );

        $this->createProject($client, 'Core');
        Project::query()
            ->where('client_id', $client->id)
            ->where('name', 'Página web')
            ->update(['name' => 'Sitio web']);
        $this->createProject($client, 'Sitio web');
    }

    private function createProject(Client $client, string $name): Project
    {
        $project = Project::query()->firstOrNew([
            'client_id' => $client->id,
            'name' => $name,
        ]);

        if (! $project->exists || $project->code === null) {
            $project->code = RandomHelper::generateUniqueAlphanumeric(7, 'projects', 'PROJ');
        }

        $project->save();

        return $project;
    }

    private function seedClientLogo(Client $client): void
    {
        $fileTypeId = FileType::query()->where('key', FileType::ClientLogo)->firstOrFail()->id;
        $identifier = '8ac01a80-99d8-469f-8ef2-74a60cf6f47a';
        $path = 'clients/client-logo-8ac01a80-99d8-469f-8ef2-74a60cf6f47a.webp';

        StoredFile::query()->updateOrCreate(
            [
                'client_id' => $client->id,
                'file_type_id' => $fileTypeId,
            ],
            [
                'identifier' => $identifier,
                'user_id' => null,
                'name' => 'client-logo-8ac01a80-99d8-469f-8ef2-74a60cf6f47a.webp',
                'url' => 'https://monkey-core-bucket.s3.us-east-2.amazonaws.com/clients/client-logo-8ac01a80-99d8-469f-8ef2-74a60cf6f47a.webp',
                'size_mb' => 0.00620842,
                'format' => 'webp',
                'width' => 256,
                'height' => 88,
                'bucket' => 'monkey-core-bucket',
                'disk' => 's3',
                'path' => $path,
                'mime_type' => 'image/webp',
            ],
        );

        $client->update(['image_path' => $path]);
    }
}
