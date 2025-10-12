<x-layouts.app>
    <x-slot name="header">
        <flux:heading size="xl">Edit Event Type: {{ $eventType->name }}</flux:heading>
    </x-slot>

    <livewire:event-type.edit :event-type="$eventType" />
</x-layouts.app>