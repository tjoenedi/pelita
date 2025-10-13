<?php

use App\Livewire\Position\Create;
use App\Models\Position;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    $this->actingAs($this->user);
});

test('can render position create component', function () {
    Livewire::test(Create::class)
        ->assertStatus(200);
});

test('can create position with required fields', function () {
    Livewire::test(Create::class)
        ->set('name', 'Senior Developer')
        ->call('save')
        ->assertRedirect(route('positions.index'))
        ->assertSessionHas('success', 'Position created successfully.');

    $this->assertDatabaseHas('positions', [
        'name' => 'Senior Developer',
        'organization_id' => $this->organization->id,
    ]);
});

test('can create position with all fields', function () {
    Livewire::test(Create::class)
        ->set('name', 'Project Manager')
        ->set('description', 'Lead project teams and coordinate deliverables')
        ->call('save')
        ->assertRedirect(route('positions.index'));

    $this->assertDatabaseHas('positions', [
        'name' => 'Project Manager',
        'description' => 'Lead project teams and coordinate deliverables',
        'organization_id' => $this->organization->id,
    ]);
});

test('validates required fields', function () {
    Livewire::test(Create::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validates name is string and max length', function () {
    Livewire::test(Create::class)
        ->set('name', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validates description max length', function () {
    Livewire::test(Create::class)
        ->set('name', 'Test Position')
        ->set('description', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['description']);
});

test('validates organization_id exists', function () {
    Livewire::test(Create::class)
        ->set('name', 'Test Position')
        ->set('organization_id', 999)
        ->call('save')
        ->assertHasErrors(['organization_id']);
});

test('can create position without description', function () {
    Livewire::test(Create::class)
        ->set('name', 'Developer')
        ->set('description', '')
        ->call('save')
        ->assertRedirect(route('positions.index'));

    $position = Position::where('name', 'Developer')->first();
    expect($position->description)->toBeNull();
});

test('can cancel and redirect to index', function () {
    Livewire::test(Create::class)
        ->call('cancel')
        ->assertRedirect(route('positions.index'));
});

test('component initializes with default organization_id', function () {
    Livewire::test(Create::class)
        ->assertSet('organization_id', $this->organization->id);
});

test('form fields are properly cleared on component mount', function () {
    Livewire::test(Create::class)
        ->assertSet('name', '')
        ->assertSet('description', '');
});
