<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(private AvatarService $avatarService) {}

    /**
     * @return Collection<int, User>
     */
    public function all(string $search = ''): Collection
    {
        return User::query()
            ->when(trim($search) !== '', function ($query) use ($search): void {
                $term = '%'.Str::lower(Str::ascii(trim($search))).'%';
                $columns = ['name', 'lastname', 'ide', 'email'];

                $query->where(function ($query) use ($term, $columns): void {
                    foreach ($columns as $column) {
                        $method = $column === 'name' ? 'whereRaw' : 'orWhereRaw';
                        $expression = DB::connection()->getDriverName() === 'pgsql'
                            ? "unaccent(lower(users.{$column}))"
                            : "lower(users.{$column})";

                        $query->{$method}("{$expression} LIKE ?", [$term]);
                    }
                });
            })
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
            return User::create([
                ...$attributes,
                'avatar_path' => $attributes['avatar_path'] ?? $this->avatarService->generateRobotAvatar(),
            ]);
        }

        $user->update($attributes);

        return $user->refresh();
    }

    public function regenerateAvatar(User $user): User
    {
        $oldAvatarPath = $user->avatar_path;
        $newAvatarPath = $this->avatarService->generateRobotAvatar();

        $user->update([
            'avatar_path' => $newAvatarPath,
        ]);

        if ($oldAvatarPath !== null) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return $user->refresh();
    }

    public function selectAvatar(User $user, string $avatarPath): User
    {
        $this->updateAvatar($user, $avatarPath);

        return $user->refresh();
    }

    private function updateAvatar(User $user, string $avatarPath): void
    {
        $oldAvatarPath = $user->avatar_path;

        $user->update([
            'avatar_path' => $avatarPath,
        ]);

        if ($oldAvatarPath !== null && $oldAvatarPath !== $avatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }
    }
}
