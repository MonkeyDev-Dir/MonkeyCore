<?php

namespace App\Models;

use Database\Factories\BackupConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'project_id', 'name', 'database_type', 'ssh_host', 'ssh_port', 'ssh_user', 'ssh_private_key', 'postgres_host', 'postgres_port', 'postgres_database', 'postgres_user', 'postgres_password', 'mysql_host', 'mysql_port', 'mysql_database', 'mysql_user', 'mysql_password', 'is_active', 'backup_frequency', 'backup_daily_retention_months', 'backup_monthly_retention_years', 'backup_last_run_at'])]
class BackupConnection extends Model
{
    /** @use HasFactory<BackupConnectionFactory> */
    use HasFactory;

    protected $attributes = ['database_type' => 'postgresql', 'ssh_port' => 22, 'postgres_host' => 'localhost', 'postgres_port' => 5432, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'ssh_port' => 'integer',
            'postgres_port' => 'integer',
            'ssh_private_key' => 'encrypted',
            'postgres_password' => 'encrypted',
            'mysql_port' => 'integer',
            'mysql_password' => 'encrypted',
            'is_active' => 'boolean',
            'backup_daily_retention_months' => 'integer',
            'backup_monthly_retention_years' => 'integer',
            'backup_last_run_at' => 'datetime',
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

    /** @return BelongsTo<BackupDatabaseType, $this> */
    public function databaseType(): BelongsTo
    {
        return $this->belongsTo(BackupDatabaseType::class, 'database_type', 'key');
    }
}
