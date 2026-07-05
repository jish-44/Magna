<x-filament-panels::page>
    @assets
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endassets

    <div
        id="magna-dashboard-widgets"
        class="grid gap-6"
        style="grid-template-columns: repeat({{ $this->getColumns() }}, minmax(0, 1fr))"
    >
        @foreach ($this->getVisibleWidgets() as $widgetClass)
            <div
                data-widget-key="{{ class_basename($widgetClass) }}"
                class="relative group"
            >
                {{-- Drag handle — appears on hover --}}
                <div class="absolute top-3 right-3 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-150 pointer-events-none group-hover:pointer-events-auto">
                    <button
                        data-sort-handle
                        type="button"
                        title="Drag to reorder"
                        class="cursor-grab active:cursor-grabbing rounded-md bg-white dark:bg-gray-800 p-1.5 shadow-sm border border-gray-200 dark:border-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <circle cx="5" cy="3" r="1.5"/>
                            <circle cx="11" cy="3" r="1.5"/>
                            <circle cx="5" cy="8" r="1.5"/>
                            <circle cx="11" cy="8" r="1.5"/>
                            <circle cx="5" cy="13" r="1.5"/>
                            <circle cx="11" cy="13" r="1.5"/>
                        </svg>
                    </button>
                </div>

                <livewire:dynamic-component :is="$widgetClass" :key="$widgetClass" />
            </div>
        @endforeach
    </div>

    @script
    <script>
        (function () {
            const grid = document.getElementById('magna-dashboard-widgets');

            if (! grid || typeof Sortable === 'undefined') return;

            new Sortable(grid, {
                animation: 150,
                handle: '[data-sort-handle]',
                ghostClass: 'opacity-40',
                dragClass: 'shadow-xl',
                onEnd: function () {
                    const order = Array.from(grid.children).map(el => el.dataset.widgetKey);
                    $wire.reorderWidgets(order);
                },
            });
        })();
    </script>
    @endscript
</x-filament-panels::page>
