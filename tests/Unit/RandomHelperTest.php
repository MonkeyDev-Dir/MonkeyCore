<?php

use App\Helpers\RandomHelper;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('generates a unique value with the requested number of digits', function () {
    $existingClient = Client::factory()->create(['code' => '123456']);

    $code = RandomHelper::generateUniqueDigits(6, 'clients');

    expect($code)
        ->toHaveLength(6)
        ->toMatch('/^\d{6}$/')
        ->not->toBe($existingClient->code);
});

it('generates a unique alphanumeric value with a prefix', function () {
    $existingProject = Project::factory()->create(['code' => 'PROJ-2J4234H']);

    $code = RandomHelper::generateUniqueAlphanumeric(7, 'projects', 'PROJ');

    expect($code)
        ->toMatch('/^PROJ-[A-Z0-9]{7}$/')
        ->not->toBe($existingProject->code);
});
