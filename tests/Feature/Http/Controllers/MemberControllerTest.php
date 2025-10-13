<?php

use App\Models\Member;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    $this->actingAs($this->user);
});

test('index page can be rendered', function () {
    $response = $this->get(route('members.index'));

    $response->assertOk()
        ->assertViewIs('members.index')
        ->assertSee('Members')
        ->assertSee('Manage all members in your organization');
});

test('create page can be rendered', function () {
    $response = $this->get(route('members.create'));

    $response->assertOk()
        ->assertViewIs('members.create')
        ->assertSee('Add New Member')
        ->assertSee('Create a new member profile');
});

test('edit page can be rendered', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response = $this->get(route('members.edit', $member));

    $response->assertOk()
        ->assertViewIs('members.edit')
        ->assertSee('Edit Member: John Doe')
        ->assertViewHas('member', $member);
});

test('edit page returns 404 for non-existent member', function () {
    $response = $this->get(route('members.edit', 999));

    $response->assertNotFound();
});

test('unauthenticated users cannot access member pages', function () {
    auth()->logout();

    $this->get(route('members.index'))->assertRedirect('/login');
    $this->get(route('members.create'))->assertRedirect('/login');

    $member = Member::factory()->create([
        'organization_id' => $this->organization->id]);
    $this->get(route('members.edit', $member))->assertRedirect('/login');
});

test('member pages contain livewire components', function () {
    $response = $this->get(route('members.index'));
    $response->assertSee('wire:click="sort(\'first_name\')"', false);

    $response = $this->get(route('members.create'));
    $response->assertSee('wire:submit="save"', false);

    $member = Member::factory()->create(['organization_id' => $this->organization->id]);
    $response = $this->get(route('members.edit', $member));
    $response->assertSee('wire:submit="save"', false);
});
