<x-layouts.app>
    <div class="max-w-7xl mx-auto p-6">
        <div class="bg-white dark:bg-zinc-800 shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Events</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage all events in your organization</p>
                    </div>
                    <a href="{{ route('events.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600 focus:bg-blue-700 dark:focus:bg-blue-600 active:bg-blue-900 dark:active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        Add Event
                    </a>
                </div>
            </div>

            <div class="p-6">
                <livewire:event.index />
            </div>
        </div>
    </div>
</x-layouts.app>