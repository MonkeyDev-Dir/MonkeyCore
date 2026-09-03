<?php

namespace App\Services;

use App\Enums\WorkItemOrigin;
use App\Enums\WorkItemPriority;
use App\Enums\WorkItemStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemCategory;
use App\Models\WorkItemSequence;
use App\Models\WorkItemType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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

    /** @return Collection<int, User> */
    public function projectMembers(?int $projectId): Collection
    {
        if ($projectId === null) {
            return User::query()->whereKey(null)->get();
        }

        return User::query()
            ->whereHas('projects', fn ($query) => $query->whereKey($projectId))
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();
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
                $this->validateAssigneesBelongToProject($workItem, $assigneeIds);
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

    /** @param array<int, int> $assigneeIds */
    private function validateAssigneesBelongToProject(WorkItem $workItem, array $assigneeIds): void
    {
        if ($workItem->project_id === null) {
            return;
        }

        $memberCount = DB::table('project_user')
            ->where('project_id', $workItem->project_id)
            ->whereIn('user_id', $assigneeIds)
            ->count();

        if ($memberCount !== count(array_unique($assigneeIds))) {
            throw ValidationException::withMessages([
                'assignee_ids' => __('Todos los responsables deben pertenecer al proyecto seleccionado.'),
            ]);
        }
    }
}
