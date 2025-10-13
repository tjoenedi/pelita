<?php

use App\Livewire\Member\Create;
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

test('can render member create component', function () {
    Livewire::test(Create::class)
        ->assertStatus(200);
});

test('can create member with required fields', function () {
    Livewire::test(Create::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john.doe@example.com')
        ->call('save')
        ->assertRedirect(route('members.index'))
        ->assertSessionHas('success', 'Member created successfully.');

    $this->assertDatabaseHas('members', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
    ]);
});

test('can create member with all fields', function () {
    Livewire::test(Create::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Smith')
        ->set('email', 'jane.smith@example.com')
        ->set('phone', '+1234567890')
        ->set('gender', 'F')
        ->set('marital_status', 'Single')
        ->set('birth_date', '1990-01-01')
        ->set('birth_place', 'New York')
        ->set('address', '123 Main St')
        ->set('city', 'New York')
        ->set('state_province', 'NY')
        ->set('zip', '10001')
        ->set('country', 'USA')
        ->set('baptism_date', '2010-01-01')
        ->call('save')
        ->assertRedirect(route('members.index'));

    $this->assertDatabaseHas('members', [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.com',
        'phone' => '+1234567890',
        'gender' => 'F',
        'marital_status' => 'Single',
        'birth_place' => 'New York',
        'city' => 'New York',
    ]);
});

test('validates required fields', function () {
    Livewire::test(Create::class)
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'email']);
});

test('validates email format', function () {
    Livewire::test(Create::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('validates unique email', function () {
    Member::factory()->create([
        'email' => 'existing@example.com',
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(Create::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'existing@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('can upload profile picture', function () {
    $file = UploadedFile::fake()->image('profile.jpg');

    Livewire::test(Create::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('profile_picture', $file)
        ->call('save')
        ->assertRedirect(route('members.index'));

    $member = Member::where('email', 'john@example.com')->first();
    expect($member->profile_picture)->not->toBeNull();
    Storage::disk('public')->assertExists($member->profile_picture);
});

test('validates profile picture file type', function () {
    $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

    Livewire::test(Create::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('profile_picture', $file)
        ->call('save')
        ->assertHasErrors(['profile_picture']);
});

test('can cancel and redirect to index', function () {
    Livewire::test(Create::class)
        ->call('cancel')
        ->assertRedirect(route('members.index'));
});

test('validates date fields format', function () {
    Livewire::test(Create::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('birth_date', 'invalid-date')
        ->set('baptism_date', 'invalid-date')
        ->call('save')
        ->assertHasErrors(['birth_date', 'baptism_date']);
});
