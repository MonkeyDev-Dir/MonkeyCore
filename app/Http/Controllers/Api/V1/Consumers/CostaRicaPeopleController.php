<?php

namespace App\Http\Controllers\Api\V1\Consumers;

use App\Http\Controllers\Controller;
use App\Services\ApifyCrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class CostaRicaPeopleController extends Controller
{
    public function __construct(private ApifyCrService $apifyCrService) {}

    public function show(string $cedula): JsonResponse
    {
        if (! preg_match('/^\d{9}$/', $cedula)) {
            throw ValidationException::withMessages([
                'cedula' => 'La cédula debe contener exactamente 9 dígitos.',
            ]);
        }

        try {
            $person = $this->apifyCrService->consultarPersona($cedula);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No fue posible consultar el registro de Costa Rica.',
            ], 502);
        }

        if ($person === null) {
            return response()->json([
                'message' => 'No se encontró información para la cédula indicada.',
            ], 404);
        }

        return response()->json([
            'data' => $person,
        ]);
    }
}
