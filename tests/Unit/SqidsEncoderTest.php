<?php

use App\Support\SqidsEncoder;

it('encodes ids with the locked lowercase alphabet and min length 3', function () {
    $encoder = new SqidsEncoder(
        alphabet: 'yn1g3rvoejitkqum0fdbc5x78lz6hs92p4aw',
        minLength: 3,
    );

    expect($encoder->encode(12))->toBe('1vx')
        ->and(strlen($encoder->encode(1)))->toBeGreaterThanOrEqual(3)
        ->and($encoder->decode('1vx'))->toBe([12]);
});

it('is stable for the same id and config', function () {
    $encoder = new SqidsEncoder(
        alphabet: 'yn1g3rvoejitkqum0fdbc5x78lz6hs92p4aw',
        minLength: 3,
    );

    expect($encoder->encode(100))->toBe($encoder->encode(100));
});
