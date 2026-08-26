<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    /**
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();
    }

    public function findOrFail(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    public function save(array $attributes, ?User $user = null): User
    {
        if ($user === null) {
            return User::create($attributes);
        }

        $user->update($attributes);

        return $user->refresh();
    }
}
