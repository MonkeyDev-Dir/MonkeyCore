<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\AvatarService;
use App\Services\UserService;
use Livewire\Component;

class AvatarSelector extends Component
{
    public bool $isOpen = false;

    /** @var array<int, string> */
    public array $options = [];

    public ?string $selectedAvatarPath = null;

    public function open(AvatarService $avatarService): void
    {
        $this->options = $avatarService->generateRobotAvatars();
        $this->selectedAvatarPath = null;
        $this->isOpen = true;
    }

    public function select(string $avatarPath): void
    {
        if (! in_array($avatarPath, $this->options, true)) {
            return;
        }

        $this->selectedAvatarPath = $avatarPath;
    }

    public function save(UserService $userService): void
    {
        if ($this->selectedAvatarPath === null || ! in_array($this->selectedAvatarPath, $this->options, true)) {
            $this->addError('selectedAvatarPath', __('Selecciona un avatar.'));

            return;
        }

        $user = request()->user();

        abort_unless($user instanceof User, 403);

        $userService->selectAvatar($user, $this->selectedAvatarPath);

        session()->flash('toast', [
            'icon' => 'success',
            'title' => __('Avatar actualizado correctamente.'),
        ]);

        $this->redirectRoute('profile', navigate: false);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->selectedAvatarPath = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.profile.avatar-selector');
    }
}
