<?php

namespace App\View\Components;

use App\Helpers\MenuHelper;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Sidebar extends Component
{
    /**
     * @var array<int, array{title: string, items: array<int, array<string, mixed>>}>
     */
    public array $menuGroups;

    public string $currentPath;

    public function __construct()
    {
        $this->menuGroups = MenuHelper::getMenuGroups();
        $this->currentPath = request()->path();
    }

    public function render(): View
    {
        return view('components.sidebar');
    }
}
