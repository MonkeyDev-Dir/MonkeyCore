<?php

namespace App\Http\Controllers\Api\V1\Consumers;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class ExchangeRatesController extends Controller
{
    public function __construct(private ExchangeRateService $exchangeRateService) {}

    public function latest(): JsonResponse
    {
        $rates = $this->exchangeRateService->latestRates();

        if ($rates === null) {
            return response()->json([
                'message' => 'No hay tipos de cambio disponibles.',
            ], 404);
        }

        return response()->json([
            'data' => $rates,
        ]);
    }

    public function show(string $date): JsonResponse
    {
        $rates = $this->exchangeRateService->ratesForDate(
            CarbonImmutable::createFromFormat('!Y-m-d', $date),
        );

        if ($rates === null) {
            return response()->json([
                'message' => 'No hay tipos de cambio para la fecha solicitada.',
            ], 404);
        }

        return response()->json([
            'data' => $rates,
        ]);
    }
}
