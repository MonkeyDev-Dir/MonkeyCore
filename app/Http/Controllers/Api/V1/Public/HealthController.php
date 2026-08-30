<?php

namespace App\Http\Controllers\Api\V1\Public;

use Illuminate\Http\JsonResponse;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'service' => config('app.name'),
            ],
        ]);
    }
}
