<?php

use App\Helpers\RandomHelper;
use App\Models\Client;
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
