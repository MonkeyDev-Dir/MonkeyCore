<?php

namespace App\Services;

use App\Helpers\RandomHelper;
use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Project;
use App\Models\StoredFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageManagerInterface;
use RuntimeException;
use Throwable;

class ClientService
{
    public function __construct(private ImageManagerInterface $imageManager) {}

    /** @return Collection<int, Client> */
    public function all(): Collection
    {
        return Client::query()
            ->with(['contacts' => fn ($query) => $query->where('is_primary', true), 'addresses' => fn ($query) => $query->where('is_primary', true)])
            ->latest()
            ->get();
    }

    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Client::query()
            ->with(['contacts' => fn ($query) => $query->where('is_primary', true), 'addresses' => fn ($query) => $query->where('is_primary', true)])
            ->when(trim($search) !== '', function ($query) use ($search): void {
                $term = '%'.Str::lower(Str::ascii(trim($search))).'%';

                $query->where(function ($query) use ($term): void {
                    foreach (['name', 'code', 'email'] as $column) {
                        $method = $column === 'name' ? 'whereRaw' : 'orWhereRaw';
                        $query->{$method}($this->accentInsensitiveColumn($column).' LIKE ?', [$term]);
                    }
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    private function accentInsensitiveColumn(string $column): string
    {
        $qualifiedColumn = (new Client)->qualifyColumn($column);

        if (DB::connection()->getDriverName() === 'pgsql') {
            return "unaccent(lower({$qualifiedColumn}))";
        }

        $expression = $qualifiedColumn;

        foreach (['Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'] as $accented => $plain) {
            $expression = "REPLACE({$expression}, '{$accented}', '{$plain}')";
        }

        return 'LOWER('.$expression.')';
    }

    public function findOrFail(int $clientId): Client
    {
        return Client::query()->with(['contacts', 'addresses'])->findOrFail($clientId);
    }

    public function findByCodeOrFail(string $clientCode): Client
    {
        return Client::query()->with(['contacts', 'addresses', 'projects', 'backupConnections.project'])->where('code', $clientCode)->firstOrFail();
    }

    /** @param array<string, mixed> $attributes */
    public function saveProject(Client $client, array $attributes, ?Project $project = null): Project
    {
        $project ??= new Project(['client_id' => $client->id]);

        if (! $project->exists && $project->code === null) {
            $project->code = RandomHelper::generateUniqueAlphanumeric(7, 'projects', 'PROJ');
        }

        $project->fill($attributes);
        $project->save();

        return $project->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public function saveBackupConnection(Client $client, array $attributes, ?BackupConnection $connection = null): BackupConnection
    {
        if ($connection !== null) {
            $connection->update($attributes);

            return $connection->refresh();
        }

        return $client->backupConnections()->create($attributes);
    }

    public function findBackupConnectionOrFail(Client $client, int $connectionId): BackupConnection
    {
        return $client->backupConnections()->findOrFail($connectionId);
    }

    public function updateLogo(Client $client, UploadedFile $image): Client
    {
        return $this->save([], $client, $image);
    }

    /** @param array<string, mixed> $attributes */
    public function save(array $attributes, ?Client $client = null, ?UploadedFile $image = null): Client
    {
        $uploadedImage = $image === null ? null : $this->uploadImage($image);
        $oldImagePath = $client?->image_path;
        $oldStoredFile = $client?->storedFiles()
            ->whereHas('fileType', fn ($query) => $query->where('key', FileType::ClientLogo))
            ->latest()
            ->first();

        try {
            $savedClient = DB::transaction(function () use ($attributes, $client, $uploadedImage, $oldStoredFile): Client {
                $client ??= new Client;

                if (! $client->exists && $client->code === null) {
                    $client->code = RandomHelper::generateUniqueDigits(6, 'clients');
                }

                if ($client->exists) {
                    unset($attributes['created_by']);
                }

                $client->fill($attributes);
                $client->save();

                if ($uploadedImage !== null) {
                    StoredFile::query()->create([
                        ...$uploadedImage,
                        'client_id' => $client->id,
                        'user_id' => null,
                    ]);
                    $client->image_path = $uploadedImage['path'];
                    $client->save();
                }

                $this->savePrimaryContact($client, $attributes['contact'] ?? []);
                $this->savePrimaryAddress($client, $attributes['address'] ?? []);

                if ($oldStoredFile !== null && $uploadedImage !== null) {
                    $oldStoredFile->delete();
                }

                return $client->refresh()->load(['contacts', 'addresses']);
            });
        } catch (Throwable $exception) {
            if ($uploadedImage !== null) {
                try {
                    Storage::disk('s3')->delete($uploadedImage['path']);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        if ($oldImagePath !== null && $uploadedImage !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        if ($oldStoredFile !== null && $uploadedImage !== null) {
            Storage::disk($oldStoredFile->disk)->delete($oldStoredFile->path);
        }

        return $savedClient;
    }

    /**
     * @return array{
     *     identifier: string,
     *     file_type_id: int,
     *     name: string,
     *     url: string,
     *     size_mb: float,
     *     format: string,
     *     width: int,
     *     height: int,
     *     bucket: string,
     *     disk: string,
     *     path: string,
     *     mime_type: string,
     * }
     */
    private function uploadImage(UploadedFile $image): array
    {
        $fileTypeId = FileType::query()->where('key', FileType::ClientLogo)->firstOrFail()->id;
        $identifier = (string) Str::uuid();
        $name = "client-logo-{$identifier}.webp";
        $path = "clients/{$name}";
        $resizedImage = $this->imageManager
            ->decodePath($image->getRealPath())
            ->scaleDown(256, 256);
        $width = $resizedImage->width();
        $height = $resizedImage->height();
        $encodedImage = $resizedImage->encodeUsingFormat(Format::WEBP, quality: 85);
        $disk = Storage::disk('s3');
        $contents = (string) $encodedImage;
        $url = $disk->url($path);

        if (! $disk->put($path, $contents, ['ContentType' => 'image/webp'])) {
            throw new RuntimeException('No fue posible subir la imagen a Amazon S3.');
        }

        if (! $disk->exists($path)) {
            $disk->delete($path);

            throw new RuntimeException('Amazon S3 no confirmó la disponibilidad de la imagen.');
        }

        return [
            'identifier' => $identifier,
            'file_type_id' => $fileTypeId,
            'name' => $name,
            'url' => $url,
            'size_mb' => round(strlen($contents) / 1024 / 1024, 8),
            'format' => 'webp',
            'width' => $width,
            'height' => $height,
            'bucket' => (string) config('filesystems.disks.s3.bucket'),
            'disk' => 's3',
            'path' => $path,
            'mime_type' => 'image/webp',
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function savePrimaryContact(Client $client, array $attributes): void
    {
        if (($attributes['name'] ?? '') === '') {
            return;
        }

        $client->contacts()->updateOrCreate(['is_primary' => true], [
            'name' => $attributes['name'],
            'position' => $attributes['position'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'mobile_phone' => $attributes['mobile_phone'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'is_primary' => true,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function savePrimaryAddress(Client $client, array $attributes): void
    {
        if (($attributes['address_line'] ?? '') === '') {
            return;
        }

        $client->addresses()->updateOrCreate(['is_primary' => true], [
            'type' => 'main',
            'address_line' => $attributes['address_line'],
            'city' => $attributes['city'] ?? null,
            'state' => $attributes['state'] ?? null,
            'country' => $attributes['country'] ?? null,
            'postal_code' => $attributes['postal_code'] ?? null,
            'is_primary' => true,
        ]);
    }
}
