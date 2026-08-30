<?php

namespace App\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['rate_date', 'currency', 'rate_type', 'indicator_code', 'value'])]
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    public const CurrencyUsd = 'USD';

    public const CurrencyEur = 'EUR';

    public const TypeBuy = 'buy';

    public const TypeSell = 'sell';

    public const TypeReference = 'reference';

    protected function casts(): array
    {
        return [
            'rate_date' => 'date:Y-m-d',
            'indicator_code' => 'integer',
            'value' => 'decimal:8',
        ];
    }
}
