<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('logs the complete backup process when there are no active connections', function () {
    Storage::fake('s3');

    $this->artisan('backups:create')
        ->expectsOutput('Encolando respaldos remotos de bases de datos...')
        ->expectsOutput('Respaldos completados: 0')
        ->expectsOutput('Respaldos fallidos: 0')
        ->assertSuccessful();

    $logPath = collect(glob(storage_path('logs/request/backups-*.log')))
        ->sortByDesc(fn (string $path): int => (int) filemtime($path))
        ->first();

    expect($logPath)->not->toBeNull();
    expect(file_get_contents($logPath))
        ->toContain('Proceso de respaldos iniciado')
        ->toContain('Proceso de respaldos completado')
        ->toContain('Comando de respaldos finalizado');
});
