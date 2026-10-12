<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @include('filament.infolists.structured-values', ['values' => $getState() ?? []])
</x-dynamic-component>
