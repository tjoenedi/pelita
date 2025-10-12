{{-- Flux UI Pro Table Components Guide --}}

{{-- Basic Table Structure --}}
<flux:table>
    <flux:columns>
        <flux:column>Column 1</flux:column>
        <flux:column>Column 2</flux:column>
    </flux:columns>
    <flux:rows>
        <flux:row>
            <flux:cell>Cell 1</flux:cell>
            <flux:cell>Cell 2</flux:cell>
        </flux:row>
    </flux:rows>
</flux:table>

{{-- Table with Pagination (pass paginator object) --}}
<flux:table :paginate="$users">
    {{-- Table content --}}
</flux:table>

{{-- Sortable Columns --}}
<flux:columns>
    <flux:column
        sortable
        :sorted="$sortBy === 'name'"
        :direction="$sortBy === 'name' ? $sortDirection : null"
        wire:click="sort('name')">
        Name
    </flux:column>
</flux:columns>

{{-- Column Alignment Options --}}
<flux:columns>
    <flux:column align="start">Left Aligned (default)</flux:column>
    <flux:column align="center">Center Aligned</flux:column>
    <flux:column align="end">Right Aligned</flux:column>
</flux:columns>

{{-- Sticky Columns (for horizontal scrolling) --}}
<flux:columns>
    <flux:column sticky>Sticky First</flux:column>
    <flux:column>Normal Column</flux:column>
    <flux:column sticky>Sticky Last</flux:column>
</flux:columns>

{{-- Sticky Header (stays at top when scrolling) --}}
<flux:columns sticky>
    <flux:column>Sticky Header Column</flux:column>
</flux:columns>

{{-- Cell Variants --}}
<flux:rows>
    <flux:row>
        <flux:cell>Default styling</flux:cell>
        <flux:cell variant="strong">Bold/emphasized text</flux:cell>
    </flux:row>
</flux:rows>

{{-- Cell Alignment --}}
<flux:rows>
    <flux:row>
        <flux:cell align="start">Left aligned</flux:cell>
        <flux:cell align="center">Center aligned</flux:cell>
        <flux:cell align="end">Right aligned</flux:cell>
    </flux:row>
</flux:rows>

{{-- Row with Key for Livewire (important for loops) --}}
@foreach($items as $item)
    <flux:row :key="$item->id">
        <flux:cell>{{ $item->name }}</flux:cell>
    </flux:row>
@endforeach

{{-- Sticky Row (e.g., for totals row at bottom) --}}
<flux:rows>
    <flux:row>
        <flux:cell>Regular row</flux:cell>
    </flux:row>
    <flux:row sticky>
        <flux:cell variant="strong">Total (sticky bottom)</flux:cell>
    </flux:row>
</flux:rows>

{{-- Cell with Colspan --}}
<flux:row>
    <flux:cell colspan="3">Spans 3 columns</flux:cell>
</flux:row>

{{-- Complete Example with All Features --}}
<flux:table :paginate="$products" container:class="min-h-[400px]">
    {{-- Optional header slot for actions above table --}}
    <x-slot:header>
        <div class="flex justify-between items-center mb-4">
            <flux:heading>Products</flux:heading>
            <flux:button variant="primary">Add Product</flux:button>
        </div>
    </x-slot:header>

    {{-- Table columns --}}
    <flux:columns sticky>
        <flux:column
            sticky
            sortable
            :sorted="$sortBy === 'name'"
            :direction="$sortBy === 'name' ? $sortDirection : null"
            wire:click="sort('name')">
            Product Name
        </flux:column>

        <flux:column
            sortable
            :sorted="$sortBy === 'price'"
            :direction="$sortBy === 'price' ? $sortDirection : null"
            wire:click="sort('price')"
            align="end">
            Price
        </flux:column>

        <flux:column align="center">Stock</flux:column>

        <flux:column align="end" sticky>Actions</flux:column>
    </flux:columns>

    {{-- Table rows --}}
    <flux:rows>
        @forelse($products as $product)
            <flux:row :key="$product->id">
                <flux:cell sticky variant="strong">
                    {{ $product->name }}
                </flux:cell>

                <flux:cell align="end">
                    ${{ number_format($product->price, 2) }}
                </flux:cell>

                <flux:cell align="center">
                    <flux:badge
                        :variant="$product->stock > 10 ? 'success' : 'warning'">
                        {{ $product->stock }}
                    </flux:badge>
                </flux:cell>

                <flux:cell align="end" sticky>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="xs">
                            <flux:icon name="dots-vertical" />
                        </flux:button>

                        <flux:dropdown.items>
                            <flux:dropdown.item wire:click="edit({{ $product->id }})">
                                Edit
                            </flux:dropdown.item>
                            <flux:dropdown.item wire:click="delete({{ $product->id }})">
                                Delete
                            </flux:dropdown.item>
                        </flux:dropdown.items>
                    </flux:dropdown>
                </flux:cell>
            </flux:row>
        @empty
            <flux:row>
                <flux:cell colspan="4" align="center">
                    No products found.
                </flux:cell>
            </flux:row>
        @endforelse

        {{-- Optional totals row --}}
        <flux:row sticky>
            <flux:cell variant="strong">Total</flux:cell>
            <flux:cell align="end" variant="strong">
                ${{ number_format($products->sum('price'), 2) }}
            </flux:cell>
            <flux:cell align="center" variant="strong">
                {{ $products->sum('stock') }}
            </flux:cell>
            <flux:cell></flux:cell>
        </flux:row>
    </flux:rows>

    {{-- Optional footer slot --}}
    <x-slot:footer>
        <div class="text-sm text-gray-500 mt-4">
            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }}
            of {{ $products->total() }} results
        </div>
    </x-slot:footer>
</flux:table>

{{-- Notes on Usage:

1. STRUCTURE:
   - flux:table is the wrapper component
   - flux:columns contains flux:column components
   - flux:rows contains flux:row components
   - flux:row contains flux:cell components

2. PAGINATION:
   - Pass Laravel paginator to :paginate prop on flux:table
   - Pagination controls are automatically added

3. SORTING:
   - Add sortable prop to flux:column
   - Use :sorted and :direction props for state
   - Add wire:click to handle sorting

4. ALIGNMENT:
   - Both flux:column and flux:cell support align="start|center|end"
   - Column alignment affects header
   - Cell alignment affects content

5. STICKY:
   - flux:columns sticky - makes header stick to top
   - flux:column sticky - makes column stick horizontally (first/last)
   - flux:cell sticky - makes cell stick horizontally (first/last)
   - flux:row sticky - makes row stick to bottom (last row only)

6. VARIANTS:
   - flux:cell variant="strong" for emphasized text
   - Default variant uses lighter text color

7. LIVEWIRE:
   - Always use :key on flux:row in loops for proper reactivity
   - Use wire:click, wire:loading, etc. as normal

8. SLOTS:
   - header slot for content above table
   - footer slot for content below table (before pagination)

9. CONTAINER:
   - Use container:class to add classes to the wrapper div

--}}