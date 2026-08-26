<?php

use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('seeds the initial user and demo users', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(11)
        ->and(Client::count())->toBe(11)
        ->and(Client::query()->pluck('code')->unique())->toHaveCount(11)
        ->and(BackupConnection::where('name', 'Metai')->count())->toBe(1)
        ->and(User::where('email', 'me@gilberthrojas.com')->exists())->toBeTrue()
        ->and(User::whereNotNull('avatar_path')->count())->toBe(11);

    User::query()->pluck('avatar_path')->each(function (?string $avatarPath): void {
        expect($avatarPath)->toStartWith('avatars/');
        Storage::disk('public')->assertExists($avatarPath);
    });
});
