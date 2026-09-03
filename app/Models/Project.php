<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['client_id', 'name', 'code', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public function descriptionPreview(): string
    {
        $description = trim(strip_tags((string) $this->description));

        return Str::length($description) > 120
            ? Str::substr($description, 0, 120).'...'
            : $description;
    }

    public function hasLongDescription(): bool
    {
        return Str::length(trim(strip_tags((string) $this->description))) > 120;
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
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

    /** @return HasMany<ProjectCredential, $this> */
    public function credentials(): HasMany
    {
        return $this->hasMany(ProjectCredential::class);
    }
}
