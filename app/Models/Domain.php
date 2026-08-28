<?php

namespace App\Models;

use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['client_id', 'name', 'hosting_provider', 'annual_cost', 'currency', 'expires_at', 'renewal_period_years', 'notes'])]
class Domain extends Model
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'annual_cost' => 'decimal:2',
            'expires_at' => 'date',
            'renewal_period_years' => 'integer',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isHostedAtDonDominio(): bool
    {
        return Str::lower(trim((string) $this->hosting_provider)) === 'dondominio';
    }
}
