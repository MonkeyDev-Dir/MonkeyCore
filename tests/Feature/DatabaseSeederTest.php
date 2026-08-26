<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the initial user and demo users', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(11)
        ->and(User::where('email', 'me@gilberthrojas.com')->exists())->toBeTrue();
});
