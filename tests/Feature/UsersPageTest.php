<?php

use App\Livewire\Users\UserModal;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('shows the users page to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/users')
        ->assertOk()
        ->assertViewIs('pages.users')
        ->assertSee($user->email);
});

it('redirects guests from the users page', function () {
    $this->get('/users')->assertRedirectToRoute('login');
});

it('opens the user modal from the user name', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($viewer)
        ->get('/users')
        ->assertSee("open-user-edit', { detail: { userId: {$user->id} }", false);
});

it('updates a user from the edit page', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($viewer)
        ->put(route('users.update', $user), [
            'name' => 'Updated',
            'lastname' => 'User',
            'ide' => '987654321',
            'email' => 'updated@example.com',
        ])
        ->assertRedirectToRoute('users.edit', $user);

    expect($user->refresh())
        ->name->toBe('Updated')
        ->email->toBe('updated@example.com');
});

it('creates a user through the livewire modal', function () {
    $viewer = User::factory()->create();

    Livewire::actingAs($viewer)
        ->test(UserModal::class)
        ->call('openCreate')
        ->set('name', 'New')
        ->set('lastname', 'User')
        ->set('ide', '111222333')
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('user-saved');

    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
});

it('validates required fields in the livewire modal', function () {
    $viewer = User::factory()->create();

    Livewire::actingAs($viewer)
        ->test(UserModal::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['name', 'lastname', 'ide', 'email', 'password']);
});

it('autocompletes a new user from the Costa Rica identity registry', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    config()->set('services.gemini.enabled', false);

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=123456789' => Http::response([
            'status' => 'success',
            'data' => [
                'cedula' => '123456789',
                'nombre' => 'JUAN',
                'primer_apellido' => 'PEREZ',
                'segundo_apellido' => 'MORA',
            ],
        ]),
    ]);

    $viewer = User::factory()->create();

    Livewire::actingAs($viewer)
        ->test(UserModal::class)
        ->call('openCreate')
        ->set('ide', '123456789')
        ->call('lookupPerson')
        ->assertSet('name', 'Juan')
        ->assertSet('lastname', 'Perez Mora')
        ->assertSet('ide', '123456789');
});

it('edits a user through the livewire modal', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($viewer)
        ->test(UserModal::class)
        ->call('edit', $user->id)
        ->assertSet('isOpen', true)
        ->assertSet('email', $user->email)
        ->set('name', 'Edited')
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('user-saved');

    expect($user->refresh()->name)->toBe('Edited');
});
