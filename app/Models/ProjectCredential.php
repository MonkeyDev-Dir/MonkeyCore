<?php

namespace App\Models;

use Database\Factories\ProjectCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'name', 'type', 'login_url', 'username', 'password', 'notes'])]
#[Hidden(['password'])]
class ProjectCredential extends Model
{
    /** @use HasFactory<ProjectCredentialFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['password' => 'encrypted'];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
