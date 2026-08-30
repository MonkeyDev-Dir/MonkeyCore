<?php

namespace App\Http\Controllers;

use App\Services\BccrExchangeRateService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExchangeRatesController extends Controller
{
    public function __construct(private BccrExchangeRateService $bccrExchangeRateService) {}

    public function show(Request $request): JsonResponse
    {
        $date = $request->query('date');

        $parsedDate = null;

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            try {
                $parsedDate = CarbonImmutable::createFromFormat('!Y-m-d', $date);
            } catch (InvalidFormatException) {
                $parsedDate = null;
            }
        }

        if ($date !== null && (! is_string($date) || $parsedDate === null || $parsedDate->format('Y-m-d') !== $date)) {
            throw ValidationException::withMessages([
                'date' => 'La fecha debe tener el formato YYYY-MM-DD.',
            ]);
        }

        try {
            $rates = $this->bccrExchangeRateService->obtenerTiposDeCambio(
                $parsedDate,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No fue posible consultar los tipos de cambio del BCCR.',
            ], 502);
        }

        return response()->json(['data' => $rates]);
    }
}
