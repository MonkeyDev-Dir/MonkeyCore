<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupsController extends Controller
{
    public function index(DatabaseBackupService $backupService): View
    {
        return view('pages.backups', ['backups' => $backupService->all()]);
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
}
