<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Detailed itinerary - Annex J</title>
    <style>
        @page { margin: 26px 38px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.1; color: #111; }
        .sheet { max-width: 730px; margin: 0 auto; }
        .source { text-align: center; font-size: 8px; }
        h1 { font-size: 12px; margin: 12px 0; }
        h2 { font-size: 10px; margin: 10px 0 4px; }
        .letterhead { text-align: center; white-space: pre-line; font-weight: bold; margin: 10px 0; }
        .letterhead img { max-width: 100%; max-height: 45px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        td, th { border: 1px solid #111; padding: 1.5px 3px; vertical-align: top; overflow-wrap: break-word; }
        .basic td { width: 50%; }
        .itinerary td { padding: 5px 3px; white-space: pre-line; word-wrap: break-word; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .signatures { margin-top: 14px; page-break-inside: avoid; }
        .signatures td { border: 0; width: 33.33%; padding: 0 10px 0 0; }
        .signature-space { height: 22px; border-bottom: 1px solid #555; margin: 0 0 4px; }
        .footer { font-size: 7px; margin-top: 8px; color: #444; }
        .controls { font-size: 12px; border: 1px solid #aaa; background: #f8fafc; padding: 16px; margin-bottom: 18px; }
        .controls a { display: inline-block; padding: 10px; background: #173b66; color: white; }
        @if(empty($pdf))
        @media screen { body { background: #e8ecf0; } .sheet { background: #fff; padding: 26px; } }
        @endif
        @media print { .controls { display: none; } .sheet { max-width: none; padding: 0; } }
    </style>
</head>
<body>
<div class="sheet">
@if(isset($entries))
    <h1>Preserved itineraries (Annex J)</h1>
    <p>Submission {{ $reference }}. These preparation copies do not replace signed itinerary evidence.</p>
    <ul>@foreach($entries as $id => $entry)
        <li><a href="{{ route('itinerary.submission', ['publicId' => $publicId, 'version' => $id]) }}">{{ $entry['basic']['title'] }} - activity revision {{ $entry['activity_revision'] }}</a></li>
    @endforeach</ul>
@else
    @unless($pdf)
        <div class="controls">
            <strong>{{ $reference ? 'Preserved itinerary: '.$reference : 'Itinerary preparation preview' }}</strong>
            <p>Unsigned Annex J preparation copy. Printing does not submit the itinerary or authorize an activity. Sign externally and retain/upload signed evidence under the applicable checklist.</p>
            @if($missing)
                <strong>Complete these print fields:</strong>
                <ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <a href="{{ $downloadUrl }}">Download printable itinerary PDF</a>
            @endif
        </div>
    @endunless
    <div class="source">Annex J of CMO No. 24, series of 2026</div>
    <h1>ANNEX J. TEMPLATE: DETAILED ITINERARY</h1>
    <div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $itinerary['header']['letterhead_text'] }}@endif</div>
    <h2>I. BASIC INFORMATION</h2>
    <table class="basic">
        <tr><td colspan="2"><strong>Name of HEI:</strong> {{ $itinerary['header']['hei_name'] }}</td></tr>
        <tr><td><strong>Address:</strong> {{ $itinerary['header']['address'] }}</td><td><strong>Type of HEI (SUC/LUC/Private):</strong> {{ $itinerary['header']['hei_type'] }}</td></tr>
        <tr><td><strong>Title of off-campus activity/event/engagement:</strong> {{ $itinerary['basic']['title'] }}</td><td><strong>Category (1, 2, or 3):</strong> {{ $itinerary['basic']['category'] }}</td></tr>
        <tr><td><strong>Proposed Date/s:</strong> {{ $itinerary['basic']['proposed_dates'] }}</td><td><strong>Total No. of Participants:</strong> {{ $itinerary['basic']['participants'] }}</td></tr>
        @foreach(['venue' => 'Venue or Location/s', 'office' => 'HEI Office/Organization Responsible', 'pic' => 'Personnel-in-Charge (PIC)', 'apic' => 'Assistant PIC (if applicable)'] as $key => $label)
            <tr><td colspan="2"><strong>{{ $label }}:</strong> {{ $itinerary['basic'][$key] }}</td></tr>
        @endforeach
    </table>
    <h2>II. DETAILED ITINERARY</h2>
    <table class="itinerary">
        <thead><tr>
            <th style="width:12%">Date</th><th style="width:9%">Time</th><th style="width:24%">Activity</th>
            <th style="width:18%">Venue/Location</th><th style="width:20%">Information on DOT Accreditation (if applicable)</th><th style="width:17%">Person/s Responsible</th>
        </tr></thead>
        <tbody>
        @foreach($itinerary['rows'] as $row)
            @php
                $splitCell = static fn (string $text): array => collect(preg_split('/\R/u', $text))
                    ->flatMap(static fn (string $line): array => mb_str_split($line, 45) ?: [''])
                    ->chunk(4)->map(static fn ($lines): string => $lines->implode("\n"))->all();
                $cells = [];
                foreach (['date', 'time', 'activity', 'location', 'dot_accreditation', 'responsible_person'] as $field) {
                    $cells[$field] = $splitCell((string) ($row[$field] ?? ''));
                }
                $count = max(array_map('count', $cells));
            @endphp
            @for($part = 0; $part < $count; $part++)
                <tr>@foreach($cells as $field => $chunks)<td>{{ $chunks[$part] ?? '' }}</td>@endforeach</tr>
            @endfor
        @endforeach
        @for($empty = count($itinerary['rows']); $empty < 4; $empty++)
            <tr>@for($column = 0; $column < 6; $column++)<td style="height:22px"></td>@endfor</tr>
        @endfor
        </tbody>
    </table>
    <table class="signatures"><tr>
        @foreach(['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $role => $label)
            <td><strong>{{ $label }}:</strong><div class="signature-space"></div>
                {{ data_get($itinerary, 'signatories.'.$role.'.name') }}<br>
                {{ $role === 'prepared_by' ? 'Signature over printed name' : 'Signature over printed name of authorized HEI official' }}<br>
                {{ data_get($itinerary, 'signatories.'.$role.'.position') }}<br>Designation/Position
                @if($role === 'prepared_by')<br>Contact Number: {{ data_get($itinerary, 'signatories.prepared_by.contact') }}<br>Email address: {{ data_get($itinerary, 'signatories.prepared_by.email') }}@endif
            </td>
        @endforeach
    </tr></table>
    <div class="footer">Unsigned preparation copy | Annex J template version {{ $itinerary['template']['version'] }} | Activity revision {{ $itinerary['activity_revision'] }}</div>
@endif
</div>
</body>
</html>
