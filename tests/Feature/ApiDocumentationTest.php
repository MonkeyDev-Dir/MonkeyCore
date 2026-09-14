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

it('shows civil registry API documentation to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('api-docs.civil-registry'))
        ->assertOk()
        ->assertViewIs('pages.api-documentation.civil-registry')
        ->assertSee(__('Consulta civil'))
        ->assertSee('/api/v1/civil-registry/people')
        ->assertSee('/api/v1/civil-registry/legal-entities')
        ->assertSee('POST')
        ->assertSee(__('Número de cédula física de 9 dígitos.'))
        ->assertSee(__('Número de cédula jurídica de 10 dígitos.'))
        ->assertSee(__('Los nombres y apellidos se corrigen con Gemini hasta un máximo de tres intentos.'))
        ->assertSee(__('Si Gemini no responde correctamente, se aplica formato de nombre propio como respaldo.'))
        ->assertSee(__('La respuesta no incluye información de padre ni madre.'))
        ->assertSee('404');
});

it('redirects guests from civil registry API documentation', function () {
    $this->get(route('api-docs.civil-registry'))
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
