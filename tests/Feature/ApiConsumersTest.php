<?php

use App\Livewire\ApiConsumers\ApiConsumerModal;
use App\Livewire\ApiConsumers\ApiConsumersTable;
use App\Models\ApiConsumer;
use App\Models\ExchangeRate;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

it('shows the API consumers page to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('api-consumers.index'))
        ->assertOk()
        ->assertViewIs('pages.api-consumers')
        ->assertSee(__('API Tokens'));
});

it('redirects guests from the API consumers page', function () {
    $this->get(route('api-consumers.index'))
        ->assertRedirectToRoute('login');
});

it('creates an API consumer and displays its token only in the modal response', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ApiConsumerModal::class)
        ->call('open')
        ->set('name', 'Tienda principal')
        ->set('description', 'Consulta tipos de cambio')
        ->call('save')
        ->assertSet('isOpen', true)
        ->assertNotSet('plainTextToken', null)
        ->assertDispatched('api-consumer-created');

    $consumer = ApiConsumer::query()->where('name', 'Tienda principal')->firstOrFail();
    $token = PersonalAccessToken::query()->where('tokenable_id', $consumer->id)->firstOrFail();

    expect($token->abilities)->toBe(['*'])
        ->and($token->tokenable_type)->toBe(ApiConsumer::class);
});

it('revokes a consumer token', function () {
    $user = User::factory()->create();
    $consumer = ApiConsumer::query()->create(['name' => 'Tienda', 'active' => true]);
    $token = $consumer->createToken('Tienda')->accessToken;

    Livewire::actingAs($user)
        ->test(ApiConsumersTable::class)
        ->call('revokeToken', $consumer->id, $token->id);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('generates another token for an existing consumer', function () {
    $user = User::factory()->create();
    $consumer = ApiConsumer::query()->create(['name' => 'Tienda', 'active' => true]);

    Livewire::actingAs($user)
        ->test(ApiConsumerModal::class)
        ->call('openToken', $consumer->id)
        ->set('tokenName', 'Tienda - rotación')
        ->call('save')
        ->assertNotSet('plainTextToken', null);

    expect($consumer->tokens()->count())->toBe(1)
        ->and($consumer->tokens()->first()->name)->toBe('Tienda - rotación');
});

it('deletes a consumer and all its tokens', function () {
    $user = User::factory()->create();
    $consumer = ApiConsumer::query()->create(['name' => 'Tienda', 'active' => true]);
    $token = $consumer->createToken('Tienda')->accessToken;

    Livewire::actingAs($user)
        ->test(ApiConsumersTable::class)
        ->call('deleteConsumer', $consumer->id);

    $this->assertDatabaseMissing('api_consumers', ['id' => $consumer->id]);
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('uses SweetAlert2 confirmation before deleting a consumer from the table', function () {
    $user = User::factory()->create();
    ApiConsumer::query()->create(['name' => 'Tienda', 'active' => true]);

    $this->actingAs($user)
        ->get(route('api-consumers.index'))
        ->assertSee('confirmDelete', false)
        ->assertSee('Swal.fire', false)
        ->assertDontSee('wire:click="deleteConsumer', false);
});

it('authenticates an exchange rate request with an API consumer token', function () {
    ExchangeRate::query()->create([
        'rate_date' => '2026-08-28',
        'currency' => 'USD',
        'rate_type' => 'buy',
        'indicator_code' => 317,
        'value' => 500.25,
    ]);

    $consumer = ApiConsumer::query()->create(['name' => 'Tienda', 'active' => true]);
    $token = $consumer->createToken('Tienda', ['unrelated:ability'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/exchange-rates/latest')
        ->assertOk()
        ->assertJsonPath('data.date', '2026-08-28');
});
