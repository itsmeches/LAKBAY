<x-filament-panels::page>
    <x-filament::section heading="Versioned policy baseline">
        <p>Confirmed rules cannot be edited. Copy a version to propose changes. CMO 24 requires verified publication and a publication reference; effectivity is derived at +15 days. Issuance is not publication.</p>
        @foreach($policies as $policy)
            <p>{{ $policy->code }} — {{ $policy->status }} — {{ $policy->requirements_count }} rules. Publication: {{ $policy->publication_on?->format('Y-m-d') ?? 'Not verified' }}. Effectivity: {{ $policy->effective_on?->format('Y-m-d') ?? 'Pending' }}.</p>
            @if($policy->confirmation_reference)<p>{{ $policy->confirmation_reference }}</p>@endif
        @endforeach
        <p>CMO 24 covers all 22 Section 14 rows, with annual, pre-activity, post-activity and requested-evidence stages. Category 3 timing gaps and the formal change-report anchor remain explicit until clarified.</p>
        <p>Configured working-day calendar: {{ $calendarVersion }}. Once referenced by a submission, its holidays are locked. Use a new version for later changes.</p>
    </x-filament::section>
</x-filament-panels::page>
