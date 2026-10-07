<x-filament-panels::page>
    <x-filament::section heading="Regional overview"><dl>@foreach($report['totals'] as $label=>$value)<dt>{{ str_replace('_',' ',ucfirst($label)) }}</dt><dd>{{ number_format($value) }}</dd>@endforeach</dl><p>Student totals represent estimated participations, not unique students. Activity revisions and incident supplements are counted once. Confidential incident details are excluded.</p></x-filament::section>
    <x-filament::section heading="Your exports">@forelse($exports as $export)<p>#{{ $export->id }} — {{ $export->status }} @if($export->status==='ready' && $export->expires_at->isFuture())<a href="{{ route('exports.download',$export) }}">Download {{ strtoupper($export->filters['format']) }}</a>@endif</p>@empty<p>No exports requested.</p>@endforelse</x-filament::section>
    <x-filament::section heading="Frozen reporting snapshots">
        <p>Annual M&E reports are due in the last week of August. The exact cutoff and reporting period need CHED confirmation. Record external dispatch and receipt using the actions above; this platform does not send documents to OSDS.</p>
        @forelse($snapshots as $snapshot)
            <p>{{ $snapshot->period }} — {{ $snapshot->report_type }} — cutoff {{ $snapshot->cutoff_at->timezone(config('lakbay.timezone'))->format('Y-m-d H:i') }} — {{ $snapshot->totals['activities'] }} activities.</p>
            @if($snapshot->due_window)<p>{{ $snapshot->due_window }}</p>@endif
            <p>OSDS dispatch: {{ $snapshot->dispatch_reference ?? 'Not recorded' }}. Receipt: {{ $snapshot->receipt_reference ?? 'Not recorded' }}.</p>
        @empty<p>No snapshots prepared.</p>@endforelse
    </x-filament::section>
</x-filament-panels::page>
