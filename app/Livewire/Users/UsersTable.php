<?php

namespace App\Livewire\Users;

use App\Services\UserService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class UsersTable extends Component
{
    public string $search = '';

    #[On('user-saved')]
    public function refreshUsers(): void {}

    public function render(UserService $userService): View
    {
        return view('livewire.users.users-table', [
            'users' => $userService->all($this->search),
            'headers' => [
                ['index' => 'name', 'label' => __('Nombre')],
                ['index' => 'ide', 'label' => __('Identificación')],
                ['index' => 'email', 'label' => __('Correo electrónico')],
            ],
        ]);
    }
}
