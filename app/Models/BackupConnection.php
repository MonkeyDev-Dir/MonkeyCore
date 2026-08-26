<?php

namespace App\Models;

use Database\Factories\BackupConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'project_id', 'name', 'ssh_host', 'ssh_port', 'ssh_user', 'ssh_private_key', 'postgres_host', 'postgres_port', 'postgres_database', 'postgres_user', 'postgres_password', 'is_active'])]
class BackupConnection extends Model
{
    /** @use HasFactory<BackupConnectionFactory> */
    use HasFactory;

    protected $attributes = ['ssh_port' => 22, 'postgres_host' => 'localhost', 'postgres_port' => 5432, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'ssh_port' => 'integer',
            'postgres_port' => 'integer',
            'ssh_private_key' => 'encrypted',
            'postgres_password' => 'encrypted',
            'is_active' => 'boolean',
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
}
