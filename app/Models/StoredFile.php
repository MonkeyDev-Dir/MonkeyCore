<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['identifier', 'client_id', 'user_id', 'work_item_id', 'work_item_follow_up_id', 'file_type_id', 'name', 'url', 'size_mb', 'format', 'width', 'height', 'bucket', 'disk', 'path', 'mime_type'])]
class StoredFile extends Model
{
    use SoftDeletes;

    protected $table = 'core_files';

    protected function casts(): array
    {
        return [
            'size_mb' => 'decimal:8',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WorkItem, $this> */
    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    /** @return BelongsTo<WorkItemFollowUp, $this> */
    public function workItemFollowUp(): BelongsTo
    {
        return $this->belongsTo(WorkItemFollowUp::class);
    }

    /** @return BelongsTo<FileType, $this> */
    public function fileType(): BelongsTo
    {
        return $this->belongsTo(FileType::class);
    }
}
