<?php

use App\Enums\Country;

test('country enum has correct cases', function () {
    $cases = Country::cases();

    expect($cases)->toHaveCount(2);
    expect($cases[0])->toBe(Country::US);
    expect($cases[1])->toBe(Country::ID);
});

test('country enum has correct values', function () {
    expect(Country::US->value)->toBe('United States');
    expect(Country::ID->value)->toBe('Indonesia');
});

test('country enum has correct names', function () {
    expect(Country::US->name)->toBe('US');
    expect(Country::ID->name)->toBe('ID');
});

test('country enum can be created from string', function () {
    expect(Country::from('United States'))->toBe(Country::US);
    expect(Country::from('Indonesia'))->toBe(Country::ID);
});

test('country enum throws exception for invalid value', function () {
    expect(fn () => Country::from('Invalid Country'))
        ->toThrow(ValueError::class);
});

test('country enum tryFrom returns null for invalid value', function () {
    expect(Country::tryFrom('Invalid Country'))->toBeNull();
    expect(Country::tryFrom('United States'))->toBe(Country::US);
    expect(Country::tryFrom('Indonesia'))->toBe(Country::ID);
});

test('getCountries returns correct array', function () {
    $countries = Country::getCountries();

    expect($countries)->toBeArray();
    expect($countries)->toHaveCount(2);
    expect($countries)->toBe([
        'US' => 'United States',
        'ID' => 'Indonesia',
    ]);
});

test('getCountries array has correct keys and values', function () {
    $countries = Country::getCountries();

    expect($countries)->toHaveKey('US', 'United States');
    expect($countries)->toHaveKey('ID', 'Indonesia');
});

test('country enum is string backed', function () {
    expect(Country::US)->toBeInstanceOf(Country::class);
    expect(Country::US->value)->toBe('United States');
    expect(Country::ID->value)->toBe('Indonesia');
});

test('country enum cases are immutable', function () {
    $us1 = Country::US;
    $us2 = Country::US;

    expect($us1)->toBe($us2);
    expect($us1 === $us2)->toBeTrue();
});

test('country enum can be used in match expressions', function () {
    $getCountryCode = function (Country $country) {
        return match ($country) {
            Country::US => 'US',
            Country::ID => 'ID',
        };
    };

    expect($getCountryCode(Country::US))->toBe('US');
    expect($getCountryCode(Country::ID))->toBe('ID');
});

test('country enum can be serialized', function () {
    $serialized = serialize(Country::US);
    $unserialized = unserialize($serialized);

    expect($unserialized)->toBe(Country::US);
    expect($unserialized->value)->toBe('United States');
});

test('country enum can be used in arrays', function () {
    $countries = [Country::US, Country::ID];

    expect($countries)->toContain(Country::US);
    expect($countries)->toContain(Country::ID);
    expect(in_array(Country::US, $countries))->toBeTrue();
});

test('country enum values are unique', function () {
    $values = array_map(fn ($case) => $case->value, Country::cases());
    $uniqueValues = array_unique($values);

    expect($values)->toBe($uniqueValues);
});

test('country enum names are unique', function () {
    $names = array_map(fn ($case) => $case->name, Country::cases());
    $uniqueNames = array_unique($names);

    expect($names)->toBe($uniqueNames);
});
