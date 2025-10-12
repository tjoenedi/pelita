<x-layouts.app>
    <div class="p-8">
        <h1>Test Flux Table 2</h1>

        <!-- Test with table.columns syntax -->
        <flux:table>
            <x-flux::table.columns>
                <x-flux::table.column>Test Column</x-flux::table.column>
            </x-flux::table.columns>
            <x-flux::table.rows>
                <x-flux::table.row>
                    <x-flux::table.cell>Test Cell</x-flux::table.cell>
                </x-flux::table.row>
            </x-flux::table.rows>
        </flux:table>
    </div>
</x-layouts.app>