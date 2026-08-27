<?php

use App\Helpers\MenuHelper;
use Tests\TestCase;

uses(TestCase::class);

it('places users inside the system menu group', function () {
    $systemGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'Sistema');

    expect($systemGroup['items'])
        ->toContain([
            'icon' => 'user-profile',
            'name' => 'Usuarios',
            'path' => '/users',
        ]);
});

it('uses dashboard as a direct menu link', function () {
    $mainGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'MENÚ');

    expect($mainGroup['items'])
        ->toContain([
            'icon' => 'dashboard',
            'name' => 'Dashboard',
            'path' => '/',
        ]);
});

it('places clients inside the main menu group', function () {
    $mainGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'MENÚ');

    expect($mainGroup['items'])
        ->toContain([
            'icon' => 'users',
            'name' => 'Clientes',
            'path' => '/clients',
        ]);
});

it('places backups inside the main menu group', function () {
    $mainGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'MENÚ');

    expect($mainGroup['items'])
        ->toContain([
            'icon' => 'task',
            'name' => 'Respaldos',
            'path' => '/backups',
        ]);
});

it('does not include template navigation items', function () {
    $itemNames = collect(MenuHelper::getMenuGroups())
        ->pluck('items')
        ->flatten(1)
        ->pluck('name');

    expect($itemNames)
        ->not->toContain('Calendar')
        ->not->toContain('Forms')
        ->not->toContain('Tables')
        ->not->toContain('Pages')
        ->not->toContain('Charts')
        ->not->toContain('UI Elements')
        ->not->toContain('Authentication');
});

it('returns a Lucide icon for menu icons', function () {
    expect(MenuHelper::getIconSvg('dashboard'))
        ->toContain('data-lucide="layout-dashboard"')
        ->not->toContain('<svg');
});
