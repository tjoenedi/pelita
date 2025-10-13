<?php

use App\Models\Organization;
use App\Models\Position;

beforeEach(function () {
    // Create an organization for the tests
    $this->organization = Organization::factory()->create();
});

test('position can be created with valid data', function () {
    $positionData = [
        'name' => 'Senior Developer',
        'description' => 'Lead development team and mentor junior developers',
        'organization_id' => $this->organization->id,
    ];

    $position = Position::create($positionData);

    expect($position)->toBeInstanceOf(Position::class)
        ->and($position->name)->toBe('Senior Developer')
        ->and($position->description)->toBe('Lead development team and mentor junior developers')
        ->and($position->organization_id)->toBe($this->organization->id);
});

test('position can be created with minimal required data', function () {
    $positionData = [
        'name' => 'Manager',
        'organization_id' => $this->organization->id,
    ];

    $position = Position::create($positionData);

    expect($position)->toBeInstanceOf(Position::class)
        ->and($position->name)->toBe('Manager')
        ->and($position->description)->toBeNull()
        ->and($position->organization_id)->toBe($this->organization->id);
});

test('position fillable attributes are correct', function () {
    $position = new Position;

    $expectedFillable = [
        'name',
        'description',
        'organization_id',
    ];

    expect($position->getFillable())->toBe($expectedFillable);
});

test('position can have null description', function () {
    $position = Position::create([
        'name' => 'Test Position',
        'description' => null,
        'organization_id' => $this->organization->id,
    ]);

    expect($position->description)->toBeNull()
        ->and($position->name)->toBe('Test Position');
});

test('position timestamps are automatically managed', function () {
    $position = Position::create([
        'name' => 'Test Position',
        'organization_id' => $this->organization->id,
    ]);

    expect($position->created_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($position->updated_at)->toBeInstanceOf(Carbon\Carbon::class);
});
