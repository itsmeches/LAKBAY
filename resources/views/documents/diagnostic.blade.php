<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Diagnostic tool of HEI - Annex D</title>
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
        .criteria th { text-align: center; }
        .criteria .question { width: 66%; }
        .criteria .answer { width: 9%; text-align: center; }
        .criteria .remarks { width: 25%; }
        .group { font-weight: bold; background: #f0f0f0; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .summary { margin-top: 10px; page-break-inside: avoid; }
        .choice { margin: 4px 0; }
        .box { display: inline-block; width: 8px; height: 8px; border: 1px solid #111; text-align: center; font-size: 8px; line-height: 8px; }
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
    <h1>Preserved diagnostics (Annex D)</h1>
    <p>Submission {{ $reference }}. These preparation copies do not replace signed diagnostic evidence.</p>
    <ul>@foreach($entries as $id => $entry)
        <li><a href="{{ route('diagnostic.submission', ['publicId' => $publicId, 'version' => $id]) }}">{{ $entry['basic']['title'] }} - activity revision {{ $entry['activity_revision'] }}</a></li>
    @endforeach</ul>
@else
    @unless($pdf)
        <div class="controls">
            <strong>{{ $reference ? 'Preserved diagnostic: '.$reference : 'Diagnostic preparation preview' }}</strong>
            <p>Unsigned Annex D sample-based preparation copy. The HEI recommendation is not CHED approval or authorization. Sign externally and retain/upload signed evidence under the applicable checklist.</p>
            @if($missing)
                <strong>Complete these print fields:</strong>
                <ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <a href="{{ $downloadUrl }}">Download printable diagnostic PDF</a>
            @endif
        </div>
    @endunless
    <div class="source">Annex D of CMO No. 24, series of 2026</div>
    <h1>ANNEX D. SAMPLE: DIAGNOSTIC TOOL OF HEI</h1>
    <div class="letterhead">@if($letterhead)<img src="{{ $letterhead }}" alt="HEI letterhead">@else{{ $diagnostic['header']['letterhead_text'] }}@endif</div>
    <h2>I. BASIC INFORMATION</h2>
    <table class="basic">
        <tr><td colspan="2"><strong>Name of HEI:</strong> {{ $diagnostic['header']['hei_name'] }}</td></tr>
        <tr><td><strong>Address:</strong> {{ $diagnostic['header']['address'] }}</td><td><strong>Type of HEI (SUC/LUC/Private):</strong> {{ $diagnostic['header']['hei_type'] }}</td></tr>
        <tr><td><strong>Title of off-campus activity/event/engagement:</strong> {{ $diagnostic['basic']['title'] }}</td><td><strong>Category (1, 2, or 3):</strong> {{ $diagnostic['basic']['category'] }}</td></tr>
        <tr><td><strong>Proposed Date/s:</strong> {{ $diagnostic['basic']['proposed_dates'] }}</td><td><strong>Total No. of Participants:</strong> {{ $diagnostic['basic']['participants'] }}</td></tr>
        @foreach(['venue' => 'Venue or Location/s', 'office' => 'HEI Office/Organization Responsible', 'pic' => 'Personnel-in-Charge (PIC)', 'apic' => 'Assistant PIC (if applicable)'] as $key => $label)
            <tr><td colspan="2"><strong>{{ $label }}:</strong> {{ $diagnostic['basic'][$key] }}</td></tr>
        @endforeach
    </table>
    <h2>II. CRITERIA</h2>
    <table class="criteria">
        <thead><tr><th class="question">Particulars</th><th class="answer">Yes or No</th><th class="remarks">Remarks</th></tr></thead>
        <tbody>@foreach($diagnostic['groups'] as $group => $criteria)
            <tr><td colspan="3" class="group">{{ $group }}</td></tr>
            @foreach($criteria as $code => $question)
                <tr><td>{{ $question }}</td><td class="answer">{{ ucfirst(data_get($diagnostic, 'answers.'.$code.'.answer') ?? '') }}</td><td>{{ data_get($diagnostic, 'answers.'.$code.'.remarks') }}</td></tr>
            @endforeach
        @endforeach</tbody>
    </table>
    <div class="summary">
        <h2>SUMMARY AND RECOMMENDATION</h2>
        @foreach($diagnostic['recommendations'] as $code => $label)
            <div class="choice"><span class="box">{{ $diagnostic['recommendation'] === $code ? 'X' : '' }}</span> {{ $label }}</div>
        @endforeach
    </div>
    <table class="signatures"><tr>
        @foreach(['prepared_by' => 'Prepared by', 'reviewed_by' => 'Reviewed by', 'noted_by' => 'Noted by'] as $role => $label)
            <td><strong>{{ $label }}:</strong><div class="signature-space"></div>
                {{ data_get($diagnostic, 'signatories.'.$role.'.name') }}<br>
                {{ $role === 'prepared_by' ? 'Signature over printed name' : 'Signature over printed name of authorized HEI official' }}<br>
                {{ data_get($diagnostic, 'signatories.'.$role.'.position') }}<br>Designation/Position
                @if($role === 'prepared_by')<br>Contact Number: {{ data_get($diagnostic, 'signatories.prepared_by.contact') }}<br>Email address: {{ data_get($diagnostic, 'signatories.prepared_by.email') }}@endif
            </td>
        @endforeach
    </tr></table>
    <div class="footer">Unsigned preparation copy | Annex D sample version {{ $diagnostic['template']['version'] }} | Activity revision {{ $diagnostic['activity_revision'] }} | Criteria version {{ $diagnostic['criteria_version'] }}</div>
@endif
</div>
</body>
</html>
