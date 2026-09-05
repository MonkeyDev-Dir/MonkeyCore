<?php

namespace App\Services;

use App\Enums\WorkItemOrigin;
use App\Enums\WorkItemPriority;
use App\Enums\WorkItemStatus;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Project;
use App\Models\StoredFile;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemCategory;
use App\Models\WorkItemFollowUp;
use App\Models\WorkItemSequence;
use App\Models\WorkItemType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkItemService
{
    /** @return Collection<int, WorkItemType> */
    public function types(): Collection
    {
        return WorkItemType::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return Collection<int, WorkItemCategory> */
    public function categoriesForType(?int $typeId): Collection
    {
        if ($typeId === null) {
            return WorkItemCategory::query()->whereKey(null)->get();
        }

        return WorkItemCategory::query()
            ->where('work_item_type_id', $typeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, User> */
    public function users(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();
    }

    /** @return Collection<int, Client> */
    public function clients(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    /** @return Collection<int, Project> */
    public function projectsForClient(?int $clientId): Collection
    {
        if ($clientId === null) {
            return Project::query()->whereKey(null)->get();
        }

        return Project::query()->where('client_id', $clientId)->orderBy('name')->get();
    }

    public function findByPublicCodeOrFail(string $publicCode): WorkItem
    {
        return WorkItem::query()
            ->with(['type', 'category', 'client', 'project', 'creator', 'assignees', 'events.actor'])
            ->where('public_code', $publicCode)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $assigneeIds
     */
    public function update(WorkItem $workItem, array $attributes, User $actor, array $assigneeIds): WorkItem
    {
        return DB::transaction(function () use ($workItem, $attributes, $actor, $assigneeIds): WorkItem {
            $this->validateTypeContext($attributes);
            $this->validateProjectBelongsToClient($attributes);
            $this->validateCategoryBelongsToType($attributes);

            $workItem->loadMissing('assignees');
            $original = collect(['title', 'description', 'client_id', 'project_id', 'work_item_type_id', 'work_item_category_id', 'priority', 'status'])
                ->mapWithKeys(fn (string $field): array => [$field => $workItem->getRawOriginal($field)])
                ->all();
            $normalizedAssigneeIds = collect($assigneeIds)->map(fn (int|string $id): int => (int) $id)->sort()->values()->all();
            $originalAssigneeIds = $workItem->assignees->modelKeys();
            sort($originalAssigneeIds);

            $workItem->fill($attributes);
            $workItem->save();

            $changedInformation = [];
            foreach (['title', 'client_id', 'project_id', 'work_item_type_id', 'work_item_category_id', 'priority', 'status'] as $field) {
                if ($workItem->getAttributes()[$field] != $original[$field]) {
                    $changedInformation[$field] = ['from' => $original[$field], 'to' => $workItem->getAttributes()[$field]];
                }
            }

            if ($changedInformation !== []) {
                $this->recordEvent($workItem, $actor, 'information_updated', $changedInformation);
            }

            if ($workItem->description != $original['description']) {
                $this->recordEvent($workItem, $actor, 'description_updated', [
                    'description' => ['from' => $original['description'], 'to' => $workItem->description],
                ]);
            }

            if ($normalizedAssigneeIds !== $originalAssigneeIds) {
                $workItem->assignees()->sync(array_fill_keys($normalizedAssigneeIds, ['assigned_at' => now()]));
                $this->recordEvent($workItem, $actor, 'assignees_updated', [
                    'user_ids' => ['from' => $originalAssigneeIds, 'to' => $normalizedAssigneeIds],
                ]);
            }

            return $workItem->fresh(['type', 'category', 'client', 'project', 'creator', 'assignees', 'events.actor']);
        });
    }

    /** @param array<string, mixed> $changes */
    private function recordEvent(WorkItem $workItem, User $actor, string $eventType, array $changes): void
    {
        $workItem->events()->create([
            'actor_id' => $actor->id,
            'event_type' => $eventType,
            'previous_values' => collect($changes)->mapWithKeys(fn (array $change, string $field): array => [$field => $change['from']])->all(),
            'new_values' => collect($changes)->mapWithKeys(fn (array $change, string $field): array => [$field => $change['to']])->all(),
            'metadata' => ['changed_fields' => array_keys($changes)],
            'occurred_at' => now(),
        ]);
    }

    public function createFollowUp(WorkItem $workItem, string $content, User $user, ?float $effectiveHours = null, array $attachmentIds = []): WorkItemFollowUp
    {
        return DB::transaction(function () use ($workItem, $content, $user, $effectiveHours, $attachmentIds): WorkItemFollowUp {
            $followUp = $workItem->followUps()->create([
                'user_id' => $user->id,
                'content' => $content,
                'effective_hours' => $effectiveHours,
            ]);

            if ($attachmentIds !== []) {
                StoredFile::query()
                    ->whereIn('id', $attachmentIds)
                    ->where('work_item_id', $workItem->getKey())
                    ->whereNull('work_item_follow_up_id')
                    ->update(['work_item_follow_up_id' => $followUp->id]);
            }

            $this->recordEvent($workItem, $user, 'follow_up_created', [
                'follow_up_id' => ['from' => null, 'to' => $followUp->id],
                'content' => ['from' => null, 'to' => $content],
                'effective_hours' => ['from' => null, 'to' => $effectiveHours],
            ]);

            return $followUp->load(['user', 'attachments']);
        });
    }

    public function findFollowUpOrFail(WorkItem $workItem, int $followUpId): WorkItemFollowUp
    {
        return $workItem->followUps()->with(['user', 'attachments'])->findOrFail($followUpId);
    }

    public function updateFollowUp(WorkItemFollowUp $followUp, string $content, User $user, ?float $effectiveHours = null, array $attachmentIds = []): WorkItemFollowUp
    {
        return DB::transaction(function () use ($followUp, $content, $user, $effectiveHours, $attachmentIds): WorkItemFollowUp {
            $followUp->loadMissing('workItem');
            $originalContent = $followUp->content;
            $originalEffectiveHours = $followUp->effective_hours;
            $changes = [];

            if ($originalContent !== $content) {
                $changes['content'] = ['from' => $originalContent, 'to' => $content];
            }

            if ((float) ($originalEffectiveHours ?? 0) !== (float) ($effectiveHours ?? 0) || ($originalEffectiveHours === null) !== ($effectiveHours === null)) {
                $changes['effective_hours'] = ['from' => $originalEffectiveHours, 'to' => $effectiveHours];
            }

            $followUp->fill([
                'content' => $content,
                'effective_hours' => $effectiveHours,
            ]);
            $followUp->save();

            if ($attachmentIds !== []) {
                StoredFile::query()
                    ->whereIn('id', $attachmentIds)
                    ->where('work_item_id', $followUp->workItem->getKey())
                    ->whereNull('work_item_follow_up_id')
                    ->update(['work_item_follow_up_id' => $followUp->id]);
                $changes['attachments'] = ['from' => null, 'to' => $attachmentIds];
            }

            if ($changes !== []) {
                $this->recordEvent($followUp->workItem, $user, 'follow_up_updated', $changes);
            }

            return $followUp->fresh(['user', 'attachments']);
        });
    }

    public function storeFollowUpImage(WorkItem $workItem, UploadedFile $image, User $user): StoredFile
    {
        $fileTypeId = FileType::query()->where('key', FileType::WorkItemFollowUpImage)->firstOrFail()->id;
        $identifier = (string) Str::uuid();
        $extension = strtolower($image->getClientOriginalExtension() ?: 'bin');
        $name = "work-item-follow-up-{$identifier}.{$extension}";
        $path = "work-items/{$workItem->getKey()}/follow-ups/{$name}";
        $disk = Storage::disk('s3');
        $contents = file_get_contents($image->getRealPath());

        if ($contents === false || ! $disk->put($path, $contents, ['ContentType' => $image->getMimeType()])) {
            throw new \RuntimeException('No fue posible subir la imagen del seguimiento a Amazon S3.');
        }

        if (! $disk->exists($path)) {
            $disk->delete($path);

            throw new \RuntimeException('Amazon S3 no confirmó la disponibilidad de la imagen.');
        }

        $dimensions = @getimagesize($image->getRealPath());

        return StoredFile::query()->create([
            'identifier' => $identifier,
            'client_id' => $workItem->client_id,
            'user_id' => $user->id,
            'work_item_id' => $workItem->getKey(),
            'file_type_id' => $fileTypeId,
            'name' => $name,
            'url' => $disk->url($path),
            'size_mb' => round(strlen($contents) / 1024 / 1024, 8),
            'format' => $extension,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'bucket' => (string) config('filesystems.disks.s3.bucket'),
            'disk' => 's3',
            'path' => $path,
            'mime_type' => (string) $image->getMimeType(),
        ]);
    }

    /** @return Collection<int, WorkItemFollowUp> */
    public function followUps(WorkItem $workItem): Collection
    {
        return $workItem->followUps()->with(['user', 'attachments'])->latest()->get();
    }

    /** @param array{column?: string, direction?: string} $sort */
    public function paginate(string $search = '', array $sort = [], int $perPage = 10): LengthAwarePaginator
    {
        $sortColumns = [
            'public_code' => 'work_items.public_code',
            'case' => 'work_items.title',
            'client_name' => 'clients.name',
            'type_name' => 'work_item_types.name',
            'status' => 'work_items.status',
            'created_at' => 'work_items.created_at',
        ];
        $sortColumn = $sortColumns[$sort['column'] ?? 'created_at'] ?? $sortColumns['created_at'];
        $sortDirection = ($sort['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return WorkItem::query()
            ->with(['type', 'category', 'client', 'project', 'creator'])
            ->leftJoin('clients', 'clients.id', '=', 'work_items.client_id')
            ->leftJoin('work_item_types', 'work_item_types.id', '=', 'work_items.work_item_type_id')
            ->select('work_items.*')
            ->when(trim($search) !== '', function ($query) use ($search): void {
                $term = '%'.Str::lower(Str::ascii(trim($search))).'%';

                $query->where(function ($query) use ($term): void {
                    $query->whereRaw($this->accentInsensitiveColumn('work_items.public_code').' LIKE ?', [$term])
                        ->orWhereRaw($this->accentInsensitiveColumn('work_items.title').' LIKE ?', [$term])
                        ->orWhereHas('client', function ($query) use ($term): void {
                            $query->whereRaw($this->accentInsensitiveColumn('clients.name').' LIKE ?', [$term]);
                        });
                });
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage);
    }

    private function accentInsensitiveColumn(string $column): string
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return "unaccent(lower({$column}))";
        }

        $expression = $column;

        foreach (['Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'] as $accented => $plain) {
            $expression = "REPLACE({$expression}, '{$accented}', '{$plain}')";
        }

        return 'LOWER('.$expression.')';
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $assigneeIds
     */
    public function create(array $attributes, User $creator, array $assigneeIds = []): WorkItem
    {
        return DB::transaction(function () use ($attributes, $creator, $assigneeIds): WorkItem {
            $this->validateTypeContext($attributes);
            $this->validateProjectBelongsToClient($attributes);
            $this->validateCategoryBelongsToType($attributes);

            $year = (int) now()->format('Y');
            $sequence = $this->nextSequence($year);
            $workItem = WorkItem::query()->create([
                ...$attributes,
                'public_code' => sprintf('MKY-%s%06d', now()->format('y'), $sequence),
                'public_code_year' => $year,
                'public_code_sequence' => $sequence,
                'created_by' => $creator->id,
                'origin' => $attributes['origin'] ?? WorkItemOrigin::Internal,
                'priority' => $attributes['priority'] ?? WorkItemPriority::Medium,
                'status' => WorkItemStatus::New,
            ]);

            if ($assigneeIds !== []) {

                $workItem->assignees()->attach(array_fill_keys($assigneeIds, ['assigned_at' => now()]));
            }

            $workItem->events()->create([
                'actor_id' => $creator->id,
                'event_type' => 'created',
                'new_values' => $workItem->only(['public_code', 'status', 'priority', 'origin']),
                'occurred_at' => now(),
            ]);

            if ($assigneeIds !== []) {
                $workItem->events()->create([
                    'actor_id' => $creator->id,
                    'event_type' => 'assigned',
                    'new_values' => ['user_ids' => array_values($assigneeIds)],
                    'occurred_at' => now(),
                ]);
            }

            return $workItem->load(['type', 'category', 'project', 'assignees', 'events']);
        });
    }

    private function nextSequence(int $year): int
    {
        WorkItemSequence::query()->insertOrIgnore(['year' => $year, 'next_sequence' => 1]);
        $sequence = WorkItemSequence::query()->whereKey($year)->lockForUpdate()->firstOrFail();
        $currentSequence = $sequence->next_sequence;
        $sequence->increment('next_sequence');

        return $currentSequence;
    }

    /** @param array<string, mixed> $attributes */
    private function validateTypeContext(array $attributes): void
    {
        $type = WorkItemType::query()->whereKey($attributes['work_item_type_id'] ?? null)->first();

        if ($type?->slug === 'support' && (($attributes['client_id'] ?? null) === null || ($attributes['project_id'] ?? null) === null)) {
            throw ValidationException::withMessages([
                'client_id' => __('Los casos de soporte requieren un cliente y un proyecto.'),
            ]);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function validateProjectBelongsToClient(array $attributes): void
    {
        $clientId = $attributes['client_id'] ?? null;
        $projectId = $attributes['project_id'] ?? null;

        if ($projectId === null) {
            return;
        }

        $project = Project::query()->whereKey($projectId)->first();

        if ($project === null || ($clientId !== null && $project->client_id !== (int) $clientId)) {
            throw ValidationException::withMessages([
                'project_id' => __('El proyecto seleccionado no pertenece al cliente indicado.'),
            ]);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function validateCategoryBelongsToType(array $attributes): void
    {
        $categoryId = $attributes['work_item_category_id'] ?? null;
        $typeId = $attributes['work_item_type_id'] ?? null;

        if ($categoryId === null || $typeId === null) {
            return;
        }

        if (! WorkItemCategory::query()->whereKey($categoryId)->where('work_item_type_id', $typeId)->exists()) {
            throw ValidationException::withMessages([
                'work_item_category_id' => __('La categoría seleccionada no pertenece al tipo indicado.'),
            ]);
        }
    }
}
