<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class ProjectModal extends Component
{
    public bool $isOpen = false;

    public string $clientCode = '';

    public ?int $projectId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public function openCreate(string $clientCode): void
    {
        $this->resetForm();
        $this->clientCode = $clientCode;
        $this->isOpen = true;
    }

    public function openEdit(string $clientCode, int $projectId, ClientService $clientService): void
    {
        $project = $clientService->findByCodeOrFail($clientCode)->projects()->findOrFail($projectId);

        $this->clientCode = $clientCode;
        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->code = $project->code ?? '';
        $this->description = $project->description ?? '';
        $this->resetValidation();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function save(ClientService $clientService): void
    {
        $client = $clientService->findByCodeOrFail($this->clientCode);
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('projects', 'name')->where(fn ($query) => $query->where('client_id', $client->id))->ignore($this->projectId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $project = $this->projectId === null
            ? null
            : $client->projects()->findOrFail($this->projectId);

        $clientService->saveProject($client, [
            'name' => $validated['name'],
            'description' => $this->sanitizeDescription($validated['description']) ?: null,
        ], $project);

        $wasEditing = $this->projectId !== null;
        $this->close();
        $this->dispatch('project-saved', message: $wasEditing ? __('Proyecto actualizado correctamente.') : __('Proyecto creado correctamente.'));
    }

    private function resetForm(): void
    {
        $this->reset(['clientCode', 'projectId', 'name', 'code', 'description']);
        $this->resetValidation();
    }

    private function sanitizeDescription(?string $description): string
    {
        $description = strip_tags($description ?? '', '<p><br><strong><em><u><s><h1><h2><h3><h4><h5><ul><ol><li><blockquote><hr><code><pre>');

        return (string) preg_replace('/<([a-z0-9]+)\s+[^>]*>/i', '<$1>', $description);
    }

    public function render(): View
    {
        return view('livewire.clients.project-modal');
    }
}
