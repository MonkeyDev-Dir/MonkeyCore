<?php

namespace App\Models;

use Database\Factories\WorkItemFollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['work_item_id', 'user_id', 'content', 'effective_hours'])]
class WorkItemFollowUp extends Model
{
    /** @use HasFactory<WorkItemFollowUpFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'effective_hours' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<WorkItem, $this> */
    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<StoredFile, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(StoredFile::class, 'work_item_follow_up_id');
    }
}
