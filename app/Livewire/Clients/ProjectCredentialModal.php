<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class ProjectCredentialModal extends Component
{
    public bool $isOpen = false;

    public string $clientCode = '';

    public ?int $projectId = null;

    public ?int $credentialId = null;

    public string $name = '';

    public string $type = 'other';

    public string $loginUrl = '';

    public string $username = '';

    public string $password = '';

    public string $notes = '';

    public function openCreate(string $clientCode, int $projectId): void
    {
        $this->resetForm();
        $this->clientCode = $clientCode;
        $this->projectId = $projectId;
        $this->isOpen = true;
    }

    public function openEdit(string $clientCode, int $projectId, int $credentialId, ClientService $clientService): void
    {
        $this->resetForm();
        $client = $clientService->findByCodeOrFail($clientCode);
        $project = $client->projects()->findOrFail($projectId);
        $credential = $clientService->findProjectCredentialOrFail($project, $credentialId);
        $this->clientCode = $clientCode;
        $this->projectId = $project->id;
        $this->credentialId = $credential->id;
        $this->name = $credential->name;
        $this->type = $credential->type;
        $this->loginUrl = $credential->login_url ?? '';
        $this->username = $credential->username ?? '';
        $this->notes = $credential->notes ?? '';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
        $this->password = '';
    }

    public function save(ClientService $clientService): void
    {
        $client = $clientService->findByCodeOrFail($this->clientCode);
        $project = $client->projects()->findOrFail($this->projectId);
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['wordpress', 'hosting', 'ftp', 'cpanel', 'other'])],
            'loginUrl' => ['nullable', 'url', 'max:2048'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => [$this->credentialId === null ? 'required' : 'nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $credential = $this->credentialId === null ? null : $clientService->findProjectCredentialOrFail($project, $this->credentialId);

        if ($credential !== null && ($validated['password'] ?? '') === '') {
            unset($validated['password']);
        }

        $attributes = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'login_url' => $validated['loginUrl'] ?: null,
            'username' => $validated['username'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->password !== '') {
            $attributes['password'] = $this->password;
        }

        $clientService->saveProjectCredential($project, $attributes, $credential);
        $wasEditing = $this->credentialId !== null;
        $this->close();
        $this->dispatch('project-credential-saved', message: $wasEditing ? __('Credencial actualizada correctamente.') : __('Credencial creada correctamente.'));
    }

    private function resetForm(): void
    {
        $this->reset(['clientCode', 'projectId', 'credentialId', 'name', 'type', 'loginUrl', 'username', 'password', 'notes']);
        $this->type = 'other';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.clients.project-credential-modal');
    }
}
