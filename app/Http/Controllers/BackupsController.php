<?php

namespace App\Http\Controllers;

use App\Models\DatabaseBackup;
use App\Services\ClientService;
use App\Services\DatabaseBackupService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupsController extends Controller
{
    public function index(): View
    {
        return view('pages.backups');
    }

    public function client(string $clientCode, ClientService $clientService): View
    {
        return view('pages.client-backups', [
            'client' => $clientService->findByCodeOrFail($clientCode),
        ]);
    }

    public function download(string $path, DatabaseBackupService $backupService): StreamedResponse
    {
        $stream = $backupService->readStream($path);

        abort_unless(is_resource($stream), 404);

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, basename($path), ['Content-Type' => 'application/octet-stream']);
    }

    public function downloadClientBackup(string $clientCode, DatabaseBackup $backup, ClientService $clientService, DatabaseBackupService $backupService): StreamedResponse
    {
        $client = $clientService->findByCodeOrFail($clientCode);
        $stream = $backupService->readStreamForClient($client, $backup);

        abort_unless(is_resource($stream), 404);

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $backup->filename, ['Content-Type' => 'application/octet-stream']);
    }
}
