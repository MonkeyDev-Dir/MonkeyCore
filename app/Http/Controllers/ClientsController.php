<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use Illuminate\View\View;

class ClientsController extends Controller
{
    public function index(): View
    {
        return view('pages.clients');
    }

    public function show(string $clientCode, ClientService $clientService): View
    {
        $client = $clientService->findByCodeOrFail($clientCode);

        return view('pages.client-profile', [
            'client' => $client,
            'imageUrl' => $client->imageUrl(),
            'primaryContact' => $client->contacts->firstWhere('is_primary', true),
        ]);
    }
}
