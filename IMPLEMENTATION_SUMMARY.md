# Event Reminder Notification System - Implementation Summary

## Overview
A comprehensive event reminder notification system for the Pelita church management application. This system sends automated SMS and email reminders to members scheduled for events.

## ⚠️ IMPORTANT: PHP Version Issue
**The system currently runs PHP 8.1.33, but the project requires PHP 8.3+**. This prevented running artisan commands during implementation. All code has been manually created following Laravel conventions. Before running migrations or tests, you must upgrade to PHP 8.3.

---

## COMPLETED COMPONENTS

### 1. Database Schema (✅ Complete)

**Migrations Created:**
- `/Users/freddy/Works/pelita/database/migrations/2025_10_26_000001_add_reminder_settings_to_organizations_table.php`
- `/Users/freddy/Works/pelita/database/migrations/2025_10_26_000002_create_communication_templates_table.php`
- `/Users/freddy/Works/pelita/database/migrations/2025_10_26_000003_add_reminder_settings_to_event_types_table.php`
- `/Users/freddy/Works/pelita/database/migrations/2025_10_26_000004_add_reminder_settings_to_events_table.php`
- `/Users/freddy/Works/pelita/database/migrations/2025_10_26_000005_create_member_communication_preferences_table.php`
- `/Users/freddy/Works/pelita/database/migrations/2025_10_26_000006_create_reminder_logs_table.php`

**New Tables:**
- `communication_templates` - Email/SMS templates with placeholders
- `member_communication_preferences` - Granular unsubscribe control
- `reminder_logs` - Complete audit trail of all reminders

**Updated Tables:**
- `organizations` - Added: reminder_enabled, reminder_days_before, reminder_time, timezone
- `event_types` - Added: reminder_enabled, reminder_schedules (JSON), email_template_id, sms_template_id
- `events` - Added: reminder_mode (auto/manual/disabled), reminder_override (JSON), timezone

### 2. Enums (✅ Complete)

- `/Users/freddy/Works/pelita/app/Enums/NotificationChannel.php` - Email, SMS, All
- `/Users/freddy/Works/pelita/app/Enums/ReminderStatus.php` - Scheduled, Sent, Failed, Cancelled
- `/Users/freddy/Works/pelita/app/Enums/ReminderMode.php` - Auto, Manual, Disabled

### 3. Models (✅ Complete)

**New Models:**
- `/Users/freddy/Works/pelita/app/Models/CommunicationTemplate.php`
- `/Users/freddy/Works/pelita/app/Models/MemberCommunicationPreference.php` (auto-generates unsubscribe tokens)
- `/Users/freddy/Works/pelita/app/Models/ReminderLog.php`

**Updated Models with Relationships:**
- `Organization` - Added: communicationTemplates(), memberCommunicationPreferences()
- `EventType` - Added: communicationTemplates(), emailTemplate(), smsTemplate()
- `Event` - Added: reminderLogs(), updated casts and fillable
- `Member` - Added: communicationPreferences(), reminderLogs()

### 4. Domain-Driven Design Structure (✅ Complete)

Following the existing SMS domain pattern:

**Contracts:**
- `/Users/freddy/Works/pelita/app/Domains/Notifications/Contracts/EmailProviderInterface.php`
- `/Users/freddy/Works/pelita/app/Domains/Notifications/Contracts/TemplateRendererInterface.php`

**Services:**
- `/Users/freddy/Works/pelita/app/Domains/Notifications/Services/NotificationService.php`
  - Main orchestrator (facade pattern)
  - Checks member preferences before sending
  - Delegates to email/SMS providers
  - Logs all sends

- `/Users/freddy/Works/pelita/app/Domains/Notifications/Services/ReminderScheduler.php`
  - Calculates reminder times with timezone support
  - Queues jobs at correct UTC times
  - Handles event rescheduling
  - Supports manual triggering

- `/Users/freddy/Works/pelita/app/Domains/Notifications/Services/TemplateRenderer.php`
  - Replaces template placeholders
  - Generates unsubscribe URLs

**Providers:**
- `/Users/freddy/Works/pelita/app/Domains/Notifications/Providers/LaravelMailProvider.php`
  - Implements EmailProviderInterface
  - Uses Laravel Mail facade
  - Error handling and logging

**DTOs:**
- `/Users/freddy/Works/pelita/app/Domains/Notifications/DTOs/ReminderContext.php`
  - Encapsulates member, event, position data
  - Provides template parameters

### 5. Queue Jobs (✅ Complete)

- `/Users/freddy/Works/pelita/app/Domains/Notifications/Jobs/ScheduleEventReminders.php`
  - Dispatched when event created/updated
  - Finds all scheduled members
  - Queues individual reminder jobs

- `/Users/freddy/Works/pelita/app/Domains/Notifications/Jobs/SendEventReminder.php`
  - Checks member still subscribed
  - Renders template
  - Calls NotificationService
  - Updates reminder_logs

### 6. Unsubscribe System (✅ Complete)

**Controller:**
- `/Users/freddy/Works/pelita/app/Http/Controllers/UnsubscribeController.php`
  - Public routes (no auth required)
  - Token-based preference management
  - Granular control per event type × channel

**Routes:** (Added to `/Users/freddy/Works/pelita/routes/web.php`)
```php
Route::get('unsubscribe/{token}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('unsubscribe/{token}', [UnsubscribeController::class, 'update'])->name('unsubscribe.update');
```

**View:**
- `/Users/freddy/Works/pelita/resources/views/unsubscribe/show.blade.php`
  - Clean, accessible UI
  - Global and per-event-type preferences
  - Success messaging
  - Dark mode support

---

## REMAINING TASKS

### 7. Email Base Layout (❌ Not Started)

**Create:**
- `/Users/freddy/Works/pelita/resources/views/emails/layouts/base.blade.php`

**Requirements:**
- Include organization branding
- Responsive design
- Auto-append unsubscribe footer (if template.append_unsubscribe_footer = true)
- Support both HTML emails and plain text fallback

**Example Structure:**
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'Event Reminder' }}</title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <!-- Organization Logo -->
        </div>

        <div style="background: white; padding: 30px; border-radius: 8px;">
            {!! $content !!}
        </div>

        @if($includeUnsubscribe ?? true)
            <div style="margin-top: 20px; padding: 20px; text-align: center; font-size: 12px; color: #666;">
                <p>You received this because you are scheduled for an event at {{ $organizationName }}.</p>
                <p>
                    <a href="{{ $unsubscribeUrl }}" style="color: #3b82f6;">
                        Manage your communication preferences
                    </a>
                </p>
            </div>
        @endif
    </div>
</body>
</html>
```

### 8. Organization Reminder Settings Component (❌ Not Started)

**Create:**
- Livewire component: `app/Livewire/Settings/Organization/Reminders.php`
- View: `resources/views/livewire/settings/organization/reminders.blade.php`

**Features:**
- Toggle: Enable/disable reminders org-wide
- Input: Default days before (integer, e.g., 3)
- Input: Default time (time picker, e.g., 3:00 PM)
- Select: Timezone (dropdown with common timezones)
- Save button

**Command to create:**
```bash
php artisan make:livewire Settings/Organization/Reminders
```

**Add route to web.php:**
```php
Route::get('settings/organization/reminders', \App\Livewire\Settings\Organization\Reminders::class)
    ->name('settings.organization.reminders');
```

### 9. Template Management Components (❌ Not Started)

**Create:**
- `app/Livewire/Settings/Templates/Index.php` - List templates
- `app/Livewire/Settings/Templates/Create.php` - Create template
- `app/Livewire/Settings/Templates/Edit.php` - Edit template

**Features:**
- Filter by type (email/SMS) and event type
- Rich text editor for email content
- Plain textarea for SMS content
- Show available parameters: {name}, {event_name}, {event_date}, {event_time}, {position_name}, {organization_name}, {unsubscribe_link}
- Preview with sample data
- Mark as default
- Toggle append_unsubscribe_footer

**Commands:**
```bash
php artisan make:livewire Settings/Templates/Index
php artisan make:livewire Settings/Templates/Create
php artisan make:livewire Settings/Templates/Edit
```

**Add routes:**
```php
Route::get('settings/templates', \App\Livewire\Settings\Templates\Index::class)->name('settings.templates.index');
Route::get('settings/templates/create', \App\Livewire\Settings\Templates\Create::class)->name('settings.templates.create');
Route::get('settings/templates/{template}/edit', \App\Livewire\Settings\Templates\Edit::class)->name('settings.templates.edit');
```

### 10. Event Type Form Updates (❌ Not Started)

**Update existing:** `app/Livewire/EventType/Edit.php` (or Create)

**Add fields:**
- Checkbox: Override organization reminder settings
- Input: Days before (shown if override checked)
- Input: Time (shown if override checked)
- Select: Email template (dropdown of email templates for this org)
- Select: SMS template (dropdown of SMS templates for this org)

### 11. Event Form Updates (❌ Not Started)

**Update existing:** `app/Livewire/Event/Edit.php` (or Create)

**Add fields:**
- Radio buttons: Reminder mode (auto/manual/disabled)
- If manual: Button "Send Reminders Now"
- Section: "Scheduled Reminders" - Table showing:
  - Member name
  - Channel (email/SMS)
  - Scheduled time
  - Status
  - Actions (cancel if scheduled)

**Button handler for "Send Reminders Now":**
```php
public function sendRemindersNow()
{
    $scheduler = app(\App\Domains\Notifications\Services\ReminderScheduler::class);
    $scheduler->triggerManualReminders($this->event);

    $this->dispatch('alert', message: 'Reminders have been queued for sending');
}
```

### 12. Reminder Logs Dashboard (❌ Not Started)

**Create:**
- `app/Livewire/Reports/ReminderLogs.php`
- `resources/views/livewire/reports/reminder-logs.blade.php`

**Features:**
- Filterable Flux table:
  - Filter by event
  - Filter by member
  - Filter by status
  - Filter by date range
  - Filter by channel
- Columns:
  - Member name
  - Event name
  - Channel (badge)
  - Scheduled time
  - Sent time
  - Status (badge with colors)
  - Actions (view details modal)
- Modal to show:
  - Template snapshot (JSON)
  - Failure reason (if failed)
  - Full event/member details

**Command:**
```bash
php artisan make:livewire Reports/ReminderLogs
```

**Add route:**
```php
Route::get('reports/reminder-logs', \App\Livewire\Reports\ReminderLogs::class)->name('reports.reminder-logs');
```

### 13. Event Listeners (❌ Not Started)

**Create listeners in:** `app/Listeners/`

**Events to listen for:**
1. **Event Created** - Schedule reminders if mode = auto
2. **Event Updated** - Reschedule if date changed
3. **Event Deleted** - Cancel all scheduled reminders
4. **Member Removed from Event** - Cancel that member's reminders

**Example Listener:**
```php
// app/Listeners/ScheduleEventReminders.php
namespace App\Listeners;

use App\Domains\Notifications\Jobs\ScheduleEventReminders as ScheduleJob;
use App\Events\EventCreated;

class ScheduleEventRemindersListener
{
    public function handle(EventCreated $event): void
    {
        if ($event->event->reminder_mode === \App\Enums\ReminderMode::Auto) {
            ScheduleJob::dispatch($event->event->id);
        }
    }
}
```

**Register in:** `app/Providers/EventServiceProvider.php`
```php
protected $listen = [
    \App\Events\EventCreated::class => [
        \App\Listeners\ScheduleEventRemindersListener::class,
    ],
    \App\Events\EventUpdated::class => [
        \App\Listeners\RescheduleEventRemindersListener::class,
    ],
    \App\Events\EventDeleted::class => [
        \App\Listeners\CancelEventRemindersListener::class,
    ],
];
```

**Note:** You may need to create these event classes first if they don't exist.

### 14. Seeders (❌ Not Started)

**Create:**
- `database/seeders/CommunicationTemplateSeeder.php`

**Default Templates:**

**Email Template:**
```
Subject: Reminder: {event_name} on {event_date}
Content:
Hi {name},

This is a friendly reminder that you are scheduled for {event_name} on {event_date} at {event_time}.

Your role: {position_name}

We look forward to seeing you!

Best regards,
{organization_name}
```

**SMS Template:**
```
Hi {name}, reminder: {event_name} on {event_date} at {event_time}. Role: {position_name}. - {organization_name}
```

**Command to create:**
```bash
php artisan make:seeder CommunicationTemplateSeeder
```

**Run with:**
```bash
php artisan db:seed --class=CommunicationTemplateSeeder
```

### 15. Service Provider Bindings (❌ Not Started)

**Update:** `app/Providers/AppServiceProvider.php`

**Add to register() method:**
```php
use App\Domains\Notifications\Contracts\EmailProviderInterface;
use App\Domains\Notifications\Contracts\TemplateRendererInterface;
use App\Domains\Notifications\Providers\LaravelMailProvider;
use App\Domains\Notifications\Services\TemplateRenderer;

public function register(): void
{
    // Existing SMS bindings...

    // Notification system bindings
    $this->app->bind(EmailProviderInterface::class, LaravelMailProvider::class);
    $this->app->bind(TemplateRendererInterface::class, TemplateRenderer::class);
}
```

### 16. Code Formatting (❌ Not Started)

**Run:**
```bash
vendor/bin/pint --dirty
```

This will format all the newly created PHP files to match the project's coding standards.

---

## TESTING REQUIREMENTS

After implementation is complete, comprehensive tests should be written for:

### Unit Tests Needed:
- `TemplateRenderer` - Test placeholder replacement
- `ReminderScheduler` - Test time calculations with different timezones
- `NotificationService` - Test preference checking logic
- Model relationships and scopes

### Feature Tests Needed:
- Unsubscribe flow (GET/POST)
- Template CRUD operations
- Reminder scheduling when event created/updated
- Manual reminder triggering
- Email/SMS sending (with mocking)
- Job execution

### Integration Tests Needed:
- Full reminder flow: event created → job scheduled → reminder sent → log updated
- Preference override scenarios
- Timezone handling edge cases
- Event rescheduling scenarios

---

## CONFIGURATION REQUIREMENTS

### .env Variables to Add:
```env
# Mail Configuration (if not already set)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# SMS Configuration (already exists in project)
# Just ensure these are set for testing
```

### Queue Configuration:
Ensure queues are running:
```bash
php artisan queue:work
```

Or add to supervisor/systemd for production.

---

## USAGE FLOW

### For Administrators:

1. **Setup Organization Defaults:**
   - Go to Settings → Organization → Reminders
   - Enable reminders
   - Set default days before (e.g., 3)
   - Set default time (e.g., 3:00 PM)
   - Set timezone

2. **Create Templates:**
   - Go to Settings → Templates
   - Create email template with placeholders
   - Create SMS template with placeholders
   - Mark one of each as default OR assign to specific event types

3. **Configure Event Types (Optional):**
   - Edit event type
   - Override organization defaults if needed
   - Assign specific templates

4. **Create Events:**
   - Create event with date and members
   - Choose reminder mode (auto/manual/disabled)
   - If auto: reminders automatically scheduled
   - If manual: click "Send Reminders Now" when ready

5. **Monitor:**
   - Go to Reports → Reminder Logs
   - View send status
   - Investigate failures

### For Members:

1. **Receive Reminder:**
   - Email or SMS with event details
   - Contains unsubscribe link

2. **Manage Preferences:**
   - Click unsubscribe link in any reminder
   - See all event types
   - Toggle email/SMS per event type
   - Save preferences

---

## IMPORTANT IMPLEMENTATION NOTES

### Timezone Handling:
- All times stored in database as UTC
- Organization timezone used for display
- Event can override with its own timezone
- ReminderScheduler converts properly to UTC for queue delays

### Preference Checking:
- Checks event-type-specific preference first
- Falls back to global preference
- If no preference exists, assumes subscribed
- Preference created on first unsubscribe URL generation

### Template System:
- Templates belong to organization
- Can be global (event_type_id = null) or event-type-specific
- is_default flag for fallback selection
- Template snapshot stored in reminder_log for audit trail

### Job Queueing:
- ScheduleEventReminders runs immediately
- SendEventReminder delayed until scheduled time
- Uses Laravel's built-in delayed job feature
- Can be cancelled by updating reminder_log status

### Error Handling:
- All failures logged to reminder_logs with reason
- Email failures captured from provider
- SMS failures captured from existing SMSService
- Missing data (no email/phone) handled gracefully

---

## DEPENDENCIES

### Required Packages (Already Installed):
- Laravel 12
- Livewire 3
- Flux UI Pro
- Existing SMS domain (Twilio/Vonage)

### No New Dependencies Needed

---

## MIGRATION STEPS

1. **Upgrade PHP to 8.3+**
2. **Run migrations:**
   ```bash
   php artisan migrate
   ```
3. **Complete remaining tasks (7-16)**
4. **Run seeders:**
   ```bash
   php artisan db:seed --class=CommunicationTemplateSeeder
   ```
5. **Format code:**
   ```bash
   vendor/bin/pint --dirty
   ```
6. **Write tests**
7. **Run tests:**
   ```bash
   php artisan test
   ```

---

## SECURITY CONSIDERATIONS

- Unsubscribe tokens are 64-character random strings
- No authentication required for unsubscribe (token-based)
- Tokens regenerated on preference changes
- Rate limiting should be added to unsubscribe routes
- Email content sanitized (HTML emails)
- SMS content length validated (160 chars recommended)

---

## PERFORMANCE CONSIDERATIONS

- Uses queue system for async sending
- Eager loading in jobs to prevent N+1
- Indexes on all foreign keys and commonly queried fields
- Soft deletes for audit trail preservation
- JSON columns for flexible future expansion

---

## FUTURE ENHANCEMENTS (Not Implemented)

- Multi-reminder support (7 days before, 1 day before, etc.)
- Reminder templates with conditional logic
- WhatsApp/Telegram integration
- Member-facing preference management portal
- Reminder preview before sending
- A/B testing for templates
- Delivery analytics and reporting
- Template version control

---

## FILES CREATED

### Migrations (6 files)
- 2025_10_26_000001_add_reminder_settings_to_organizations_table.php
- 2025_10_26_000002_create_communication_templates_table.php
- 2025_10_26_000003_add_reminder_settings_to_event_types_table.php
- 2025_10_26_000004_add_reminder_settings_to_events_table.php
- 2025_10_26_000005_create_member_communication_preferences_table.php
- 2025_10_26_000006_create_reminder_logs_table.php

### Enums (3 files)
- app/Enums/NotificationChannel.php
- app/Enums/ReminderStatus.php
- app/Enums/ReminderMode.php

### Models (3 new + 4 updated)
- app/Models/CommunicationTemplate.php
- app/Models/MemberCommunicationPreference.php
- app/Models/ReminderLog.php
- app/Models/Organization.php (updated)
- app/Models/EventType.php (updated)
- app/Models/Event.php (updated)
- app/Models/Member.php (updated)

### Domain Layer (10 files)
- app/Domains/Notifications/Contracts/EmailProviderInterface.php
- app/Domains/Notifications/Contracts/TemplateRendererInterface.php
- app/Domains/Notifications/Services/NotificationService.php
- app/Domains/Notifications/Services/ReminderScheduler.php
- app/Domains/Notifications/Services/TemplateRenderer.php
- app/Domains/Notifications/Providers/LaravelMailProvider.php
- app/Domains/Notifications/Jobs/ScheduleEventReminders.php
- app/Domains/Notifications/Jobs/SendEventReminder.php
- app/Domains/Notifications/DTOs/ReminderContext.php

### Controllers (1 file)
- app/Http/Controllers/UnsubscribeController.php

### Views (1 file)
- resources/views/unsubscribe/show.blade.php

### Routes
- routes/web.php (updated with 2 new routes)

---

## ARCHITECTURE DECISIONS

### Why Domain-Driven Design?
- Follows existing SMS domain pattern
- Clear separation of concerns
- Easy to swap providers (email, SMS)
- Testable in isolation

### Why Queue-Based?
- Non-blocking for user
- Handles high volume
- Built-in retry mechanism
- Scheduled delivery support

### Why Token-Based Unsubscribe?
- No authentication required
- Complies with CAN-SPAM
- Works for members without user accounts
- Simple to implement

### Why JSON for Template Snapshot?
- Audit trail of what was sent
- Handle template changes gracefully
- Debugging failures easier
- Immutable record

---

## MAINTENANCE

### Regular Tasks:
- Clean up old reminder_logs (older than X months)
- Monitor failed reminders
- Update templates based on feedback
- Review unsubscribe rates

### Monitoring:
- Queue length (ensure workers running)
- Failed job count
- Email/SMS provider status
- Reminder send success rate

---

This document serves as a complete implementation guide and reference for the event reminder notification system.
