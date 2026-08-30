<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

#[Fillable(['name', 'description', 'active'])]
#[Hidden(['remember_token'])]
class ApiConsumer extends Authenticatable
{
    use HasApiTokens;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /** @return MorphMany<PersonalAccessToken, $this> */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }
}
