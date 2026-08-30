<?php

namespace App\Services;

use App\Models\ApiConsumer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;

class ApiConsumerService
{
    /** @return Collection<int, ApiConsumer> */
    public function all(?string $search = null): Collection
    {
        return ApiConsumer::query()
            ->with(['tokens' => fn ($query) => $query->latest()])
            ->when($search, fn ($query, string $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->get();
    }

    /**
     * @param  array{ name: string, description: string|null, active: bool }  $data
     * @return array{0: ApiConsumer, 1: NewAccessToken}
     */
    public function createWithToken(array $data, ?string $expiresAt = null): array
    {
        return DB::transaction(function () use ($data, $expiresAt): array {
            $consumer = ApiConsumer::query()->create($data);
            $token = $consumer->createToken(
                $consumer->name,
                ['*'],
                $expiresAt === null ? null : now()->parse($expiresAt),
            );

            return [$consumer, $token];
        });
    }

    public function issueToken(ApiConsumer $consumer, string $tokenName, ?string $expiresAt = null): NewAccessToken
    {
        return $consumer->createToken(
            $tokenName,
            ['*'],
            $expiresAt === null ? null : now()->parse($expiresAt),
        );
    }

    public function revokeToken(ApiConsumer $consumer, int $tokenId): void
    {
        $consumer->tokens()->whereKey($tokenId)->delete();
    }

    public function deactivate(ApiConsumer $consumer): void
    {
        DB::transaction(function () use ($consumer): void {
            $consumer->update(['active' => false]);
            $consumer->tokens()->delete();
        });
    }

    public function delete(ApiConsumer $consumer): void
    {
        DB::transaction(function () use ($consumer): void {
            $consumer->tokens()->delete();
            $consumer->delete();
        });
    }
}
