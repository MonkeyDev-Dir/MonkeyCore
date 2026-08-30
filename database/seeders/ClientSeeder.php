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
