<?php

use App\Livewire\Member\Index;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create();
    $this->user->organizations()->attach($this->organization);
    $this->actingAs($this->user);
});

it('can render member index page without errors', function () {
    $response = $this->get(route('members.index'));

    $response->assertStatus(200);
    $response->assertSeeLivewire(Index::class);
});

it('displays members in the table', function () {
    $member1 = Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '123-456-7890',
        'organization_id' => $this->organization->id
    ]);

    $member2 = Member::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'phone' => '098-765-4321',
        'organization_id' => $this->organization->id
    ]);

    Livewire::test(Index::class)
        ->assertSee('John')
        ->assertSee('Doe')
        ->assertSee('john@example.com')
        ->assertSee('123-456-7890')
        ->assertSee('Jane')
        ->assertSee('Smith')
        ->assertSee('jane@example.com')
        ->assertSee('098-765-4321');
});

it('can search members by name', function () {
    Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Bob',
        'last_name' => 'Johnson',
        'organization_id' => $this->organization->id
    ]);

    Livewire::test(Index::class)
        ->set('search', 'John')
        ->assertSee('John')
        ->assertSee('Johnson')
        ->assertDontSee('Jane')
        ->assertDontSee('Smith');
});

it('can search members by email', function () {
    Member::factory()->create([
        'first_name' => 'John',
        'email' => 'john@example.com',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Jane',
        'email' => 'jane@test.com',
        'organization_id' => $this->organization->id
    ]);

    Livewire::test(Index::class)
        ->set('search', 'example.com')
        ->assertSee('john@example.com')
        ->assertDontSee('jane@test.com');
});

it('can sort members by first name', function () {
    Member::factory()->create([
        'first_name' => 'Charlie',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Alice',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Bob',
        'organization_id' => $this->organization->id
    ]);

    // Default sort is first_name ascending, so just verify initial state
    Livewire::test(Index::class)
        ->assertSeeInOrder(['Alice', 'Bob', 'Charlie'])
        ->call('sort', 'first_name') // Toggle to descending
        ->assertSeeInOrder(['Charlie', 'Bob', 'Alice']);
});

it('can sort members by last name', function () {
    Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Zebra',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Alpha',
        'organization_id' => $this->organization->id
    ]);

    Member::factory()->create([
        'first_name' => 'Bob',
        'last_name' => 'Beta',
        'organization_id' => $this->organization->id
    ]);

    Livewire::test(Index::class)
        ->call('sort', 'last_name')
        ->assertSeeInOrder(['Alpha', 'Beta', 'Zebra']);
});

it('can delete a member', function () {
    $member = Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $this->organization->id
    ]);

    $this->assertDatabaseHas('members', ['id' => $member->id]);

    Livewire::test(Index::class)
        ->call('delete', $member->id)
        ->assertDispatched('member-deleted');

    $this->assertDatabaseMissing('members', ['id' => $member->id]);
});

it('displays empty state when no members exist', function () {
    Livewire::test(Index::class)
        ->assertSee('No members found')
        ->assertSee('Add your first member');
});

it('shows edit and delete buttons for each member', function () {
    $member = Member::factory()->create([
        'first_name' => 'John',
        'organization_id' => $this->organization->id
    ]);

    Livewire::test(Index::class)
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->assertSee(route('members.edit', $member));
});

it('only shows members from user organization', function () {
    $otherOrg = Organization::factory()->create();

    $myMember = Member::factory()->create([
        'first_name' => 'John',
        'organization_id' => $this->organization->id
    ]);

    $otherMember = Member::factory()->create([
        'first_name' => 'Jane',
        'organization_id' => $otherOrg->id
    ]);

    Livewire::test(Index::class)
        ->assertSee('John')
        ->assertDontSee('Jane');
});