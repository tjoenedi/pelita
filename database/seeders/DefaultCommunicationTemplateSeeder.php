<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\CommunicationTemplate;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DefaultCommunicationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            // Email Template
            CommunicationTemplate::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'type' => NotificationChannel::Email,
                    'is_default' => true,
                ],
                [
                    'name' => 'General Event Reminder (Email)',
                    'subject' => 'Reminder: {event_name} on {event_date}',
                    'content' => "Hi {name},\n\nThis is a friendly reminder that you are scheduled for {event_name} as {position_name} on {event_date} at {event_time}.\n\nThank you for your service!\n\n{organization_name}",
                    'event_type_id' => null,
                    'append_unsubscribe_footer' => true,
                ]
            );

            // SMS Template
            CommunicationTemplate::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'type' => NotificationChannel::SMS,
                    'is_default' => true,
                ],
                [
                    'name' => 'General Event Reminder (SMS)',
                    'subject' => null,
                    'content' => 'Hi {name}, reminder: {event_name} on {event_date} at {event_time}. Your role: {position_name}. -{organization_name}',
                    'event_type_id' => null,
                    'append_unsubscribe_footer' => false,
                ]
            );
        }

        $this->command->info('Default communication templates created successfully.');
    }
}
