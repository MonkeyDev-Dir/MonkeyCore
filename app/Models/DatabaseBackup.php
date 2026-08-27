<?php

namespace App\Models;

use Database\Factories\DatabaseBackupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'project_id', 'backup_connection_id', 'execution_id', 'disk', 'path', 'filename', 'size', 'status', 'command', 'exit_code', 'attempts', 'duration_ms', 'checksum', 'started_at', 'completed_at', 'storage_verified_at', 'generated_at', 'error_message', 'error_output', 'metadata'])]
class DatabaseBackup extends Model
{
    /** @use HasFactory<DatabaseBackupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'exit_code' => 'integer',
            'attempts' => 'integer',
            'duration_ms' => 'integer',
            'storage_verified_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<BackupConnection, $this> */
    public function backupConnection(): BelongsTo
    {
        return $this->belongsTo(BackupConnection::class);
    }
}
