<?php

namespace App\Livewire\WorkItems;

use App\Models\WorkItem;
use App\Models\WorkItemFollowUp;
use App\Services\WorkItemService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class WorkItemFollowUpComposer extends Component
{
    use WithFileUploads;

    public WorkItem $workItem;

    public ?int $editingFollowUpId = null;

    public string $content = '';

    public ?float $effectiveHours = null;

    public ?TemporaryUploadedFile $followUpImage = null;

    /** @var array<int, int> */
    public array $pendingAttachmentIds = [];

    public function mount(WorkItem $workItem): void
    {
        $this->workItem = $workItem;
    }

    public function openCreate(): void
    {
        $this->resetEditor();
    }

    public function openEdit(int $followUpId, WorkItemService $workItemService): void
    {
        $followUp = $workItemService->findFollowUpOrFail($this->workItem, $followUpId);
        $this->editingFollowUpId = $followUp->id;
        $this->content = $followUp->content;
        $this->effectiveHours = $followUp->effective_hours === null ? null : (float) $followUp->effective_hours;
        $this->pendingAttachmentIds = [];
        $this->resetValidation();
    }

    public function close(): void
    {
        $this->resetEditor();
    }

    public function storeFollowUpImage(WorkItemService $workItemService): string
    {
        if ($this->followUpImage === null) {
            return '';
        }

        $storedFile = $workItemService->storeFollowUpImage($this->workItem, $this->followUpImage, auth()->user());
        $this->pendingAttachmentIds[] = $storedFile->id;
        $this->reset('followUpImage');

        return $storedFile->url;
    }

    public function save(WorkItemService $workItemService): void
    {
        $validated = $this->validate([
            'content' => ['required', 'string', 'max:50000'],
            'effectiveHours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'pendingAttachmentIds' => ['array'],
            'pendingAttachmentIds.*' => ['integer', 'distinct', 'exists:core_files,id'],
        ]);

        if ($this->editingFollowUpId === null) {
            $workItemService->createFollowUp(
                $this->workItem,
                $validated['content'],
                auth()->user(),
                $validated['effectiveHours'] ?? null,
                $validated['pendingAttachmentIds'],
            );
        } else {
            $followUp = $workItemService->findFollowUpOrFail($this->workItem, $this->editingFollowUpId);
            $workItemService->updateFollowUp(
                $followUp,
                $validated['content'],
                auth()->user(),
                $validated['effectiveHours'] ?? null,
                $validated['pendingAttachmentIds'],
            );
        }

        $this->resetEditor();
        $this->dispatch('close-follow-up-modal');
        $this->dispatch('work-item-follow-up-saved');
        $this->dispatch('work-item-follow-up-changed');
    }

    public function excerpt(WorkItemFollowUp $followUp): string
    {
        return Str::of(strip_tags($followUp->content))
            ->squish()
            ->words(100, '...')
            ->toString();
    }

    private function resetEditor(): void
    {
        $this->reset(['editingFollowUpId', 'content', 'effectiveHours', 'followUpImage', 'pendingAttachmentIds']);
        $this->resetValidation();
    }

    public function render(WorkItemService $workItemService): View
    {
        $followUps = $workItemService->followUps($this->workItem);

        return view('livewire.work-items.work-item-follow-up-composer', [
            'followUps' => $followUps,
            'followUpCount' => $followUps->count(),
            'totalEffectiveHours' => (float) $followUps->sum(fn (WorkItemFollowUp $followUp): float => (float) ($followUp->effective_hours ?? 0)),
        ]);
    }
}
