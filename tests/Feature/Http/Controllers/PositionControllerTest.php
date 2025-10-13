<?php

use App\Models\Position;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    $this->actingAs($this->user);
});

test('index page can be rendered', function () {
    $response = $this->get(route('positions.index'));

    $response->assertOk()
        ->assertViewIs('positions.index')
        ->assertSee('Positions')
        ->assertSee('Manage all positions in your organization');
});

test('create page can be rendered', function () {
    $response = $this->get(route('positions.create'));

    $response->assertOk()
        ->assertViewIs('positions.create')
        ->assertSee('Add New Position')
        ->assertSee('Create a new position for your organization');
});

test('edit page can be rendered', function () {
    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Senior Developer',
    ]);

    $response = $this->get(route('positions.edit', $position));

    $response->assertOk()
        ->assertViewIs('positions.edit')
        ->assertSee('Edit Position: Senior Developer')
        ->assertViewHas('position', $position);
});

test('edit page returns 404 for non-existent position', function () {
    $response = $this->get(route('positions.edit', 999));

    $response->assertNotFound();
});

test('unauthenticated users cannot access position pages', function () {
    auth()->logout();

    $this->get(route('positions.index'))->assertRedirect('/login');
    $this->get(route('positions.create'))->assertRedirect('/login');

    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->get(route('positions.edit', $position))->assertRedirect('/login');
});

test('position pages contain livewire components', function () {
    $response = $this->get(route('positions.index'));
    $response->assertSee('wire:click="sort(\'name\')"', false);

    $response = $this->get(route('positions.create'));
    $response->assertSee('wire:submit="save"', false);

    $position = Position::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $response = $this->get(route('positions.edit', $position));
    $response->assertSee('wire:submit="save"', false);
});
