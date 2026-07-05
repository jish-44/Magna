<x-filament::section class="mb-4">
    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Media Library</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ number_format($grandTotal) }} {{ Str::plural('file', $grandTotal) }} &middot;
                    {{ $grandBytes >= 1_073_741_824
                        ? number_format($grandBytes / 1_073_741_824, 2).' GB'
                        : number_format($grandBytes / 1_048_576, 1).' MB' }} total
                    @if ($trashed > 0)
                        &middot; <span class="text-warning-600 dark:text-warning-400">{{ $trashed }} in recycle bin</span>
                    @endif
                </p>
            </div>
        </div>

        @if ($grandTotal > 0)
            {{-- Stacked distribution bar --}}
            <div class="h-2.5 rounded-full overflow-hidden flex w-full">
                @foreach ($categories as $cat)
                    <div
                        class="h-full transition-all duration-500"
                        style="width: {{ $cat['pct'] }}%; background-color: {{ $cat['color'] }};"
                        title="{{ $cat['label'] }}: {{ $cat['pct'] }}%"
                    ></div>
                @endforeach
            </div>

            {{-- Category cards --}}
            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));">
                @foreach ($categories as $cat)
                    <div class="rounded-lg p-3 border border-gray-200 dark:border-white/10
                                bg-white/40 dark:bg-white/5 space-y-1.5">
                        {{-- Category indicator + label --}}
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0"
                                  style="background-color: {{ $cat['color'] }};"></span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate">
                                {{ $cat['label'] }}
                            </span>
                        </div>

                        {{-- Stats --}}
                        <div class="space-y-0.5">
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($cat['count']) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $cat['bytes'] >= 1_048_576
                                    ? number_format($cat['bytes'] / 1_048_576, 1).' MB'
                                    : number_format($cat['bytes'] / 1_024, 0).' KB' }}
                                &middot; {{ $cat['pct'] }}%
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No media uploaded yet.</p>
        @endif
    </div>
</x-filament::section>
