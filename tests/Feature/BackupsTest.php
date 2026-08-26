<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('requires authentication to view database backups', function () {
    $this->get(route('backups.index'))
        ->assertRedirectToRoute('login');
});

it('lists private database backups stored in s3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('database-backups/backup-2026-08-26-050000.backup', 'backup contents');
    Storage::disk('s3')->put('database-backups/ignore.txt', 'not a backup');

    $this->actingAs(User::factory()->create())
        ->get(route('backups.index'))
        ->assertOk()
        ->assertViewIs('pages.backups')
        ->assertSee('backup-2026-08-26-050000.backup')
        ->assertDontSee('ignore.txt');
});

it('downloads a backup from s3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('database-backups/backup-2026-08-26-050000.backup', 'backup contents');

    $this->actingAs(User::factory()->create())
        ->get(route('backups.download', ['path' => 'database-backups/backup-2026-08-26-050000.backup']))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=backup-2026-08-26-050000.backup')
        ->assertStreamedContent('backup contents');
});

it('does not allow downloading a path outside the backup directory', function () {
    Storage::fake('s3');

    $this->actingAs(User::factory()->create())
        ->get(route('backups.download', ['path' => 'other/file.backup']))
        ->assertNotFound();
});
