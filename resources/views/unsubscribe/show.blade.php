<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Communication Preferences - {{ $organization->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-zinc-50 dark:bg-zinc-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-zinc-900 dark:text-white">
                    Communication Preferences
                </h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Manage your notification preferences for {{ $organization->name }}
                </p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
                    {{ $member->first_name }} {{ $member->last_name }}
                </p>
            </div>

            @if (session('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-zinc-800 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('unsubscribe.update', $token) }}" class="space-y-6 p-6">
                    @csrf

                    <div class="space-y-4">
                        <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4">
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                                Global Preferences
                            </h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                These settings apply to all event types
                            </p>
                            <div class="mt-4 space-y-3">
                                @php
                                    $globalPrefs = $preferences->get('global', collect());
                                    $globalEmailPref = $globalPrefs->where('channel', \App\Enums\NotificationChannel::Email)->first();
                                    $globalSmsPref = $globalPrefs->where('channel', \App\Enums\NotificationChannel::SMS)->first();
                                @endphp

                                <label class="flex items-center">
                                    <input type="checkbox"
                                           name="subscriptions[global_email]"
                                           value="1"
                                           @if(!$globalEmailPref || $globalEmailPref->is_subscribed) checked @endif
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-zinc-300 rounded dark:border-zinc-600 dark:bg-zinc-700">
                                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                                        Email notifications
                                    </span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox"
                                           name="subscriptions[global_sms]"
                                           value="1"
                                           @if(!$globalSmsPref || $globalSmsPref->is_subscribed) checked @endif
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-zinc-300 rounded dark:border-zinc-600 dark:bg-zinc-700">
                                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                                        SMS notifications
                                    </span>
                                </label>
                            </div>
                        </div>

                        @foreach ($eventTypes as $eventType)
                            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 last:border-0">
                                <h4 class="text-base font-medium text-zinc-900 dark:text-white">
                                    {{ $eventType->name }}
                                </h4>
                                @if ($eventType->description)
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $eventType->description }}
                                    </p>
                                @endif
                                <div class="mt-3 space-y-3">
                                    @php
                                        $eventPrefs = $preferences->get($eventType->id, collect());
                                        $emailPref = $eventPrefs->where('channel', \App\Enums\NotificationChannel::Email)->first();
                                        $smsPref = $eventPrefs->where('channel', \App\Enums\NotificationChannel::SMS)->first();
                                    @endphp

                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="subscriptions[event_{{ $eventType->id }}_email]"
                                               value="1"
                                               @if(!$emailPref || $emailPref->is_subscribed) checked @endif
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-zinc-300 rounded dark:border-zinc-600 dark:bg-zinc-700">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                                            Email notifications
                                        </span>
                                    </label>

                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="subscriptions[event_{{ $eventType->id }}_sms]"
                                               value="1"
                                               @if(!$smsPref || $smsPref->is_subscribed) checked @endif
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-zinc-300 rounded dark:border-zinc-600 dark:bg-zinc-700">
                                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                                            SMS notifications
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>

            <div class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                <p>You received this link because you are a member of {{ $organization->name }}.</p>
                <p class="mt-1">Your preferences will be saved and applied to future notifications.</p>
            </div>
        </div>
    </div>
</body>
</html>
