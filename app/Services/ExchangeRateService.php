<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExchangeRateService
{
    public function __construct(public BccrExchangeRateService $bccrExchangeRateService) {}

    public function sync(?CarbonImmutable $date = null): int
    {
        $date ??= CarbonImmutable::now((string) config('services.bccr.timezone'))->subDay();
        $rates = $this->bccrExchangeRateService->obtenerTiposDeCambio($date);

        return DB::transaction(function () use ($rates): int {
            $rows = [
                ['rate_date' => $rates['dollar']['buy']['date'], 'currency' => ExchangeRate::CurrencyUsd, 'rate_type' => ExchangeRate::TypeBuy, 'indicator_code' => (int) $rates['dollar']['buy']['code'], 'value' => $rates['dollar']['buy']['value']],
                ['rate_date' => $rates['dollar']['sell']['date'], 'currency' => ExchangeRate::CurrencyUsd, 'rate_type' => ExchangeRate::TypeSell, 'indicator_code' => (int) $rates['dollar']['sell']['code'], 'value' => $rates['dollar']['sell']['value']],
                ['rate_date' => $rates['euro']['date'], 'currency' => ExchangeRate::CurrencyEur, 'rate_type' => ExchangeRate::TypeBuy, 'indicator_code' => (int) $rates['euro']['code'], 'value' => round($rates['euro']['value'] * $rates['dollar']['buy']['value'], 8)],
                ['rate_date' => $rates['euro']['date'], 'currency' => ExchangeRate::CurrencyEur, 'rate_type' => ExchangeRate::TypeSell, 'indicator_code' => (int) $rates['euro']['code'], 'value' => round($rates['euro']['value'] * $rates['dollar']['sell']['value'], 8)],
            ];

            foreach ($rows as $row) {
                ExchangeRate::query()->updateOrCreate(
                    ['rate_date' => $row['rate_date'], 'currency' => $row['currency'], 'rate_type' => $row['rate_type']],
                    ['indicator_code' => $row['indicator_code'], 'value' => $row['value']],
                );
            }

            return count($rows);
        });
    }

    /** @return array{active: bool, last_run_at: CarbonImmutable|null} */
    public function integrationStatus(): array
    {
        return [
            'active' => is_string(config('services.bccr.token')) && config('services.bccr.token') !== '',
            'last_run_at' => ExchangeRate::query()->latest('updated_at')->first()?->updated_at,
        ];
    }

    /**
     * @return array{date: string, source: string, rates: array<int, array{currency: string, type: string, value: string}>, updated_at: string|null}|null
     */
    public function latestRates(): ?array
    {
        $latestDate = ExchangeRate::query()->max('rate_date');

        if (! is_string($latestDate)) {
            return null;
        }

        return $this->ratesForDate(CarbonImmutable::parse($latestDate));
    }

    /**
     * @return array{date: string, source: string, rates: array<int, array{currency: string, type: string, value: string}>, updated_at: string|null}|null
     */
    public function ratesForDate(CarbonImmutable $date): ?array
    {
        $rates = ExchangeRate::query()
            ->whereDate('rate_date', $date)
            ->orderBy('currency')
            ->orderBy('rate_type')
            ->get();

        if ($rates->isEmpty()) {
            return null;
        }

        return [
            'date' => $date->format('Y-m-d'),
            'source' => 'BCCR',
            'rates' => $this->formatRates($rates),
            'updated_at' => $rates->max('updated_at')?->toISOString(),
        ];
    }

    /**
     * @param  Collection<int, ExchangeRate>  $rates
     * @return array<int, array{currency: string, type: string, value: string}>
     */
    private function formatRates(Collection $rates): array
    {
        return $rates->map(fn (ExchangeRate $rate): array => [
            'currency' => $rate->currency,
            'type' => $rate->rate_type,
            'value' => (string) $rate->value,
        ])->values()->all();
    }
}
