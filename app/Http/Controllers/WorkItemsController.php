<?php

namespace App\Http\Controllers;

use App\Services\WorkItemService;
use Illuminate\View\View;

class WorkItemsController extends Controller
{
    public function index(): View
    {
        return view('pages.work-items');
    }

    public function show(string $publicCode, WorkItemService $workItemService): View
    {
        $workItem = $workItemService->findByPublicCodeOrFail($publicCode);

        return view('pages.work-item-show', [
            'workItem' => $workItem,
            'eventLabels' => [
                'created' => __('Caso creado'),
                'assigned' => __('Responsables asignados'),
            ],
        ]);
    }
}
