<?php

use App\Models\Member;
use App\Models\Organization;

beforeEach(function () {
    // Create an organization for the tests
    $this->organization = Organization::factory()->create();
});

test('member can be created with valid data', function () {
    $memberData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1234567890',
        'gender' => 'M',
        'marital_status' => 'Single',
        'birth_date' => '1990-01-01',
        'birth_place' => 'New York',
        'address' => '123 Main St',
        'city' => 'New York',
        'state_province' => 'NY',
        'zip' => '10001',
        'country' => 'USA',
        'baptism_date' => '2010-01-01',
        'profile_picture' => 'profile.jpg',
        'organization_id' => $this->organization->id,
    ];

    $member = Member::create($memberData);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->first_name)->toBe('John')
        ->and($member->last_name)->toBe('Doe')
        ->and($member->email)->toBe('john.doe@example.com')
        ->and($member->phone)->toBe('+1234567890')
        ->and($member->gender)->toBe('M')
        ->and($member->marital_status)->toBe('Single')
        ->and($member->birth_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($member->baptism_date)->toBeInstanceOf(Carbon\Carbon::class);
});

test('member can be created with minimal required data', function () {
    $memberData = [
        'organization_id' => $this->organization->id,
    ];

    $member = Member::create($memberData);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->organization_id)->toBe($this->organization->id);
});

test('member fillable attributes are correct', function () {
    $member = new Member;

    $expectedFillable = [
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'state_province',
        'zip',
        'country',
        'birth_date',
        'birth_place',
        'gender',
        'baptism_date',
        'marital_status',
        'email',
        'profile_picture',
        'organization_id',
    ];

    expect($member->getFillable())->toBe($expectedFillable);
});

test('member dates are cast correctly', function () {
    $member = Member::create([
        'birth_date' => '1990-05-15',
        'baptism_date' => '2010-06-20',
        'organization_id' => $this->organization->id,
    ]);

    expect($member->birth_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($member->baptism_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($member->birth_date->format('Y-m-d'))->toBe('1990-05-15')
        ->and($member->baptism_date->format('Y-m-d'))->toBe('2010-06-20');
});

test('member can have null optional fields', function () {
    $member = Member::create([
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'phone' => null,
        'birth_date' => null,
        'baptism_date' => null,
        'profile_picture' => null,
        'organization_id' => $this->organization->id,
    ]);

    expect($member->first_name)->toBeNull()
        ->and($member->last_name)->toBeNull()
        ->and($member->email)->toBeNull()
        ->and($member->phone)->toBeNull()
        ->and($member->birth_date)->toBeNull()
        ->and($member->baptism_date)->toBeNull()
        ->and($member->profile_picture)->toBeNull();
});
