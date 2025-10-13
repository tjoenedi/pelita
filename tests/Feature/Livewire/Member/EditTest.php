<?php

use App\Livewire\Member\Edit;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    $this->actingAs($this->user);
    Storage::fake('public');
});

test('can render member edit component', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->assertStatus(200);
});

test('component is initialized with member data', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+1234567890',
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->assertSet('first_name', 'John')
        ->assertSet('last_name', 'Doe')
        ->assertSet('email', 'john@example.com')
        ->assertSet('phone', '+1234567890');
});

test('can update member with new data', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->set('first_name', 'Jane')
        ->set('last_name', 'Smith')
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertRedirect(route('members.index'))
        ->assertSessionHas('success', 'Member updated successfully.');

    $member->refresh();
    expect($member->first_name)->toBe('Jane')
        ->and($member->last_name)->toBe('Smith')
        ->and($member->email)->toBe('jane@example.com');
});

test('validates required fields on update', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->set('first_name', '')
        ->set('last_name', '')
        ->set('email', '')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'email']);
});

test('validates email uniqueness excluding current member', function () {
    $member1 = Member::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'john@example.com',
    ]);
    $member2 = Member::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'jane@example.com',
    ]);

    // Should allow keeping the same email
    Livewire::test(Edit::class, ['member' => $member1])
        ->set('email', 'john@example.com')
        ->call('save')
        ->assertHasNoErrors(['email']);

    // Should not allow using another member's email
    Livewire::test(Edit::class, ['member' => $member1])
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('can upload new profile picture', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $file = UploadedFile::fake()->image('new-profile.jpg');

    Livewire::test(Edit::class, ['member' => $member])
        ->set('new_profile_picture', $file)
        ->call('save')
        ->assertRedirect(route('members.index'));

    $member->refresh();
    expect($member->profile_picture)->not->toBeNull();
    Storage::disk('public')->assertExists($member->profile_picture);
});

test('retains existing profile picture when no new one uploaded', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
        'profile_picture' => 'existing-picture.jpg',
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->set('first_name', 'Updated Name')
        ->call('save');

    $member->refresh();
    expect($member->profile_picture)->toBe('existing-picture.jpg');
});

test('can update all member fields', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->set('first_name', 'Updated')
        ->set('last_name', 'Member')
        ->set('email', 'updated@example.com')
        ->set('phone', '+9876543210')
        ->set('gender', 'F')
        ->set('marital_status', 'Married')
        ->set('birth_date', '1985-12-25')
        ->set('birth_place', 'Los Angeles')
        ->set('address', '456 Oak Ave')
        ->set('city', 'Los Angeles')
        ->set('state_province', 'CA')
        ->set('zip', '90210')
        ->set('country', 'USA')
        ->set('baptism_date', '2005-12-25')
        ->call('save')
        ->assertRedirect(route('members.index'));

    $member->refresh();
    expect($member->first_name)->toBe('Updated')
        ->and($member->last_name)->toBe('Member')
        ->and($member->email)->toBe('updated@example.com')
        ->and($member->phone)->toBe('+9876543210')
        ->and($member->city)->toBe('Los Angeles');
});

test('can cancel and redirect to index', function () {
    $member = Member::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Edit::class, ['member' => $member])
        ->call('cancel')
        ->assertRedirect(route('members.index'));
});

test('validates new profile picture file type', function () {
    $member = Member::factory()->create(['organization_id' => $this->organization->id]);
    $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

    Livewire::test(Edit::class, ['member' => $member])
        ->set('new_profile_picture', $file)
        ->call('save')
        ->assertHasErrors(['new_profile_picture']);
});
