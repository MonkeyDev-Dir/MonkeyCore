<?php

namespace App\Models;

use Database\Factories\BackupDatabaseTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'backup_command', 'is_active'])]
class BackupDatabaseType extends Model
{
    /** @use HasFactory<BackupDatabaseTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<BackupConnection, $this> */
    public function backupConnections(): HasMany
    {
        return $this->hasMany(BackupConnection::class, 'database_type', 'key');
    }
}
