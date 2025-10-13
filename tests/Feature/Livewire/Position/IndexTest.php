<?php

use App\Livewire\Position\Index;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create();
    $this->user->organizations()->attach($this->organization);
    $this->actingAs($this->user);
});

it('can render position index page without errors', function () {
    $response = $this->get(route('positions.index'));

    $response->assertStatus(200);
    $response->assertSeeLivewire(Index::class);
});

it('displays positions in the table', function () {
    $position1 = Position::factory()->create([
        'name' => 'Pastor',
        'description' => 'Lead pastor role',
        'organization_id' => $this->organization->id,
    ]);

    $position2 = Position::factory()->create([
        'name' => 'Worship Leader',
        'description' => 'Leads worship services',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->assertSee('Pastor')
        ->assertSee('Lead pastor role')
        ->assertSee('Worship Leader')
        ->assertSee('Leads worship services');
});

it('can search positions by name', function () {
    Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);

    Position::factory()->create([
        'name' => 'Worship Leader',
        'organization_id' => $this->organization->id,
    ]);

    Position::factory()->create([
        'name' => 'Youth Pastor',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Pastor')
        ->assertSee('Pastor')
        ->assertSee('Youth Pastor')
        ->assertDontSee('Worship Leader');
});

it('can search positions by description', function () {
    Position::factory()->create([
        'name' => 'Pastor',
        'description' => 'Leads the congregation',
        'organization_id' => $this->organization->id,
    ]);

    Position::factory()->create([
        'name' => 'Worship Leader',
        'description' => 'Manages worship team',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->set('search', 'congregation')
        ->assertSee('Pastor')
        ->assertSee('Leads the congregation')
        ->assertDontSee('Worship Leader');
});

it('can sort positions by name', function () {
    Position::factory()->create([
        'name' => 'Zebra Keeper',
        'organization_id' => $this->organization->id,
    ]);

    Position::factory()->create([
        'name' => 'Alpha Leader',
        'organization_id' => $this->organization->id,
    ]);

    Position::factory()->create([
        'name' => 'Beta Tester',
        'organization_id' => $this->organization->id,
    ]);

    // Default sort is name ascending, so verify initial state
    Livewire::test(Index::class)
        ->assertSeeInOrder(['Alpha Leader', 'Beta Tester', 'Zebra Keeper'])
        ->call('sort', 'name') // Toggle to descending
        ->assertSeeInOrder(['Zebra Keeper', 'Beta Tester', 'Alpha Leader']);
});

it('can delete a position', function () {
    $position = Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);

    $this->assertDatabaseHas('positions', ['id' => $position->id]);

    Livewire::test(Index::class)
        ->call('delete', $position->id)
        ->assertDispatched('position-deleted');

    $this->assertDatabaseMissing('positions', ['id' => $position->id]);
});

it('displays empty state when no positions exist', function () {
    Livewire::test(Index::class)
        ->assertSee('No positions found')
        ->assertSee('Add your first position');
});

it('shows edit and delete buttons for each position', function () {
    $position = Position::factory()->create([
        'name' => 'Pastor',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Index::class)
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->assertSee(route('positions.edit', $position));
});

it('only shows positions from user organization', function () {
    $otherOrg = Organization::factory()->create();

    $myPosition = Position::factory()->create([
        'name' => 'My Pastor',
        'organization_id' => $this->organization->id,
    ]);

    $otherPosition = Position::factory()->create([
        'name' => 'Other Pastor',
        'organization_id' => $otherOrg->id,
    ]);

    Livewire::test(Index::class)
        ->assertSee('My Pastor')
        ->assertDontSee('Other Pastor');
});
