<?php

use App\Livewire\Profile\AvatarSelector;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows the authenticated user profile', function () {
    Storage::fake('public');
    $user = User::factory()->create([
        'avatar_path' => 'avatars/profile.svg',
        'name' => 'Ada',
        'lastname' => 'Lovelace',
        'ide' => '123456789',
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertViewIs('pages.profile')
        ->assertSee('Ada Lovelace')
        ->assertSee('123456789')
        ->assertSee(Storage::disk('public')->url('avatars/profile.svg'), false);
});

it('redirects guests from the profile page', function () {
    $this->get('/profile')->assertRedirectToRoute('login');
});

it('lets the authenticated user choose an avatar from generated options', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(AvatarSelector::class)->call('open');
    $options = $component->get('options');

    $component
        ->assertSet('isOpen', true)
        ->assertCount('options', 20)
        ->assertSet('options.0', 'avatars/options/robot-new-1.svg')
        ->assertSee('storage/avatars/', false)
        ->call('close')
        ->call('open')
        ->assertSet('options', $options)
        ->call('select', $options[2])
        ->call('save')
        ->assertRedirect(route('profile'))
        ->assertSessionHas('toast', [
            'icon' => 'success',
            'title' => 'Avatar actualizado correctamente.',
        ]);

    Storage::disk('public')->assertExists($user->refresh()->avatar_path);
});

it('links the authenticated user name to the profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('href="/profile"', false)
        ->assertSee($user->name);
});
