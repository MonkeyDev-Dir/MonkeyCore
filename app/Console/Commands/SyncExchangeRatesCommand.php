<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('exchange-rates:sync {--date= : Fecha a sincronizar en formato YYYY-MM-DD}')]
#[Description('Consulta y almacena los tipos de cambio del BCCR')]
class SyncExchangeRatesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $exchangeRateService): int
    {
        try {
            $date = $this->dateFromOption();
            $stored = $exchangeRateService->sync($date);
        } catch (Throwable $exception) {
            Log::channel('bccr')->error('Sincronización de tipos de cambio fallida', [
                'date' => $this->option('date'), 'exception' => $exception::class, 'error' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        Log::channel('bccr')->info('Sincronización de tipos de cambio completada', ['date' => $date->format('Y-m-d'), 'stored' => $stored]);
        $this->info("Tipos de cambio almacenados: {$stored}");

        return self::SUCCESS;
    }

    private function dateFromOption(): CarbonImmutable
    {
        $date = $this->option('date');
        if ($date === null) {
            return CarbonImmutable::now()->subDay();
        }

        try {
            $parsedDate = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        } catch (InvalidFormatException) {
            throw new InvalidFormatException('La fecha debe tener el formato YYYY-MM-DD.');
        }

        if ($parsedDate === null || $parsedDate->format('Y-m-d') !== $date) {
            throw new InvalidFormatException('La fecha debe tener el formato YYYY-MM-DD.');
        }

        return $parsedDate;
    }
}
