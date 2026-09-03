<?php

namespace App\Models;

use App\Enums\WorkItemOrigin;
use App\Enums\WorkItemPriority;
use App\Enums\WorkItemStatus;
use Database\Factories\WorkItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['public_code', 'public_code_year', 'public_code_sequence', 'client_id', 'project_id', 'work_item_type_id', 'work_item_category_id', 'created_by', 'origin', 'priority', 'status', 'title', 'description', 'resolved_at', 'closed_at'])]
class WorkItem extends Model
{
    /** @use HasFactory<WorkItemFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'origin' => WorkItemOrigin::class,
            'priority' => WorkItemPriority::class,
            'status' => WorkItemStatus::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
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

    /** @return BelongsTo<WorkItemType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WorkItemType::class, 'work_item_type_id');
    }

    /** @return BelongsTo<WorkItemCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkItemCategory::class, 'work_item_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<User, $this> */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_item_user', 'work_item_id', 'user_id')->withTimestamps()->withPivot('assigned_at');
    }

    /** @return HasMany<WorkItemEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(WorkItemEvent::class);
    }
}
