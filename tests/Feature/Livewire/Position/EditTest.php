<?php

use App\Livewire\Position\Edit;
use App\Models\Position;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    $this->actingAs($this->user);
});

test('can render position edit component', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'organization_id' => $this->organization->id]);

    Livewire::test(Edit::class, ['position' => $position])
        ->assertStatus(200);
});

test('component is initialized with position data', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Senior Developer',
        'description' => 'Lead development team',
        'organization_id' => 1,
    ]);

    Livewire::test(Edit::class, ['position' => $position])
        ->assertSet('name', 'Senior Developer')
        ->assertSet('description', 'Lead development team')
        ->assertSet('organization_id', 1);
});

test('can update position with new data', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Developer',
        'description' => 'Write code',
        'organization_id' => 1,
    ]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('name', 'Senior Developer')
        ->set('description', 'Lead development team and mentor juniors')
        ->call('save')
        ->assertRedirect(route('positions.index'))
        ->assertSessionHas('success', 'Position updated successfully.');

    $position->refresh();
    expect($position->name)->toBe('Senior Developer')
        ->and($position->description)->toBe('Lead development team and mentor juniors');
});

test('validates required fields on update', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'organization_id' => $this->organization->id]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('name', '')
        ->set('organization_id', '')
        ->call('save')
        ->assertHasErrors(['name', 'organization_id']);
});

test('validates name max length on update', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'organization_id' => $this->organization->id]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('name', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validates description max length on update', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'organization_id' => $this->organization->id]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('description', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['description']);
});

test('validates organization_id exists on update', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'organization_id' => $this->organization->id]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('organization_id', 999)
        ->call('save')
        ->assertHasErrors(['organization_id']);
});

test('can update position without description', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
        'description' => 'Original description',
    ]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('description', '')
        ->call('save')
        ->assertRedirect(route('positions.index'));

    $position->refresh();
    expect($position->description)->toBeNull();
});

test('can cancel and redirect to index', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'organization_id' => $this->organization->id]);

    Livewire::test(Edit::class, ['position' => $position])
        ->call('cancel')
        ->assertRedirect(route('positions.index'));
});

test('position model is properly passed to component', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id, 'name' => 'Test Position']);

    $component = Livewire::test(Edit::class, ['position' => $position]);

    expect($component->get('position')->name)->toBe('Test Position');
});

test('can update only name field', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Old Name',
        'description' => 'Same description',
    ]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('name', 'New Name')
        ->call('save');

    $position->refresh();
    expect($position->name)->toBe('New Name')
        ->and($position->description)->toBe('Same description');
});

test('can update only description field', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Same Name',
        'description' => 'Old description',
    ]);

    Livewire::test(Edit::class, ['position' => $position])
        ->set('description', 'New description')
        ->call('save');

    $position->refresh();
    expect($position->name)->toBe('Same Name')
        ->and($position->description)->toBe('New description');
});
