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
