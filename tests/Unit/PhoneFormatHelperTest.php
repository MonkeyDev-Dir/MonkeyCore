<?php

use App\Helpers\PhoneFormatHelper;

it('normalizes phone numbers according to their digit count', function (string $phone, ?string $expected): void {
    expect(PhoneFormatHelper::normalize($phone))->toBe($expected);
})->with([
    ['123456', '123-456'],
    ['123-4567', '123-45-67'],
    ['8562-6443', '8562 6443'],
    ['+506 1234 5678', '50612345678'],
    ['1234567890123456', '1234567890123456'],
    ['12345', null],
    ['12345678901234567', null],
]);

it('returns null for an absent phone number', function (): void {
    expect(PhoneFormatHelper::normalize(null))->toBeNull();
});
