<?php

use App\Models\User;

it('renders the template login page for guests', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertViewIs('auth.login')
        ->assertSee('Iniciar sesión');
});

it('redirects guests away from the protected home page', function () {
    $response = $this->get('/');

    $response->assertRedirectToRoute('login');
});

it('authenticates a user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirectToRoute('home');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid login credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login')
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs an authenticated user out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
