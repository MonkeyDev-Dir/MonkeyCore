<?php

use App\Services\GeminiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('corrects a compound name using Gemini and returns title case', function () {
    config()->set('services.gemini.enabled', true);
    config()->set('services.gemini.api_key', 'test-api-key');
    config()->set('services.gemini.model', 'gemini-test');

    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => '{"name":"Gilberth Andrés"}',
                    ]],
                ],
            ]],
        ]),
    ]);

    $result = app(GeminiService::class)->corregirNombreApellido('GILBERTH ANDRES');

    expect($result)->toBe('Gilberth Andrés');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent?key=test-api-key'
            && $request->method() === 'POST';
    });
});

it('formats compound names when Gemini is disabled', function () {
    config()->set('services.gemini.enabled', false);

    Http::preventStrayRequests();

    expect(app(GeminiService::class)->corregirNombreApellido('  maria jose  '))
        ->toBe('Maria Jose');
});

it('rejects an invalid Gemini response', function () {
    config()->set('services.gemini.enabled', true);
    config()->set('services.gemini.api_key', 'test-api-key');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"wrong":"value"}']]],
            ]],
        ]),
    ]);

    expect(fn () => app(GeminiService::class)->corregirNombreApellido('maria jose'))
        ->toThrow(UnexpectedValueException::class);
});
