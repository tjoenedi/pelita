<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UnsubscribeController;
use App\Livewire\Reports\ReminderLogs;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Organization\Reminders as OrganizationReminders;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Templates\Create as TemplatesCreate;
use App\Livewire\Settings\Templates\Edit as TemplatesEdit;
use App\Livewire\Settings\Templates\Index as TemplatesIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('unsubscribe/{token}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('unsubscribe/{token}', [UnsubscribeController::class, 'update'])->name('unsubscribe.update');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Organization Settings
    Route::get('settings/organization/reminders', OrganizationReminders::class)->name('settings.organization.reminders');

    // Communication Templates
    Route::get('settings/templates', TemplatesIndex::class)->name('settings.templates.index');
    Route::get('settings/templates/create', TemplatesCreate::class)->name('settings.templates.create');
    Route::get('settings/templates/{template}/edit', TemplatesEdit::class)->name('settings.templates.edit');

    // Reports
    Route::get('reports/reminder-logs', ReminderLogs::class)->name('reports.reminder-logs');

    Route::get('test-flux-table', [TestController::class, 'testFluxTable'])->name('test.flux-table');

    Route::get('members/index', [MemberController::class, 'index'])->name('members.index');
    Route::get('members/create', [MemberController::class, 'create'])->name('members.create');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');

    Route::get('positions/index', [PositionController::class, 'index'])->name('positions.index');
    Route::get('positions/create', [PositionController::class, 'create'])->name('positions.create');
    Route::get('positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');

    Route::get('events/index', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');

    Route::get('event-types/index', [EventTypeController::class, 'index'])->name('event-types.index');
    Route::get('event-types/create', [EventTypeController::class, 'create'])->name('event-types.create');
    Route::get('event-types/{eventType}/edit', [EventTypeController::class, 'edit'])->name('event-types.edit');
});

require __DIR__.'/auth.php';
