<?php

use App\Livewire\CivilConsultation\CivilConsultation;
use App\Models\CivilRegistryRecord;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('shows the civil consultation module to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('civil-consultation.index'))
        ->assertOk()
        ->assertSeeText(__('Consulta civil'))
        ->assertSeeText(__('Número de cédula'));
});

it('uses a local civil consultation without calling the API', function () {
    $consultation = CivilRegistryRecord::query()->create([
        'type' => CivilRegistryRecord::TypePerson,
        'identification' => '123456789',
        'name' => 'JUAN',
        'first_surname' => 'PEREZ',
        'second_surname' => 'MORA',
        'found' => true,
        'consulted_at' => now(),
    ]);

    Http::preventStrayRequests();

    Livewire::actingAs(User::factory()->create())
        ->test(CivilConsultation::class)
        ->set('identification', $consultation->identification)
        ->call('consult')
        ->assertSet('result.nombre', 'Juan')
        ->assertSee('Perez');
});

it('consults and stores a civil record when it is not local', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=987654321' => Http::response([
            'cedula' => '987654321',
            'nombre' => 'MARIA',
            'apellido1' => 'LOPEZ',
            'provincia' => 'HEREDIA',
        ]),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(CivilConsultation::class)
        ->set('identification', '987654321')
        ->call('consult')
        ->assertSet('result.nombre', 'Maria')
        ->assertSee('Lopez');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://tse.apifycr.com/api/v2/cedula?cedula=987654321');
    expect(CivilRegistryRecord::query()
        ->where('identification', '987654321')
        ->value('province'))->toBe('HEREDIA');
});

it('shows an error when the civil registry provider fails', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=987654321' => Http::response([], 500),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(CivilConsultation::class)
        ->set('identification', '987654321')
        ->call('consult')
        ->assertHasErrors(['lookup'])
        ->assertSet('result', null);
});

it('rejects an identification with the wrong length for the selected type', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(CivilConsultation::class)
        ->set('identification', '123')
        ->call('consult')
        ->assertHasErrors(['identification']);
});
