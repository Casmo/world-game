<?php

use App\Support\H3;

test('latLngToCell returns the known H3 index for a reference point', function () {
    $h3 = new H3;

    expect($h3->latLngToCell(37.775938728915946, -122.41795063018799, 9))
        ->toBe('8928308280fffff');
});

test('cellToLatLng returns the center coordinates of a cell', function () {
    $h3 = new H3;

    [$lat, $lng] = $h3->cellToLatLng('8928308280fffff');

    expect($lat)->toEqualWithDelta(37.77670234943567, 1e-9)
        ->and($lng)->toEqualWithDelta(-122.41845932318311, 1e-9);
});

test('disk returns the origin plus every cell within k rings', function () {
    $h3 = new H3;

    $disk = $h3->disk('8928308280fffff', 1);
    sort($disk);

    expect($disk)->toBe([
        '89283082803ffff',
        '89283082807ffff',
        '8928308280bffff',
        '8928308280fffff',
        '8928308283bffff',
        '89283082873ffff',
        '89283082877ffff',
    ]);
});

test('neighbors returns the six adjacent cells, excluding the origin', function () {
    $h3 = new H3;

    $neighbors = $h3->neighbors('8928308280fffff');

    expect($neighbors)->toHaveCount(6)
        ->and($neighbors)->not->toContain('8928308280fffff');
});

test('isValidCell distinguishes a valid cell from garbage', function () {
    $h3 = new H3;

    expect($h3->isValidCell('8928308280fffff'))->toBeTrue()
        ->and($h3->isValidCell('1234'))->toBeFalse();
});

test('latLngToCell holds at another resolution (guards against H3 version drift)', function () {
    $h3 = new H3;

    expect($h3->latLngToCell(40.689421843699, -74.044431399909, 10))
        ->toBe('8a2a1072b59ffff');
});
