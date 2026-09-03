<?php

namespace App\Models;

use Database\Factories\WorkItemCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['work_item_type_id', 'name', 'slug', 'is_active'])]
class WorkItemCategory extends Model
{
    /** @use HasFactory<WorkItemCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<WorkItemType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WorkItemType::class, 'work_item_type_id');
    }

    /** @return HasMany<WorkItem, $this> */
    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }
}
