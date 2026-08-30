<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use TallStackUi\Traits\Interactions;
use Throwable;

class IntegrationsController extends Controller
{
    use Interactions;

    public function index(ExchangeRateService $exchangeRateService): View
    {
        return view('pages.integrations', [
            'integrations' => [
                [
                    'name' => __('Tipo de cambio BCCR'),
                    'description' => __('Consulta y almacena diariamente el tipo de cambio del dólar y el euro.'),
                    'status' => $exchangeRateService->integrationStatus(),
                ],
            ],
        ]);
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
