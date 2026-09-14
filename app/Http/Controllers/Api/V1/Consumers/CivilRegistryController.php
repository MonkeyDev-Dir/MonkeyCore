<?php

namespace App\Http\Controllers\Api\V1\Consumers;

use App\Http\Controllers\Controller;
use App\Services\ApifyCrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CivilRegistryController extends Controller
{
    public function __construct(private ApifyCrService $apifyCrService) {}

    public function showPerson(Request $request): JsonResponse
    {
        $cedula = $request->validate(['cedula' => ['required', 'digits:9']])['cedula'];

        try {
            $person = $this->apifyCrService->consultarPersona($cedula);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'No fue posible consultar el registro civil.'], 502);
        }

        if ($person === null) {
            return response()->json(['message' => 'No se encontró información para la cédula indicada.'], 404);
        }

        return response()->json(['data' => $person]);
    }

    public function showJuridical(Request $request): JsonResponse
    {
        $cedula = $request->validate(['cedula' => ['required', 'digits:10']])['cedula'];

        try {
            $company = $this->apifyCrService->consultarJuridica($cedula);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'No fue posible consultar el registro civil.'], 502);
        }

        if ($company === null) {
            return response()->json(['message' => 'No se encontró información para la cédula jurídica indicada.'], 404);
        }

        return response()->json(['data' => $company]);
    }
}
