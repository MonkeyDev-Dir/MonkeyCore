<?php

namespace App\Livewire\Users;

use App\Services\ApifyCrService;
use App\Services\GeminiService;
use App\Services\UserService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class UserModal extends Component
{
    public bool $isOpen = false;

    public ?int $userId = null;

    public string $name = '';

    public string $lastname = '';

    public string $ide = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[On('open-user-create')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    #[On('open-user-edit')]
    public function edit(int $userId, UserService $userService): void
    {
        $user = $userService->findOrFail($userId);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->lastname = $user->lastname;
        $this->ide = $user->ide;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function lookupPerson(ApifyCrService $apifyCrService, GeminiService $geminiService): void
    {
        $this->resetErrorBag(['ide', 'lookup']);

        $this->validateOnly('ide', [
            'ide' => ['required', 'regex:/^\d{9}$/'],
        ]);

        try {
            $person = $apifyCrService->consultarPersona($this->ide);
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('lookup', __('No fue posible consultar la información de la cédula.'));

            return;
        }

        if ($person === null) {
            $this->addError('lookup', __('No se encontró información para la cédula indicada.'));

            return;
        }

        $this->ide = (string) ($person['cedula'] ?? $this->ide);
        $name = trim((string) ($person['nombre'] ?? ''));
        $lastname = trim(implode(' ', array_filter([
            $person['primer_apellido'] ?? $person['apellido1'] ?? $person['apellido'] ?? null,
            $person['segundo_apellido'] ?? $person['apellido2'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== '')));

        try {
            $this->name = $geminiService->corregirNombreApellido($name);
            $this->lastname = $geminiService->corregirNombreApellido($lastname);
        } catch (Throwable $exception) {
            report($exception);

            $this->name = $name;
            $this->lastname = $lastname;
        }
    }

    public function save(UserService $userService): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'ide' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => [$this->userId === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated['password'] === null || $validated['password'] === '') {
            unset($validated['password']);
        }

        $editingUserId = $this->userId;
        $userService->save($validated, $editingUserId === null ? null : $userService->findOrFail($editingUserId));

        $this->close();
        $this->dispatch('user-saved', message: $editingUserId === null
            ? __('Usuario creado correctamente.')
            : __('Usuario actualizado correctamente.'));
    }

    public function render()
    {
        return view('livewire.users.user-modal');
    }

    private function resetForm(): void
    {
        $this->reset(['userId', 'name', 'lastname', 'ide', 'email', 'password', 'password_confirmation']);
        $this->resetValidation();
    }
}
