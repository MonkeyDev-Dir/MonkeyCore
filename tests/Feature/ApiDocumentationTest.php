<?php

use App\Models\User;

it('shows exchange rate API documentation to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('api-docs.exchange-rates'))
        ->assertOk()
        ->assertViewIs('pages.api-documentation.exchange-rates')
        ->assertSee(__('Tipo de cambio'))
        ->assertSee('/api/v1/exchange-rates/latest')
        ->assertSee('/api/v1/exchange-rates/{date}')
        ->assertSee(__('Authorization: Bearer {token}'))
        ->assertSee(__('120 solicitudes por minuto por token.'))
        ->assertSee('401')
        ->assertSee('429');
});

it('redirects guests from exchange rate API documentation', function () {
    $this->get(route('api-docs.exchange-rates'))
        ->assertRedirectToRoute('login');
});

it('protects the generated OpenAPI specification', function () {
    $this->get(route('scramble.docs.document'))
        ->assertRedirectToRoute('login');
});

it('generates the OpenAPI specification from the versioned API routes', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('scramble.docs.document'))
        ->assertOk()
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonStructure([
            'paths',
            'components' => ['securitySchemes'],
        ]);
});
