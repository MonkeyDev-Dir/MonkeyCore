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

it('places exchange rate documentation inside the APIs submenu', function () {
    $mainGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'MENÚ');

    $apis = collect($mainGroup['items'])->firstWhere('name', 'APIS');

    expect($apis['icon'])->toBe('apis')
        ->and($apis['subItems'])->toContain([
            'name' => 'Tipo de cambio',
            'path' => '/apis/exchange-rates',
        ]);
});

it('uses different icons for APIs and API tokens', function () {
    $mainGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'MENÚ');
    $systemGroup = collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'Sistema');

    $apis = collect($mainGroup['items'])->firstWhere('name', 'APIS');
    $tokens = collect($systemGroup['items'])->firstWhere('name', 'API Tokens');

    expect($apis['icon'])->not->toBe($tokens['icon'])
        ->and(MenuHelper::getIconSvg($apis['icon']))->toContain('data-lucide="brackets"')
        ->and(MenuHelper::getIconSvg($tokens['icon']))->toContain('data-lucide="key-round"');
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

it('places the work desk above APIs in the main menu group', function () {
    $mainItems = collect(collect(MenuHelper::getMenuGroups())
        ->firstWhere('title', 'MENÚ')['items']);

    expect($mainItems->pluck('name')->all())->toContain('Mesa de trabajo')
        ->and($mainItems->pluck('name')->search('Mesa de trabajo'))
        ->toBeLessThan($mainItems->pluck('name')->search('APIS'));
});
