<?php

use App\Livewire\Clients\ClientDomains;
use App\Livewire\Clients\DomainModal;
use App\Models\Client;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('shows the domains tab on the client profile', function () {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('clients.show', ['clientCode' => $client->code]))
        ->assertOk()
        ->assertSee(__('Dominios'))
        ->assertSee('client-domains');
});

it('creates a domain for the selected client with an annual renewal period', function () {
    $client = Client::factory()->create();

    Livewire::test(DomainModal::class)
        ->call('openCreate', $client->code)
        ->fill([
            'name' => 'ejemplo.com',
            'hostingProvider' => 'Servidor Uno',
            'annualCost' => '450.00',
            'currency' => 'USD',
            'expiresAt' => '27/08/2027',
            'renewalPeriodYears' => 1,
            'notes' => 'Renovar antes del vencimiento.',
        ])
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('domain-saved');

    $domain = Domain::query()->where('name', 'ejemplo.com')->firstOrFail();

    expect($domain->client_id)->toBe($client->id)
        ->and((string) $domain->annual_cost)->toBe('450.00')
        ->and($domain->expires_at->format('Y-m-d'))->toBe('2027-08-27')
        ->and($domain->renewal_period_years)->toBe(1);
});

it('edits a domain from its client profile', function () {
    $client = Client::factory()->create();
    $domain = Domain::factory()->create(['client_id' => $client->id, 'name' => 'anterior.com']);

    Livewire::test(DomainModal::class)
        ->call('openEdit', $client->code, $domain->id)
        ->assertSet('name', 'anterior.com')
        ->set('name', 'actualizado.com')
        ->call('save')
        ->assertSet('isOpen', false);

    expect($domain->refresh()->name)->toBe('actualizado.com');
});

it('validates a domain and prevents editing another clients domain', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $domain = Domain::factory()->create(['client_id' => $otherClient->id]);

    Livewire::test(DomainModal::class)
        ->call('openCreate', $client->code)
        ->set('currency', 'GTQ')
        ->set('expiresAt', '27-08-2027')
        ->call('save')
        ->assertHasErrors(['name', 'currency', 'expiresAt']);

    expect(fn () => Livewire::test(DomainModal::class)
        ->call('openEdit', $client->code, $domain->id))
        ->toThrow(ModelNotFoundException::class);
});

it('renders the selected clients domains', function () {
    $client = Client::factory()->create();
    $domain = Domain::factory()->create(['client_id' => $client->id, 'name' => 'visible.com']);

    Livewire::test(ClientDomains::class, ['clientCode' => $client->code])
        ->assertSee($domain->name)
        ->assertSee($domain->hosting_provider);
});

it('shows a migration warning for domains outside DonDominio', function () {
    $client = Client::factory()->create();
    $pendingDomain = Domain::factory()->create(['client_id' => $client->id, 'hosting_provider' => 'Namecheap']);
    $migratedDomain = Domain::factory()->create(['client_id' => $client->id, 'hosting_provider' => 'DonDominio']);

    Livewire::test(ClientDomains::class, ['clientCode' => $client->code])
        ->assertSee(__('Pendiente de migración a DonDominio'))
        ->assertSee('x-tooltip', false)
        ->assertSee('text-orange-500', false)
        ->assertDontSee('role="alert"', false)
        ->assertSee($pendingDomain->name)
        ->assertSee($migratedDomain->name);

    expect($pendingDomain->isHostedAtDonDominio())->toBeFalse()
        ->and($migratedDomain->isHostedAtDonDominio())->toBeTrue();
});

it('shows the migration warning only while editing a non-DonDominio domain', function () {
    $client = Client::factory()->create();
    $pendingDomain = Domain::factory()->create(['client_id' => $client->id, 'hosting_provider' => 'Namecheap']);
    $migratedDomain = Domain::factory()->create(['client_id' => $client->id, 'hosting_provider' => 'DonDominio']);

    Livewire::test(DomainModal::class)
        ->call('openEdit', $client->code, $pendingDomain->id)
        ->assertSee(__('Pendiente de migración a DonDominio'))
        ->assertSee('role="alert"', false)
        ->assertSee('bg-orange-50/30', false)
        ->assertSee('border-orange-200/50', false)
        ->assertSee('font-normal', false)
        ->assertSee('text-orange-700/70', false)
        ->assertSee('gap-1', false)
        ->assertSee('px-1 py-2', false)
        ->assertSee('dark:bg-orange-500/15', false)
        ->assertSee('dark:text-orange-300', false);

    Livewire::test(DomainModal::class)
        ->call('openEdit', $client->code, $migratedDomain->id)
        ->assertDontSee(__('Pendiente de migración a DonDominio'))
        ->assertDontSee('role="alert"', false);
});
