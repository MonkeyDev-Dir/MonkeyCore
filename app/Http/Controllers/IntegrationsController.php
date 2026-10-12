<?php

namespace App\Http\Controllers;

use App\Services\ApifyCrService;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use TallStackUi\Traits\Interactions;
use Throwable;

class IntegrationsController extends Controller
{
    use Interactions;

    public function index(ApifyCrService $apifyCrService, ExchangeRateService $exchangeRateService): View
    {
        return view('pages.integrations', [
            'integrations' => [
                [
                    'name' => __('Tipo de cambio BCCR'),
                    'icon' => 'banknotes',
                    'description' => __('Consulta y almacena diariamente el tipo de cambio del dólar y el euro.'),
                    'status' => $exchangeRateService->integrationStatus(),
                    'sync_route' => 'integrations.sync',
                ],
                [
                    'name' => __('ApifyCR'),
                    'icon' => 'identification',
                    'description' => __('Consulta personas y entidades jurídicas del Registro Civil de Costa Rica.'),
                    'status' => $apifyCrService->integrationStatus(),
                    'sync_route' => null,
                    'test_route' => 'integrations.apifycr.test',
                ],
            ],
        ]);
    }

    public function testApifyCr(ApifyCrService $apifyCrService): JsonResponse
    {
        return response()->json($apifyCrService->testRandomPersonQuery());
    }

    public function sync(ExchangeRateService $exchangeRateService): RedirectResponse
    {
        try {
            $stored = $exchangeRateService->sync();
        } catch (Throwable $exception) {
            Log::channel('bccr')->error('Sincronización manual de tipos de cambio fallida', [
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            $this->toast()
                ->error(__('No fue posible actualizar los tipos de cambio.'), __('Revise los logs del BCCR para más información.'))
                ->flash()
                ->send();

            return to_route('integrations.index');
        }

        $this->toast()
            ->success(__('Integración ejecutada'), __('Se almacenaron :count valores del BCCR.', ['count' => $stored]))
            ->flash()
            ->send();

        return to_route('integrations.index');
    }
}
