<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(AvatarService $avatarService): void
    {
        $robotAvatarState = fn (): array => [
            'avatar_path' => $avatarService->generateRobotAvatar(),
        ];

        User::factory()
            ->state($robotAvatarState)
            ->create([
                'name' => 'Gilberth',
                'lastname' => 'Rojas Alfaro',
                'ide' => '113420689',
                'email' => 'me@gilberthrojas.com',
                'email_verified_at' => now(),
                'password' => 'Monkey#1',
            ]);

        User::factory()
            ->count(10)
            ->state($robotAvatarState)
            ->create();
    }
}
