<?php

use App\Models\User;

it('shows the authenticated user profile', function () {
    $user = User::factory()->create([
        'name' => 'Ada',
        'lastname' => 'Lovelace',
        'ide' => '123456789',
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertViewIs('pages.profile')
        ->assertSee('Ada Lovelace')
        ->assertSee('123456789');
});

it('redirects guests from the profile page', function () {
    $this->get('/profile')->assertRedirectToRoute('login');
});

it('links the authenticated user name to the profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('href="/profile"', false)
        ->assertSee($user->name);
});
