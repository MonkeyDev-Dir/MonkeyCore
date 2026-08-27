<?php

namespace App\Livewire\Clients;

use App\Models\BackupConnection;
use App\Models\BackupDatabaseType;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\DatabaseBackupService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class BackupConnectionModal extends Component
{
    public bool $isOpen = false;

    public ?int $connectionId = null;

    public string $clientCode = '';

    /** @var array<int, array{id: int, name: string}> */
    public array $projects = [];

    /** @var array<int, array{key: string, name: string}> */
    public array $databaseTypes = [];

    public ?int $projectId = null;

    public string $name = '';

    public string $databaseType = 'postgresql';

    public string $sshHost = '';

    public int $sshPort = 22;

    public string $sshUser = '';

    public string $postgresHost = '127.0.0.1';

    public int $postgresPort = 5432;

    public string $postgresDatabase = '';

    public string $postgresUser = '';

    public string $postgresPassword = '';

    public string $mysqlHost = '127.0.0.1';

    public int $mysqlPort = 3306;

    public string $mysqlDatabase = '';

    public string $mysqlUser = '';

    public string $mysqlPassword = '';

    public bool $isActive = true;

    public function openCreate(string $clientCode, ClientService $clientService, DatabaseBackupService $backupService): void
    {
        $this->resetForm();
        $this->clientCode = $clientCode;
        $this->loadOptions($clientService->findByCodeOrFail($clientCode), $backupService);
        $this->isOpen = true;
    }

    public function openEdit(string $clientCode, int $connectionId, ClientService $clientService, DatabaseBackupService $backupService): void
    {
        $this->resetForm();
        $client = $clientService->findByCodeOrFail($clientCode);
        $connection = $clientService->findBackupConnectionOrFail($client, $connectionId);
        $this->clientCode = $clientCode;
        $this->connectionId = $connection->id;
        $this->projectId = $connection->project_id;
        $this->name = $connection->name;
        $this->databaseType = $connection->database_type;
        $this->sshHost = $connection->ssh_host;
        $this->sshPort = $connection->ssh_port;
        $this->sshUser = $connection->ssh_user;
        $this->postgresHost = $connection->postgres_host ?? '127.0.0.1';
        $this->postgresPort = $connection->postgres_port ?? 5432;
        $this->postgresDatabase = $connection->postgres_database ?? '';
        $this->postgresUser = $connection->postgres_user ?? '';
        $this->mysqlHost = $connection->mysql_host ?? '127.0.0.1';
        $this->mysqlPort = $connection->mysql_port ?? 3306;
        $this->mysqlDatabase = $connection->mysql_database ?? '';
        $this->mysqlUser = $connection->mysql_user ?? '';
        $this->isActive = $connection->is_active;
        $this->loadOptions($client, $backupService);
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
        $validated = $this->validateForm($client);
        $connection = $this->connectionId === null
            ? null
            : $clientService->findBackupConnectionOrFail($client, $this->connectionId);

        $clientService->saveBackupConnection($client, [
            'project_id' => $validated['projectId'],
            'name' => $validated['name'],
            'database_type' => $validated['databaseType'],
            'ssh_host' => $validated['sshHost'],
            'ssh_port' => $validated['sshPort'],
            'ssh_user' => $validated['sshUser'],
            'postgres_host' => $validated['postgresHost'],
            'postgres_port' => $validated['postgresPort'],
            'postgres_database' => $validated['postgresDatabase'],
            'postgres_user' => $validated['postgresUser'],
            'postgres_password' => $validated['databaseType'] === 'postgresql'
                ? ($validated['postgresPassword'] ?: $connection?->postgres_password)
                : null,
            'mysql_host' => $validated['mysqlHost'] ?: null,
            'mysql_port' => $validated['mysqlPort'] ?: null,
            'mysql_database' => $validated['mysqlDatabase'] ?: null,
            'mysql_user' => $validated['mysqlUser'] ?: null,
            'mysql_password' => $validated['databaseType'] === 'mysql'
                ? ($validated['mysqlPassword'] ?: $connection?->mysql_password)
                : null,
            'is_active' => $validated['isActive'],
        ], $connection);

        $this->close();
        $this->dispatch('backup-connection-saved', message: $this->connectionId === null
            ? __('Configuración de respaldo creada correctamente.')
            : __('Configuración de respaldo actualizada correctamente.'));
    }

    public function testConnection(ClientService $clientService, DatabaseBackupService $backupService): void
    {
        $client = $clientService->findByCodeOrFail($this->clientCode);
        $validated = $this->validateForm($client, false);
        $connection = $this->connectionId === null
            ? null
            : $clientService->findBackupConnectionOrFail($client, $this->connectionId);

        try {
            $backupService->testConnection(new BackupConnection([
                'client_id' => $client->id,
                'project_id' => $validated['projectId'],
                'name' => $validated['name'],
                'database_type' => $validated['databaseType'],
                'ssh_host' => $validated['sshHost'],
                'ssh_port' => $validated['sshPort'],
                'ssh_user' => $validated['sshUser'],
                'ssh_private_key' => $connection?->ssh_private_key,
                'postgres_host' => $validated['postgresHost'],
                'postgres_port' => $validated['postgresPort'],
                'postgres_database' => $validated['postgresDatabase'],
                'postgres_user' => $validated['postgresUser'],
                'postgres_password' => $validated['postgresPassword'] ?: $connection?->postgres_password,
                'mysql_host' => $validated['mysqlHost'],
                'mysql_port' => $validated['mysqlPort'],
                'mysql_database' => $validated['mysqlDatabase'],
                'mysql_user' => $validated['mysqlUser'],
                'mysql_password' => $validated['mysqlPassword'] ?: $connection?->mysql_password,
            ]));
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('connectionTest', __('No fue posible comprobar la conexión. Revisa los datos e inténtalo nuevamente.'));

            return;
        }

        $this->dispatch('backup-connection-tested', message: __('La conexión y el comando se validaron correctamente.'));
    }

    /** @return array<string, mixed> */
    private function validateForm(Client $client, bool $validateName = true): array
    {
        return $this->validate([
            'name' => ['required', 'string', 'max:255', ...($validateName ? [Rule::unique('backup_connections', 'name')->where(fn ($query) => $query->where('client_id', $client->id))->ignore($this->connectionId)] : [])],
            'projectId' => ['nullable', 'integer', Rule::exists('projects', 'id')->where(fn ($query) => $query->where('client_id', $client->id))],
            'databaseType' => ['required', Rule::exists('backup_database_types', 'key')->where(fn ($query) => $query->where('is_active', true))],
            'sshHost' => ['required', 'string', 'max:255'],
            'sshPort' => ['required', 'integer', 'between:1,65535'],
            'sshUser' => ['required', 'string', 'max:255'],
            'postgresHost' => ['required_if:databaseType,postgresql', 'nullable', 'string', 'max:255'],
            'postgresPort' => ['required_if:databaseType,postgresql', 'nullable', 'integer', 'between:1,65535'],
            'postgresDatabase' => ['required_if:databaseType,postgresql', 'nullable', 'string', 'max:255'],
            'postgresUser' => ['required_if:databaseType,postgresql', 'nullable', 'string', 'max:255'],
            'postgresPassword' => ['nullable', 'string', 'max:255'],
            'mysqlHost' => ['required_if:databaseType,mysql', 'nullable', 'string', 'max:255'],
            'mysqlPort' => ['required_if:databaseType,mysql', 'nullable', 'integer', 'between:1,65535'],
            'mysqlDatabase' => ['required_if:databaseType,mysql', 'nullable', 'string', 'max:255'],
            'mysqlUser' => ['required_if:databaseType,mysql', 'nullable', 'string', 'max:255'],
            'mysqlPassword' => ['required_if:databaseType,mysql', 'nullable', 'string', 'max:255'],
            'isActive' => ['boolean'],
        ]);
    }

    private function loadOptions(Client $client, DatabaseBackupService $backupService): void
    {
        $this->projects = $client->projects
            ->map(fn ($project): array => ['id' => $project->id, 'name' => $project->name])
            ->all();
        $this->databaseTypes = $backupService->availableDatabaseTypes()
            ->map(fn (BackupDatabaseType $type): array => ['key' => $type->key, 'name' => $type->name])
            ->all();
    }

    private function resetForm(): void
    {
        $this->reset(['connectionId', 'clientCode', 'projects', 'databaseTypes', 'projectId', 'name', 'sshHost', 'sshUser', 'postgresDatabase', 'postgresUser', 'postgresPassword', 'mysqlDatabase', 'mysqlUser', 'mysqlPassword']);
        $this->databaseType = 'postgresql';
        $this->sshPort = 22;
        $this->postgresHost = '127.0.0.1';
        $this->postgresPort = 5432;
        $this->mysqlHost = '127.0.0.1';
        $this->mysqlPort = 3306;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.clients.backup-connection-modal');
    }
}
