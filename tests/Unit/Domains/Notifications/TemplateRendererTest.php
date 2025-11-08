<?php

use App\Domains\Notifications\DTOs\ReminderContext;
use App\Domains\Notifications\Services\TemplateRenderer;
use App\Models\Event;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Position;

beforeEach(function () {
    $this->renderer = new TemplateRenderer;
});

it('replaces all template parameters correctly', function () {
    $organization = Organization::factory()->create(['name' => 'Test Church']);
    $member = Member::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $organization->id,
    ]);
    $event = Event::factory()->create([
        'name' => 'Sunday Service',
        'date' => '2025-12-25',
        'start_time' => '10:00:00',
        'organization_id' => $organization->id,
    ]);
    $position = Position::factory()->create(['name' => 'Worship Leader']);

    $context = new ReminderContext($member, $event, $position);
    $template = 'Hi {name}, reminder for {event_name} on {event_date} at {event_time}. Position: {position_name}. Org: {organization_name}';

    $result = $this->renderer->render($template, $context);

    expect($result)->toContain('Hi John Doe')
        ->and($result)->toContain('Sunday Service')
        ->and($result)->toContain('December 25, 2025')
        ->and($result)->toContain('10:00 AM')
        ->and($result)->toContain('Worship Leader')
        ->and($result)->toContain('Test Church');
});

it('handles missing optional parameters gracefully', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $event = Event::factory()->create([
        'name' => 'Prayer Meeting',
        'date' => '2025-11-15',
        'start_time' => null,
        'organization_id' => $organization->id,
    ]);

    $context = new ReminderContext($member, $event, null);
    $template = 'Hi {name}, {event_name} on {event_date} at {event_time}. Position: {position_name}';

    $result = $this->renderer->render($template, $context);

    expect($result)->toContain('Hi Jane Smith')
        ->and($result)->toContain('Prayer Meeting')
        ->and($result)->toContain('November 15, 2025')
        ->and($result)->toContain('Position: Member');
});

it('includes unsubscribe link when provided', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create();
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $context = new ReminderContext($member, $event);
    $template = 'Event reminder. {unsubscribe_link}';
    $unsubscribeUrl = 'https://example.com/unsubscribe/token123';

    $result = $this->renderer->render($template, $context, $unsubscribeUrl);

    expect($result)->toContain($unsubscribeUrl);
});

it('handles template without any parameters', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create();
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $context = new ReminderContext($member, $event);
    $template = 'This is a static message without parameters.';

    $result = $this->renderer->render($template, $context);

    expect($result)->toBe('This is a static message without parameters.');
});

it('handles empty template', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create();
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $context = new ReminderContext($member, $event);
    $template = '';

    $result = $this->renderer->render($template, $context);

    expect($result)->toBe('');
});

it('handles multiple occurrences of same parameter', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create(['first_name' => 'Alice', 'last_name' => 'Wonder']);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $context = new ReminderContext($member, $event);
    $template = 'Hello {name}, welcome {name}! Did you know {name} is registered?';

    $result = $this->renderer->render($template, $context);

    expect($result)->toBe('Hello Alice Wonder, welcome Alice Wonder! Did you know Alice Wonder is registered?');
});

it('preserves unmatched placeholders', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create();
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $context = new ReminderContext($member, $event);
    $template = 'Hi {name}, custom field: {custom_field}';

    $result = $this->renderer->render($template, $context);

    expect($result)->toContain('Hi '.$member->first_name.' '.$member->last_name)
        ->and($result)->toContain('{custom_field}');
});

it('handles null values in context by replacing with empty string', function () {
    $organization = Organization::factory()->create();
    $member = Member::factory()->create();
    $event = Event::factory()->create([
        'date' => null,
        'start_time' => null,
        'organization_id' => $organization->id,
    ]);

    $context = new ReminderContext($member, $event);
    $template = 'Event on {event_date} at {event_time}';

    $result = $this->renderer->render($template, $context);

    expect($result)->toBe('Event on  at ');
});
