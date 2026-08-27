<?php

use App\Livewire\Users\UserModal;
use App\Livewire\Users\UsersTable;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

it('searches users by name, identification, or email', function () {
    $viewer = User::factory()->create();
    User::factory()->create(['name' => 'Usuario visible', 'ide' => 'ABC123', 'email' => 'visible@example.com']);
    User::factory()->create(['name' => 'Otro usuario', 'ide' => 'XYZ789', 'email' => 'otro@example.com']);

    Livewire::actingAs($viewer)
        ->test(UsersTable::class)
        ->set('search', 'abc123')
        ->assertSee('Usuario visible')
        ->assertDontSee('Otro usuario')
        ->set('search', 'otro@example.com')
        ->assertSee('Otro usuario')
        ->assertDontSee('Usuario visible');
});

it('opens the user modal from the user name', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($viewer)
        ->get('/users')
        ->assertSee("open-user-edit', { detail: { userId: {$user->id} }", false)
        ->assertSee('modalReady', false)
        ->assertSee(__('Cargando información...'));
});

it('refreshes the users table after a user is saved without reloading the page', function () {
    $viewer = User::factory()->create();
    User::factory()->create(['email' => 'before@example.com']);

    $table = Livewire::actingAs($viewer)->test(UsersTable::class);
    $newUser = User::factory()->create(['email' => 'after@example.com']);

    $table
        ->dispatch('user-saved', message: __('Usuario creado correctamente.'))
        ->assertSee($newUser->email);
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
    Storage::fake('public');
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

    $newUser = User::where('email', 'new@example.com')->firstOrFail();

    expect($newUser->avatar_path)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($newUser->avatar_path);
});

it('regenerates the authenticated user avatar from the profile', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar'))
        ->assertRedirectToRoute('profile')
        ->assertSessionHas('success', 'Avatar actualizado correctamente.');

    $avatarPath = $user->refresh()->avatar_path;

    expect($avatarPath)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($avatarPath);
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
