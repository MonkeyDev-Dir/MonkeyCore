<?php

namespace App\Models;

use Database\Factories\ClientAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'type', 'address_line', 'city', 'state', 'country', 'postal_code', 'is_primary'])]
class ClientAddress extends Model
{
    /** @use HasFactory<ClientAddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
