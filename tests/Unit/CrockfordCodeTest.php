<?php

use App\Support\CrockfordCode;

test('generated copy and member codes match the expected shape', function () {
    $body = CrockfordCode::generate();

    expect(strlen($body))->toBe(8)
        ->and(CrockfordCode::isValidBody($body))->toBeTrue();
});

test('prefixed codes validate', function () {
    $copy = CrockfordCode::withPrefix('BS');
    $loan = CrockfordCode::withPrefix('LN');

    expect(CrockfordCode::isValid($copy, 'BS'))->toBeTrue()
        ->and(CrockfordCode::isValid($loan, 'LN'))->toBeTrue();
});

test('a mutated character invalidates the check digit', function () {
    $code = CrockfordCode::generate();

    $mutated = $code[0] === '0' ? '1'.$code[1].substr($code, 2) : '0'.substr($code, 1);

    expect(CrockfordCode::isValidBody($code))->toBeTrue()
        ->and(CrockfordCode::isValidBody($mutated))->toBeFalse();
});

test('excluded Crockford letters are rejected', function () {
    expect(CrockfordCode::isValidBody('BS-4F7K2Q91'))->toBeFalse()
        ->and(fn () => CrockfordCode::checkDigit('4F7K2Q9I'))->toThrow(InvalidArgumentException::class);
});

test('wrong prefix is rejected', function () {
    $code = CrockfordCode::withPrefix('BS');
    $body = CrockfordCode::generate();

    expect(CrockfordCode::isValid($code, 'LN'))->toBeFalse()
        ->and(CrockfordCode::isValid($body))->toBeTrue();
});
