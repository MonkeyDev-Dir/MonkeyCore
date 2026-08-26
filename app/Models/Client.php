<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable(['type', 'name', 'legal_name', 'tax_id', 'email', 'phone', 'website', 'details', 'image_path', 'status', 'created_by'])]
/**
 * @property-read Collection<int, ClientContact> $contacts
 * @property-read Collection<int, ClientAddress> $addresses
 * @property string $type
 * @property string $code
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $tax_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $details
 * @property string|null $image_path
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['created_by' => 'integer'];
    }

    /** @return HasMany<ClientContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    /** @return HasMany<ClientAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(ClientAddress::class);
    }

    /** @return HasMany<StoredFile, $this> */
    public function storedFiles(): HasMany
    {
        return $this->hasMany(StoredFile::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<BackupConnection, $this> */
    public function backupConnections(): HasMany
    {
        return $this->hasMany(BackupConnection::class);
    }

    /** @return HasMany<DatabaseBackup, $this> */
    public function databaseBackups(): HasMany
    {
        return $this->hasMany(DatabaseBackup::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imageUrl(): ?string
    {
        $file = $this->storedFiles()
            ->whereHas('fileType', fn ($query) => $query->where('key', FileType::ClientLogo))
            ->latest()
            ->first();

        if ($file === null) {
            return $this->image_path === null ? null : asset("storage/{$this->image_path}");
        }

        return $file->url;
    }
}
